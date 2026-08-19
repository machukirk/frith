<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stop collecting last names.
 *
 * Dropped rather than left nullable and unused. Holding a surname we have
 * decided we do not need is the thing data minimisation is about, and the
 * column would otherwise sit there full of real names nobody looks at.
 *
 * This deletes the surnames already collected. That is the intended outcome.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('last_name');
        });

        // The admin panel builds its wording editor from these rows, so the
        // field has to go from the form definition too or it lingers there
        // offering to edit a label nothing renders.
        DB::table('form_fields')->where('key', 'last_name')->delete();
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('first_name');
        });
    }
};
