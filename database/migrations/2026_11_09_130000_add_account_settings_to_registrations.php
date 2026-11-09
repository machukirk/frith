<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The settings behind the account screens.
 *
 * Every one of these defaults to the quietest, least-exposed answer. Nobody
 * is opted into a badge, a notification or anything else by having an account
 * created for them, because the design's whole claim is that Frith is quiet
 * unless you ask it not to be.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // "Does this still sound like your family?" asks again every few
            // months. Two timestamps rather than one: when they last changed
            // their answers, and when they last said "not now", which are
            // different facts and would otherwise overwrite each other.
            $table->timestamp('experiences_reviewed_at')->nullable()->after('completed_at');
            $table->timestamp('experiences_snoozed_at')->nullable()->after('experiences_reviewed_at');

            // A badge they chose rather than earned. Off until asked for.
            $table->boolean('helping_hand')->default(false)->after('experiences_snoozed_at');

            // Badges they have but would rather not show. Earning one is a
            // fact; displaying it is a choice.
            $table->json('hidden_badges')->nullable()->after('helping_hand');

            // Which emails they want. Null means the defaults, which are the
            // ones the free plan sends anyway.
            $table->json('notification_prefs')->nullable()->after('hidden_badges');
            $table->boolean('quiet_hours')->default(true)->after('notification_prefs');

            // How many connection requests they want put in front of them.
            // Some weeks you have the energy and some weeks you do not.
            $table->string('request_pace', 20)->default('any')->after('quiet_hours');

            // A copy of their data, asked for and then sent by a person.
            $table->timestamp('data_requested_at')->nullable()->after('request_pace');

            // Leaving. The profile goes straight away; the rest is deleted on
            // a timer, which is what the screen promises.
            $table->timestamp('deletion_requested_at')->nullable()->after('data_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn([
                'experiences_reviewed_at',
                'experiences_snoozed_at',
                'helping_hand',
                'hidden_badges',
                'notification_prefs',
                'quiet_hours',
                'request_pace',
                'data_requested_at',
                'deletion_requested_at',
            ]);
        });
    }
};
