<?php

namespace App\Support;

use App\Models\Registration;
use Illuminate\Support\Collection;

/**
 * Which emails a family gets, and which they are allowed to change.
 *
 * Two rules, in one place so no screen can get them half right:
 *
 *  - A family on the free plan cannot turn on a Frith+ switch. Not by posting
 *    the form, not by having an old value in the column.
 *  - Their own request always reaches the other family. That is what the
 *    design means by "Frith+ members are also always notified when you ask
 *    them": the sender's plan decides, not the recipient's. It is the one
 *    place somebody else's membership changes what we send you, and it is in
 *    the asker's favour, so a paid request is never missed.
 */
class NotificationSettings
{
    /**
     * @return Collection<string, array{
     *     key: string, label: string, blurb: string, on: bool,
     *     plus: bool, locked: bool, shown: bool
     * }>
     */
    public static function for(Registration $family): Collection
    {
        $stored = (array) ($family->notification_prefs ?? []);
        $hasPlus = $family->hasPlus();

        return collect(config('frith-notifications'))
            ->except('pace')
            ->map(function (array $switch, string $key) use ($stored, $hasPlus) {
                $allowed = $hasPlus || ! $switch['plus'];

                return [
                    'key' => $key,
                    'label' => $switch['label'],
                    'blurb' => $switch['blurb'],
                    // The floor is worth a row on the free plan, where it is
                    // the only email that arrives. With Frith+ it is implied
                    // by the switch above it, and showing both reads as a
                    // contradiction: one locked on, one the family just
                    // turned off, and no way to tell which wins.
                    'shown' => $key !== 'plus_asks' || ! $hasPlus,
                    // A stored yes only counts if the plan allows it. A family
                    // who had Frith+ and stopped keeps the preference in the
                    // column but stops getting the email, and gets it back
                    // if they rejoin rather than having to set it again.
                    'on' => $allowed
                        ? (bool) ($stored[$key] ?? $switch['free'])
                        : $switch['free'],
                    'plus' => $switch['plus'],
                    'locked' => $switch['locked'] || ! $allowed,
                ];
            });
    }

    /** Whether one email would actually be sent to this family. */
    public static function sends(Registration $family, string $key): bool
    {
        return (bool) (self::for($family)[$key]['on'] ?? false);
    }

    /**
     * The switches a family may post, which is not the same as the ones on
     * their screen: a locked one is rendered but never accepted.
     *
     * @return array<int, string>
     */
    public static function editable(Registration $family): array
    {
        return self::for($family)
            ->reject(fn (array $switch) => $switch['locked'])
            ->keys()
            ->all();
    }

    /** @return array<string, string> */
    public static function paces(): array
    {
        return config('frith-notifications.pace', []);
    }

    /** What they chose, or the default, which is to see whatever comes. */
    public static function pace(Registration $family): string
    {
        return array_key_exists((string) $family->request_pace, self::paces())
            ? (string) $family->request_pace
            : 'any';
    }

    public static function paceLabel(Registration $family): string
    {
        return self::paces()[self::pace($family)] ?? '';
    }
}
