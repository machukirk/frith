<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets the email address be emptied when a family leaves.
 *
 * A family who deletes their account leaves a scrubbed row behind rather than
 * no row at all, because the row is what keeps their half of somebody else's
 * conversation readable. Every other column holding anything about them is
 * already nullable; the email was not, because until now every registration
 * had one.
 *
 * Null rather than a placeholder address, so the unique index does not
 * collide the second time somebody leaves. See App\Console\Commands\
 * PurgeDepartedFamilies for what else goes and what stays.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
