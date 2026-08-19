<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // What the family enjoys, and what makes an activity work for them.
            // Both nullable rather than defaulted to an empty array: null means
            // "never asked", [] means "asked, chose nothing", and the two are
            // worth telling apart when reading how far somebody got.
            $table->json('interests')->nullable()->after('support_areas');
            $table->text('interests_other')->nullable()->after('interests');
            $table->json('activity_supports')->nullable()->after('interests_other');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['interests', 'interests_other', 'activity_supports']);
        });
    }
};
