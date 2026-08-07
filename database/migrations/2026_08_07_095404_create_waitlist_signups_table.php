<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_signups', function (Blueprint $table) {
            $table->id();

            // Used in confirm/unsubscribe URLs so the row's real id — and with it
            // the size of the list — never leaves the building.
            $table->ulid('public_id')->unique();

            $table->string('email')->unique();

            // Not asked for on the coming soon form yet. Frith's value is local
            // density, so knowing where the list clusters is what tells us
            // whether a UK-wide launch will actually match anyone.
            $table->string('postcode_outcode', 4)->nullable();

            $table->string('source')->default('coming-soon');

            // Consent evidence. UK GDPR asks us to show what was agreed and when,
            // so the wording is snapshotted here rather than looked up later.
            $table->string('consent_version');
            $table->text('consent_text');
            $table->timestamp('consented_at');
            $table->string('consent_ip', 45)->nullable();
            $table->text('consent_user_agent')->nullable();

            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();

            $table->timestamps();

            // "Who do we actually email at launch" — confirmed and not unsubscribed.
            $table->index(['confirmed_at', 'unsubscribed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_signups');
    }
};
