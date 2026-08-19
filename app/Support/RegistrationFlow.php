<?php

namespace App\Support;

/**
 * The order of the registration screens, in one place.
 *
 * Six numbered steps, and the form is short on purpose: every extra screen is
 * somewhere a tired person puts the phone down, and the design goal is that
 * putting the phone down still leaves them registered.
 *
 * The detail questions are the exception to the numbering. They follow step
 * five and there is one per area of family life the person chose, so counting
 * them would mean the form got longer the more honest somebody was — a form
 * that punishes you for answering it. They deepen step five instead, and the
 * counter stays on five throughout however many of them there are.
 *
 * Interests comes last deliberately. It is the lightest question on the form
 * and the only one purely about what a family likes, so it is the kindest note
 * to end on — and if somebody stops before it, nothing that matters for
 * matching them has been lost.
 */
class RegistrationFlow
{
    /** @var array<int, string> */
    public const STEPS = ['you', 'location', 'family', 'children', 'support', 'interests', 'finding'];

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

    /** The step the detail questions belong to, rather than follow. */
    public const DETAIL_STEP = 'support';

    /**
     * Where Back goes from a numbered step, as a URL.
     *
     * Not simply the entry before it in STEPS: the detail questions sit
     * between support and interests, so going back from interests has to land
     * on the last of them rather than skipping the lot.
     *
     * @param  array<int, string>  $chosenAreas
     */
    public static function backFromStep(string $step, array $chosenAreas): ?string
    {
        if ($step === 'interests' && $chosenAreas !== []) {
            return route('register.experiences.show', end($chosenAreas));
        }

        $previous = self::previous($step);

        return $previous === null ? null : route('register.step', $previous);
    }

    /**
     * Where Back goes from one of the detail screens.
     *
     * @param  array<int, string>  $chosenAreas
     */
    public static function backFromArea(string $category, array $chosenAreas): string
    {
        $position = (int) array_search($category, $chosenAreas, true);

        return $position === 0
            ? route('register.step', self::DETAIL_STEP)
            : route('register.experiences.show', $chosenAreas[$position - 1]);
    }
}
