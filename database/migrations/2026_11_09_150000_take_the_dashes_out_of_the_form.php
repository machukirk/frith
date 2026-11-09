<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Takes the em dashes out of the five form strings that live in the database.
 *
 * The house style does not allow an em dash anywhere, and a sweep of the code
 * has just removed every one. That is not enough on its own: config/frith-forms
 * and config/frith-taxonomy are only the FLOOR for the registration form, and
 * App\Support\FormDefinition layers the stored wording over the top. Change the
 * config alone and these five strings keep their dashes on screen, because the
 * database still wins.
 *
 * Page content needed no equivalent: the stored `pages` rows carry no dashes at
 * all, so for those the config really is what renders.
 *
 * Conditional, like every other reword here. Each change only applies where the
 * stored text is still the exact sentence that shipped; anything an editor has
 * since rewritten is left as they wrote it. A migration that quietly reverts
 * somebody's work is worse than one that leaves a string a version behind.
 *
 * Two of the five were last written by 2026_08_19_124615, which still holds the
 * dashed wording as the value it writes. That is deliberate and that file must
 * not be edited: its strings are match keys against rows already in the
 * database, and rewording one in place makes it match nothing.
 */
return new class extends Migration
{
    /** @var array<int, array{key: string, column: string, from: string, to: string}> */
    private const STEPS = [
        [
            'key' => 'areas',
            'column' => 'standfirst',
            'from' => 'Choose all that apply. You are registered from here — the more you share, the more meaningful your connections can be.',
            'to' => 'Choose all that apply. You are registered from here, and the more you share, the more meaningful your connections can be.',
        ],
    ];

    /** @var array<int, array{step: string, key: string, column: string, from: string, to: string}> */
    private const FIELDS = [
        [
            'step' => 'family',
            'key' => 'family_structures',
            'column' => 'help',
            'from' => 'Optional — this helps us understand family dynamics, and is never shown on your profile.',
            'to' => 'This is optional. It helps us understand family dynamics, and is never shown on your profile.',
        ],
        [
            'step' => 'family',
            'key' => 'children',
            'column' => 'help',
            'from' => 'Month and year only — we never ask for a date of birth.',
            'to' => 'We only ask for month and year, never a date of birth.',
        ],
    ];

    /** @var array<int, array{group: string, slug: string, from: string, to: string}> */
    private const OPTIONS = [
        [
            'group' => 'support_areas',
            'slug' => 'education-options',
            'from' => 'Exploring other education options — specialist, alternative provision or home education',
            'to' => 'Exploring other education options: specialist, alternative provision or home education',
        ],
        [
            'group' => 'support_areas',
            'slug' => 'working-with-professionals',
            'from' => 'Working with professionals — therapists, health or education teams',
            'to' => 'Working with professionals: therapists, health or education teams',
        ],
    ];

    public function up(): void
    {
        $this->apply('from', 'to');
    }

    public function down(): void
    {
        $this->apply('to', 'from');
    }

    private function apply(string $was, string $becomes): void
    {
        foreach (self::STEPS as $change) {
            DB::table('form_steps')
                ->where('key', $change['key'])
                ->where($change['column'], $change[$was])
                ->update([$change['column'] => $change[$becomes], 'updated_at' => now()]);
        }

        foreach (self::FIELDS as $change) {
            $steps = DB::table('form_steps')->where('key', $change['step'])->pluck('id');

            DB::table('form_fields')
                ->whereIn('form_step_id', $steps)
                ->where('key', $change['key'])
                ->where($change['column'], $change[$was])
                ->update([$change['column'] => $change[$becomes], 'updated_at' => now()]);
        }

        foreach (self::OPTIONS as $change) {
            DB::table('form_options')
                ->where('group', $change['group'])
                ->where('slug', $change['slug'])
                ->where('label', $change[$was])
                ->update(['label' => $change[$becomes], 'updated_at' => now()]);
        }
    }
};
