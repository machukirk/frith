<?php

use App\Support\RegistrationFlow;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Clears out the step rows for screens the registration redesign merged away.
 *
 * Where you live moved onto the first screen, your children onto the household
 * one, and "Finding your Frith" split between hopes and interests. Their rows
 * are editor content for screens that no longer exist: the form itself already
 * ignores them, but the editor lists whatever is in this table, and offering
 * somebody a screen they cannot reach is worse than offering them nothing.
 *
 * Steps are safe to delete, unlike options: no registration points at one. The
 * wording that survived the merge lives on the new steps, seeded from config.
 */
return new class extends Migration
{
    public function up(): void
    {
        $keep = RegistrationFlow::STEPS;

        $retired = DB::table('form_steps')->whereNotIn('key', $keep)->pluck('id');

        if ($retired->isEmpty()) {
            return;
        }

        DB::table('form_fields')->whereIn('form_step_id', $retired)->delete();
        DB::table('form_steps')->whereIn('id', $retired)->delete();
    }

    public function down(): void
    {
        // Nothing to put back: these screens no longer exist, and the seeder
        // is what builds the ones that do.
    }
};
