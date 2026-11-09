<?php

namespace App\Support;

use App\Models\Registration;

/**
 * How alike two families are.
 *
 * Two different numbers come out of this, and they are not the same thing.
 *
 * The SCORE orders a list. It is the weighting from the Data Collection
 * workbook: experiences 0.35, children's ages 0.25, location 0.20, how they
 * like to connect 0.10, interests 0.10. It is never shown to anybody.
 *
 * The COUNT is what a family reads: "6 things in common". It is a plain tally
 * of the answers two families actually share, because a weighted decimal
 * means nothing to a person and a count can be checked against the profile
 * in front of them.
 *
 * Frith links by experience rather than by diagnosis, which is why the
 * experience overlap carries more than a third of the score on its own.
 */
class Matching
{
    private const WEIGHTS = [
        'experiences' => 0.35,
        'children' => 0.25,
        'location' => 0.20,
        'connection' => 0.10,
        'interests' => 0.10,
    ];

    /** 0 to 1. For ordering, never for display. */
    public static function score(Registration $a, Registration $b): float
    {
        $parts = [
            'experiences' => self::experienceOverlap($a, $b),
            'children' => self::childrenOverlap($a, $b),
            'location' => self::locationOverlap($a, $b),
            'connection' => self::listOverlap($a->connection_styles, $b->connection_styles),
            'interests' => self::listOverlap($a->interests, $b->interests),
        ];

        $score = 0.0;

        foreach ($parts as $key => $value) {
            $score += $value * self::WEIGHTS[$key];
        }

        return round($score, 4);
    }

    /**
     * The number a family is shown. Every shared answer counts once, and only
     * answers both families actually gave.
     */
    public static function inCommon(Registration $a, Registration $b): int
    {
        $shared = count(self::sharedExperiences($a, $b));

        foreach (['support_areas', 'interests', 'hopes', 'connection_styles'] as $field) {
            $shared += count(array_intersect(
                (array) ($a->{$field} ?? []),
                (array) ($b->{$field} ?? []),
            ));
        }

        // A child within a year of one of theirs is something in common, and
        // it is usually the first thing two parents find.
        if (self::closestChildGap($a, $b) !== null && self::closestChildGap($a, $b) <= 1) {
            $shared++;
        }

        return $shared;
    }

    /** @return array<int, string> "category:item" pairs both families chose. */
    public static function sharedExperiences(Registration $a, Registration $b): array
    {
        $theirs = $b->experiences->map(fn ($e) => $e->category.':'.$e->item)->all();

        return array_values(array_intersect(
            $a->experiences->map(fn ($e) => $e->category.':'.$e->item)->all(),
            $theirs,
        ));
    }

    private static function experienceOverlap(Registration $a, Registration $b): float
    {
        $mine = $a->experiences->count();
        $theirs = $b->experiences->count();

        if ($mine === 0 || $theirs === 0) {
            return 0.0;
        }

        // Against the smaller of the two, so a family who answered three
        // statements is not punished for matching somebody who answered
        // thirty.
        return min(1.0, count(self::sharedExperiences($a, $b)) / min($mine, $theirs));
    }

    /**
     * Closeness of the children's ages, not whether they are the same age.
     * Within a year is a full match, and it tails off to nothing at six.
     */
    private static function childrenOverlap(Registration $a, Registration $b): float
    {
        $gap = self::closestChildGap($a, $b);

        if ($gap === null) {
            return 0.0;
        }

        return max(0.0, 1 - (max(0, $gap - 1) / 5));
    }

    /** Whole years between the nearest pair of children, or null. */
    private static function closestChildGap(Registration $a, Registration $b): ?int
    {
        $mine = self::childMonths($a);
        $theirs = self::childMonths($b);

        if ($mine === [] || $theirs === []) {
            return null;
        }

        $closest = null;

        foreach ($mine as $one) {
            foreach ($theirs as $other) {
                $gap = (int) floor(abs($one - $other) / 12);
                $closest = $closest === null ? $gap : min($closest, $gap);
            }
        }

        return $closest;
    }

    /** @return array<int, int> each child as a count of months, for comparing. */
    private static function childMonths(Registration $family): array
    {
        return $family->children
            ->map(fn ($child) => ((int) $child->birth_year * 12) + (int) $child->birth_month)
            ->all();
    }

    /**
     * Same outcode is the whole mark. The same postcode area, the letters at
     * the front, is half: TN13 and TN9 are a reasonable drive apart but they
     * are the same part of the country.
     */
    private static function locationOverlap(Registration $a, Registration $b): float
    {
        $mine = strtoupper(trim((string) $a->postcode_outcode));
        $theirs = strtoupper(trim((string) $b->postcode_outcode));

        if ($mine === '' || $theirs === '') {
            return 0.0;
        }

        if ($mine === $theirs) {
            return 1.0;
        }

        return self::area($mine) === self::area($theirs) ? 0.5 : 0.0;
    }

    private static function area(string $outcode): string
    {
        preg_match('/^[A-Z]{1,2}/', $outcode, $matches);

        return $matches[0] ?? '';
    }

    /**
     * @param  array<int, string>|null  $mine
     * @param  array<int, string>|null  $theirs
     */
    private static function listOverlap(?array $mine, ?array $theirs): float
    {
        $mine = (array) $mine;
        $theirs = (array) $theirs;

        if ($mine === [] || $theirs === []) {
            return 0.0;
        }

        return count(array_intersect($mine, $theirs)) / min(count($mine), count($theirs));
    }
}
