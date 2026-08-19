<?php

namespace App\Support;

/**
 * The order of the registration steps, in one place.
 *
 * Section one is short on purpose. Every extra screen is somewhere a tired
 * person puts the phone down, and the design goal here is that putting the
 * phone down still leaves them registered.
 *
 * Interests comes last deliberately. It is the lightest question on the form
 * and the only one that is purely about what a family likes, so it is the
 * kindest note to end on — and if somebody stops before it, nothing that
 * matters for matching them has been lost.
 */
class RegistrationFlow
{
    /** @var array<int, string> */
    public const STEPS = ['you', 'location', 'family', 'children', 'support', 'interests'];

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

    public static function exists(string $step): bool
    {
        return in_array($step, self::STEPS, true);
    }
}
