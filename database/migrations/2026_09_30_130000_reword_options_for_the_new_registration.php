<?php

use App\Models\FormOption;
use Illuminate\Database\Migrations\Migration;

/**
 * Brings the answer options in line with the redesigned registration.
 *
 * Slugs never move — they are what is stored against a family, and renaming
 * one silently detaches everyone who chose it. Only the wording changes, and
 * only where it is still the wording that shipped: if somebody has already
 * reworded an option in the admin panel, theirs stands.
 *
 * Two hopes the designs no longer offer are archived rather than deleted, so
 * anybody who chose one keeps an answer that still resolves to a label.
 */
return new class extends Migration
{
    /** slug => [old wording it must still have, new wording] */
    private const REWORDED = [
        'family_structures' => [
            'two-parents' => ['Two parents/carers', 'Two parents or carers'],
            'parenting-alone' => ['I am parenting on my own', 'Parenting on my own'],
            'blended' => ['Step-family/blended family', 'Step or blended family'],
            'foster-adoptive' => ['Foster/adoptive family', 'Fostering or adoption'],
        ],

        'hopes' => [
            'families-who-understand' => ['Families who understand our journey', 'Families who understand our experiences'],
            'friendships-for-my-child' => ['Friendships for my child', 'Friendships for me and my child'],
            'local-families' => ['Local families to meet', 'Local meet-ups and get-togethers'],
            'similar-experience' => ['Someone who has been through a similar experience', 'Advice from parents with lived experience'],
            'supporting-others' => ['Opportunities to support other families', 'To support other families'],
            'parents-to-talk-to' => ['Parents to talk to who understand', 'Someone to talk to who gets it'],
            'practical-advice' => ['Practical advice from other parents', 'Practical help with forms and processes'],
        ],

        'interests' => [
            'outdoors-nature' => ['Outdoors & Nature', 'Outdoors & nature'],
            'sport-movement' => ['Sport & Movement', 'Sport & movement'],
            'creative' => ['Creative Activities', 'Creative'],
            'games-technology-building' => ['Games, Technology & Building', 'Games & building'],
            'books-reading' => ['Books, Reading & Storytelling', 'Books & stories'],
            'vehicles-collecting' => ['Vehicles, Trains & Collecting', 'Vehicles & collecting'],
            'water' => ['Water Activities', 'Water'],
            'food-eating-out' => ['Food & Eating Out', 'Food'],
            'community' => ['Community Activities', 'Community'],
            'quiet-sensory' => ['Quiet & Sensory-Friendly Activities', 'Quiet & sensory'],
        ],

        'connection_styles' => [
            'family-meet-ups' => ['Family meet-ups and activities', 'Family meet-ups'],
        ],
    ];

    /** No longer offered. Archived, never deleted. */
    private const RETIRED = [
        'hopes' => ['activity-ideas', 'belonging'],
        // The designs ask nothing about who you would like to meet.
        'family_preferences' => ['nearby', 'similar-age', 'similar-interests', 'similar-day-to-day', 'open-to-any'],
    ];

    private const ADDED = [
        'hopes' => ['something-else' => 'Something else'],
    ];

    public function up(): void
    {
        foreach (self::REWORDED as $group => $options) {
            foreach ($options as $slug => [$was, $now]) {
                FormOption::query()
                    ->where('group', $group)
                    ->where('slug', $slug)
                    // Only where nobody has reworded it themselves.
                    ->where('label', $was)
                    ->update(['label' => $now]);
            }
        }

        foreach (self::RETIRED as $group => $slugs) {
            FormOption::query()
                ->where('group', $group)
                ->whereIn('slug', $slugs)
                ->whereNull('archived_at')
                ->update(['archived_at' => now()]);
        }

        foreach (self::ADDED as $group => $options) {
            $formId = FormOption::query()->where('group', $group)->value('form_id');

            if (! $formId) {
                continue;
            }

            $position = (int) FormOption::query()->where('group', $group)->max('position') + 1;

            foreach ($options as $slug => $label) {
                FormOption::query()->firstOrCreate(
                    ['form_id' => $formId, 'group' => $group, 'parent_id' => null, 'slug' => $slug],
                    ['label' => $label, 'position' => $position++],
                );
            }
        }
    }

    public function down(): void
    {
        foreach (self::REWORDED as $group => $options) {
            foreach ($options as $slug => [$was, $now]) {
                FormOption::query()
                    ->where('group', $group)
                    ->where('slug', $slug)
                    ->where('label', $now)
                    ->update(['label' => $was]);
            }
        }

        foreach (self::RETIRED as $group => $slugs) {
            FormOption::query()->where('group', $group)->whereIn('slug', $slugs)->update(['archived_at' => null]);
        }

        foreach (self::ADDED as $group => $options) {
            FormOption::query()->where('group', $group)->whereIn('slug', array_keys($options))->delete();
        }
    }
};
