<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The nine Journeys, and who is in them.
 *
 * A Journey is the unit the whole product is built on: joining one is what
 * makes a family visible to the others in it, so the membership row is a
 * consent record as much as a join. It carries its own timestamp for that
 * reason, and because the designs say "joined this week".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journeys', function (Blueprint $table) {
            $table->id();

            // In URLs and in every membership row, so it never moves. The
            // title above it is the editor's to reword freely.
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('blurb')->nullable();
            $table->unsignedSmallInteger('position')->default(0);

            // Archived, never deleted: a Journey that closes still has
            // families who were in it, and their membership has to keep
            // meaning something.
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
        });

        Schema::create('journey_registration', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            // One family, one membership. Leaving and rejoining replaces the
            // row rather than adding a second.
            $table->unique(['journey_id', 'registration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_registration');
        Schema::dropIfExists('journeys');
    }
};
