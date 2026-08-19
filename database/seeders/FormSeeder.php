<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Support\RegistrationFlow;
use Illuminate\Database\Seeder;

/**
 * Seeds the editable form content from config.
 *
 * firstOrCreate throughout: running this again on a live site must never
 * overwrite what an editor has written, and must never resurrect an option
 * somebody has deliberately archived.
 */
class FormSeeder extends Seeder
{
    public function run(): void
    {
        $slug = 'founders-registration';
        $config = config("frith-forms.{$slug}");

        $form = Form::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $config['name'], 'description' => $config['description']],
        );

        foreach (array_values(RegistrationFlow::STEPS) as $position => $key) {
            $stepConfig = $config['steps'][$key] ?? null;

            if ($stepConfig === null) {
                continue;
            }

            $step = $form->steps()->firstOrCreate(
                ['key' => $key],
                [
                    'heading' => $stepConfig['heading'],
                    'standfirst' => $stepConfig['standfirst'] ?? null,
                    'is_private' => $stepConfig['is_private'] ?? false,
                    'position' => $position,
                ],
            );

            foreach (array_values(array_keys($stepConfig['fields'] ?? [])) as $fieldPosition => $fieldKey) {
                $field = $stepConfig['fields'][$fieldKey];

                $step->fields()->firstOrCreate(
                    ['key' => $fieldKey],
                    [
                        'label' => $field['label'],
                        'help' => $field['help'] ?? null,
                        'placeholder' => $field['placeholder'] ?? null,
                        'position' => $fieldPosition,
                    ],
                );
            }
        }

        $this->seedSupportAreas($form);
        $this->seedInterests($form);
        $this->seedFlatGroup($form, 'family_structures');
        $this->seedFlatGroup($form, 'activity_supports');
    }

    private function seedSupportAreas(Form $form): void
    {
        $position = 0;

        foreach (config('frith-taxonomy.categories', []) as $slug => $category) {
            $parent = $form->options()->firstOrCreate(
                ['group' => 'support_areas', 'parent_id' => null, 'slug' => $slug],
                [
                    'label' => $category['label'],
                    'description' => $category['description'],
                    'position' => $position++,
                ],
            );

            $childPosition = 0;

            foreach ($category['items'] as $itemSlug => $label) {
                $form->options()->firstOrCreate(
                    ['group' => 'support_areas', 'parent_id' => $parent->id, 'slug' => $itemSlug],
                    ['label' => $label, 'position' => $childPosition++],
                );
            }
        }
    }

    /** Interests carry a description as well as a label; nothing sits under them. */
    private function seedInterests(Form $form): void
    {
        $position = 0;

        foreach (config('frith-taxonomy.interests', []) as $slug => $interest) {
            $form->options()->firstOrCreate(
                ['group' => 'interests', 'parent_id' => null, 'slug' => $slug],
                [
                    'label' => $interest['label'],
                    'description' => $interest['description'] ?? null,
                    'position' => $position++,
                ],
            );
        }
    }

    /** A group that is a plain slug => label list with no nesting. */
    private function seedFlatGroup(Form $form, string $group): void
    {
        $position = 0;

        foreach (config("frith-taxonomy.{$group}", []) as $slug => $label) {
            $form->options()->firstOrCreate(
                ['group' => $group, 'parent_id' => null, 'slug' => $slug],
                ['label' => $label, 'position' => $position++],
            );
        }
    }
}
