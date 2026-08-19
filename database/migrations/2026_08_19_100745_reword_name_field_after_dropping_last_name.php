<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rewords the name screen now that there is only one name field.
 *
 * Only where the wording is still the original. If somebody has already
 * rewritten it in the admin panel, theirs is left alone — a migration quietly
 * overwriting an editor's words is exactly what the seeder is careful not to do.
 */
return new class extends Migration
{
    private const CHANGES = [
        [
            'table' => 'form_steps',
            'key' => 'you',
            'was' => ['standfirst' => 'Your first name is what other families will see. Your last name stays private.'],
            'now' => ['standfirst' => 'Just a first name or a nickname — whatever you would like other families to call you.'],
        ],
        [
            'table' => 'form_fields',
            'key' => 'first_name',
            'was' => ['label' => 'First name'],
            'now' => [
                'label' => 'Your name',
                'help' => 'This is what other families see. A first name or a nickname is fine.',
            ],
        ],
    ];

    public function up(): void
    {
        foreach (self::CHANGES as $change) {
            DB::table($change['table'])
                ->where('key', $change['key'])
                ->where($change['was'])
                ->update($change['now']);
        }
    }

    public function down(): void
    {
        foreach (self::CHANGES as $change) {
            DB::table($change['table'])
                ->where('key', $change['key'])
                ->where(collect($change['now'])->only(array_keys($change['was']))->all())
                ->update($change['was'] + ['help' => null]);
        }
    }
};
