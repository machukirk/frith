<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();

            $table->string('email')->unique();

            // --- Section 1: the public profile they consent to show ---
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('postcode_outcode', 4)->nullable();

            // "Select all that apply" — families do not fit one box.
            $table->json('family_structures')->nullable();

            // --- Section 1, private: used for matching, never displayed ---
            // Category slugs only. The detailed items live in their own table
            // because a family picks many, and because section two is optional.
            $table->json('support_areas')->nullable();

            // --- Progress ---
            // Written on every step, so a drop-off is still a registration
            // rather than a lost visitor.
            $table->unsignedTinyInteger('furthest_step')->default(1);
            $table->timestamp('completed_at')->nullable();

            // --- Founder ---
            // Assigned once, at first save. Sequential and never reused, so
            // "Founder #47" stays true for that family forever.
            $table->unsignedInteger('founder_number')->nullable()->unique();

            // --- Consent evidence, same shape as the waiting list ---
            $table->string('consent_version');
            $table->text('consent_text');
            $table->timestamp('consented_at');
            $table->string('consent_ip', 45)->nullable();
            $table->text('consent_user_agent')->nullable();

            // Registration is not gated on this — they are registered the
            // moment they hit continue. This is about being able to reach them.
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('verification_sent_at')->nullable();

            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('source')->default('coming-soon');

            $table->timestamps();

            $table->index(['completed_at', 'unsubscribed_at']);
            $table->index('furthest_step');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
