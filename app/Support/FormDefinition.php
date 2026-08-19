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

            return $stored ? self::fromModel($stored) : self::fromConfig($slug);
        });
    }

    public static function forget(?string $slug): void
    {
        if ($slug !== null) {
            Cache::forget(self::CACHE_PREFIX.$slug);
        }
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

        foreach (['family_structures', 'activity_supports'] as $group) {
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
