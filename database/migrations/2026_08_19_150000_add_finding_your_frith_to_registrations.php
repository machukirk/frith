<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Finding your Frith: what they hope for, how they would rather go
            // about it, and who they would like to meet. Nullable for the same
            // reason as the interests columns — null means never asked, an
            // empty array means asked and nothing chosen.
            $table->json('hopes')->nullable()->after('activity_supports');
            $table->json('connection_styles')->nullable()->after('hopes');
            $table->json('family_preferences')->nullable()->after('connection_styles');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['hopes', 'connection_styles', 'family_preferences']);
        });
    }
};
