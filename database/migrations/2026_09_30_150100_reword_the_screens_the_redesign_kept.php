<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Brings the wording of the three surviving screens up to the redesign.
 *
 * The seeder cannot do this: it uses firstOrCreate precisely so that running
 * it again never overwrites what an editor has written. So each change here is
 * conditional on the stored text still being the exact sentence that shipped.
 * If somebody has touched it, theirs stands — a migration that quietly reverts
 * an editor's work is worse than one that leaves a screen a version behind.
 */
return new class extends Migration
{
    /** @var array<int, array{step: string, column: string, from: string, to: string}> */
    private const STEPS = [
        [
            'step' => 'you',
            'column' => 'standfirst',
            'from' => 'Just a first name or a nickname — whatever you would like other families to call you.',
            'to' => 'This is what other families will see.',
        ],
        [
            'step' => 'family',
            'column' => 'standfirst',
            'from' => 'Families come in all shapes and sizes. Choose as many as fit — or skip it.',
            'to' => 'Families come in all shapes and sizes. Share whatever feels relevant.',
        ],
        [
            'step' => 'interests',
            'column' => 'heading',
            'from' => 'Interests & activities',
            'to' => 'What does your family enjoy?',
        ],
        [
            'step' => 'interests',
            'column' => 'standfirst',
            'from' => 'Shared interests are often where friendships begin. Tell us what your family enjoys.',
            'to' => 'Shared interests are often where friendships begin.',
        ],
    ];

    /** @var array<int, array{step: string, field: string, column: string, from: string|null, to: string}> */
    private const FIELDS = [
        [
            'step' => 'you',
            'field' => 'first_name',
            'column' => 'help',
            'from' => 'This is what other families see. A first name or a nickname is fine.',
            'to' => 'A first name or a nickname is fine.',
        ],
        [
            'step' => 'interests',
            'field' => 'activity_supports',
            'column' => 'label',
            'from' => 'Are there things that help you enjoy activities?',
            'to' => 'Things that help you enjoy activities',
        ],
        [
            'step' => 'interests',
            'field' => 'activity_supports',
            'column' => 'help',
            'from' => null,
            'to' => 'Hosts use this to make meet-ups work for your family.',
        ],
    ];

    public function up(): void
    {
        foreach (self::STEPS as $change) {
            DB::table('form_steps')
                ->where('key', $change['step'])
                ->where($change['column'], $change['from'])
                ->update([$change['column'] => $change['to'], 'updated_at' => now()]);
        }

        foreach (self::FIELDS as $change) {
            $steps = DB::table('form_steps')->where('key', $change['step'])->pluck('id');

            $query = DB::table('form_fields')
                ->whereIn('form_step_id', $steps)
                ->where('key', $change['field']);

            $change['from'] === null
                ? $query->whereNull($change['column'])
                : $query->where($change['column'], $change['from']);

            $query->update([$change['column'] => $change['to'], 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (self::STEPS as $change) {
            DB::table('form_steps')
                ->where('key', $change['step'])
                ->where($change['column'], $change['to'])
                ->update([$change['column'] => $change['from'], 'updated_at' => now()]);
        }

        foreach (self::FIELDS as $change) {
            $steps = DB::table('form_steps')->where('key', $change['step'])->pluck('id');

            DB::table('form_fields')
                ->whereIn('form_step_id', $steps)
                ->where('key', $change['field'])
                ->where($change['column'], $change['to'])
                ->update([$change['column'] => $change['from'], 'updated_at' => now()]);
        }
    }
};
