<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The name other families see.
 *
 * Not the legal one and not necessarily a person's: the designs show "Fun
 * House", "The Ahmeds" and "Willow Cottage" alongside plain first names,
 * because it is whatever a family wants to be called. The home page already
 * promises exactly that.
 *
 * Nullable on purpose. Registration does not ask for it, so everybody who has
 * already signed up has none, and the village asks the first time it is
 * opened rather than a migration inventing one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('first_name');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
    }
};
