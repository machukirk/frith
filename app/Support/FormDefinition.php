<?php

namespace App\Support;

use App\Models\Form;
use Illuminate\Support\Facades\Cache;

/**
 * Reads a form's editable content, database over config.
 *
 * Same layering as page content: config/frith-forms.php and
 * config/frith-taxonomy.php are the floor, the database is what an editor has
 * actually written. A form nobody has opened in the admin still renders real
 * sentences, and a deploy that adds a field before anyone touches it does too.
 *
 * Archived options are kept in the returned structure and flagged, rather than
 * filtered out here. Rendering and validation drop them; label lookups must
 * not, or every family who chose one would see their answer vanish.
 */
class FormDefinition
{
    private const CACHE_PREFIX = 'form-definition:';

    /** @return array<string, mixed> */
    public static function for(string $slug): array
    {
        return Cache::rememberForever(self::CACHE_PREFIX.$slug, function () use ($slug) {
            $stored = rescue(
                fn () => Form::query()->where('slug', $slug)->with(['steps.fields', 'options.children'])->first(),
                rescue: null,
                report: false,
            );

            $floor = self::fromConfig($slug);

            return $stored ? self::layer($floor, self::fromModel($stored)) : $floor;
        });
    }

    public static function forget(?string $slug): void
    {
        if ($slug !== null) {
            Cache::forget(self::CACHE_PREFIX.$slug);
        }
    }

    /**
     * Database over config, key by key.
     *
     * Not a replacement: a deploy that adds a screen, a field or an option has
     * to work on a site that was seeded before it existed, or the new thing
     * renders as an empty heading until somebody remembers to re-seed. The
     * config decides which screens there are; the database decides what they
     * say.
     *
     * @param  array<string, mixed>  $floor
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private static function layer(array $floor, array $stored): array
    {
        $steps = [];

        // Config order, and config membership: a step that has been taken out
        // of the flow must not come back just because its old row is still in
        // the database.
        foreach ($floor['steps'] as $key => $step) {
            $saved = $stored['steps'][$key] ?? null;

            $steps[$key] = $saved === null ? $step : [
                'heading' => $saved['heading'] ?: $step['heading'],
                'standfirst' => $saved['standfirst'] ?? $step['standfirst'] ?? null,
                'is_private' => $saved['is_private'],
                'fields' => self::layerFields($step['fields'] ?? [], $saved['fields']),
            ];
        }

        $options = [];

        foreach ($floor['options'] as $group => $fromConfig) {
            $saved = $stored['options'][$group] ?? [];

            // Stored first, in the order an editor put them in, then anything
            // the config has gained since.
            $options[$group] = $saved;

            foreach ($fromConfig as $slug => $option) {
                if (! isset($options[$group][$slug])) {
                    $options[$group][$slug] = $option;

                    continue;
                }

                $options[$group][$slug]['items'] = self::layerItems(
                    $option['items'] ?? [],
                    $options[$group][$slug]['items'] ?? [],
                );
            }
        }

        // A group an editor added that the config has never heard of.
        return ['steps' => $steps, 'options' => $options + $stored['options']];
    }

    /**
     * @param  array<string, mixed>  $floor
     * @param  array<string, mixed>  $saved
     * @return array<string, mixed>
     */
    private static function layerFields(array $floor, array $saved): array
    {
        foreach ($floor as $key => $field) {
            $floor[$key] = [
                'label' => $saved[$key]['label'] ?? $field['label'] ?? '',
                'help' => $saved[$key]['help'] ?? $field['help'] ?? null,
                'placeholder' => $saved[$key]['placeholder'] ?? $field['placeholder'] ?? null,
            ];
        }

        return $floor + $saved;
    }

    /**
     * @param  array<string, mixed>  $floor
     * @param  array<string, mixed>  $saved
     * @return array<string, mixed>
     */
    private static function layerItems(array $floor, array $saved): array
    {
        return $saved + $floor;
    }

    /** @return array<string, mixed> */
    private static function fromModel(Form $form): array
    {
        $steps = [];

        foreach ($form->steps as $step) {
            $fields = [];

            foreach ($step->fields as $field) {
                $fields[$field->key] = [
                    'label' => $field->label,
                    'help' => $field->help,
                    'placeholder' => $field->placeholder,
                ];
            }

            $steps[$step->key] = [
                'heading' => $step->heading,
                'standfirst' => $step->standfirst,
                'is_private' => (bool) $step->is_private,
                'fields' => $fields,
            ];
        }

        $options = [];

        foreach ($form->options->whereNull('parent_id')->sortBy('position') as $option) {
            $items = [];

            foreach ($option->children->sortBy('position') as $child) {
                $items[$child->slug] = [
                    'label' => $child->label,
                    'archived' => $child->isArchived(),
                ];
            }

            $options[$option->group][$option->slug] = [
                'label' => $option->label,
                'description' => $option->description,
                'archived' => $option->isArchived(),
                'items' => $items,
            ];
        }

        return ['steps' => $steps, 'options' => $options];
    }

    /** @return array<string, mixed> */
    private static function fromConfig(string $slug): array
    {
        $steps = config("frith-forms.{$slug}.steps", []);

        $options = [
            'support_areas' => [],
            'family_structures' => [],
            'interests' => [],
            'activity_supports' => [],
            'hopes' => [],
            'connection_styles' => [],
            'family_preferences' => [],
        ];

        foreach (config('frith-taxonomy.categories', []) as $categorySlug => $category) {
            $options['support_areas'][$categorySlug] = [
                'label' => $category['label'],
                'description' => $category['description'],
                'archived' => false,
                'items' => collect($category['items'])
                    ->map(fn ($label) => ['label' => $label, 'archived' => false])
                    ->all(),
            ];
        }

        foreach (config('frith-taxonomy.interests', []) as $interestSlug => $interest) {
            $options['interests'][$interestSlug] = [
                'label' => $interest['label'],
                'description' => $interest['description'] ?? null,
                'archived' => false,
                'items' => [],
            ];
        }

        foreach (['family_structures', 'activity_supports', 'hopes', 'connection_styles', 'family_preferences'] as $group) {
            foreach (config("frith-taxonomy.{$group}", []) as $slug => $label) {
                $options[$group][$slug] = [
                    'label' => $label,
                    'description' => null,
                    'archived' => false,
                    'items' => [],
                ];
            }
        }

        return ['steps' => $steps, 'options' => $options];
    }
}
