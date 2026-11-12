<?php

namespace App\Support;

/**
 * The order of the registration screens, in one place.
 *
 * Six screens, and each one is a whole question rather than a fragment of one.
 * The earlier build asked where you live on its own screen and about your
 * children on another; the hi-fi puts a name, an email and a postcode together
 * because they are all "who are you", and a household and its children
 * together because they are both "who is at home".
 *
 * The detail questions used to be a screen per area of family life, up to
 * eight of them, with a counter frozen so the form did not appear to grow the
 * more somebody told us. They are one screen now, an accordion per area, which
 * is the same information without the sleight of hand.
 *
 * Every screen is short on purpose. Every extra one is somewhere a tired
 * person puts the phone down, and the design goal is that putting the phone
 * down still leaves them registered.
 */
class RegistrationFlow
{
    /** @var array<int, string> */
    public const STEPS = ['you', 'family', 'hopes', 'areas', 'experiences', 'interests'];

    public static function number(string $step): int
    {
        $index = array_search($step, self::STEPS, true);

        return $index === false ? 1 : $index + 1;
    }

    public static function total(): int
    {
        return count(self::STEPS);
    }

    public static function next(string $step): ?string
    {
        $index = array_search($step, self::STEPS, true);

        return self::STEPS[$index + 1] ?? null;
    }

    public static function previous(string $step): ?string
    {
        $index = array_search($step, self::STEPS, true);

        return $index > 0 ? self::STEPS[$index - 1] : null;
    }

    /**
     * The screen a family should come back to.
     *
     * furthest_step is recorded as the number of the screen AFTER the last
     * one answered, so it is already the one to resume at. Clamped, because a
     * family who finished holds a number past the end of the list, and the
     * form the number was written against may since have lost a screen.
     */
    public static function resumeAt(int $furthestStep): string
    {
        $index = max(0, min($furthestStep - 1, count(self::STEPS) - 1));

        return self::STEPS[$index];
    }

    public static function exists(string $step): bool
    {
        return in_array($step, self::STEPS, true);
    }

    /** Where Back goes, as a URL, or null on the first screen. */
    public static function backFrom(string $step): ?string
    {
        $previous = self::previous($step);

        return $previous === null ? null : route('register.step', $previous);
    }
}
