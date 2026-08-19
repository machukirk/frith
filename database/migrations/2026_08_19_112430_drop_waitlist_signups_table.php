<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The email waiting list is gone, replaced by the Founders registration.
 *
 * Dropped rather than kept dormant. It holds email addresses and consent
 * records for a thing that no longer exists, and personal data nobody is going
 * to use is personal data nobody should be holding.
 *
 * Anyone on it has to be invited to register properly — their consent was to
 * "one email when we launch", which is not consent to the registration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('waitlist_signups');
    }

    public function down(): void
    {
        Schema::create('waitlist_signups', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('email')->unique();
            $table->string('source')->default('coming-soon');
            $table->string('consent_version');
            $table->text('consent_text');
            $table->timestamp('consented_at')->useCurrent();
            $table->string('consent_ip', 45)->nullable();
            $table->text('consent_user_agent')->nullable();
            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->index(['confirmed_at', 'unsubscribed_at']);
        });
    }
};
