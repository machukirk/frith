<?php

namespace App\Support;

use App\Models\Connection;
use App\Models\Registration;
use Illuminate\Support\Collection;

/**
 * The menu behind the avatar in the desktop header.
 *
 * Its own class rather than a list in config/frith-nav, because two of these
 * rows carry a number that is only true at the moment it is read. A nav file
 * can hold a label; it cannot hold "2 waiting for you".
 *
 * The counts are the same ones the village and the Requests screen show, and
 * they are facts about this family rather than a badge to draw the eye. The
 * designs are explicit elsewhere that Frith has no streaks and no red counts,
 * so a row says what is waiting in words and says nothing at all when
 * nothing is.
 */
class AccountMenu
{
    /**
     * @return Collection<int, array{label: string, meta: ?string, url: string, current: bool}>
     */
    public static function rows(Registration $family, ?string $currentUrl = null): Collection
    {
        $currentUrl ??= url()->current();

        return collect([
            [
                'label' => 'Your village',
                'meta' => 'Back to your families',
                'url' => route('village'),
            ],
            [
                'label' => 'Requests',
                'meta' => self::waiting($family),
                'url' => route('requests'),
            ],
            [
                'label' => 'Your profile',
                'meta' => 'What other families see',
                'url' => route('account'),
            ],
            [
                'label' => 'Badges',
                'meta' => 'What you have earned',
                'url' => route('account.badges'),
            ],
        ])->map(fn (array $row) => [
            ...$row,
            'current' => $row['url'] === $currentUrl,
        ]);
    }

    /**
     * How many families are waiting on an answer from this one.
     *
     * Null when nobody is, so the row reads as a plain link rather than as a
     * zero. "0 waiting for you" is a worse sentence than no sentence.
     */
    public static function waiting(Registration $family): ?string
    {
        $count = self::waitingCount($family);

        return match (true) {
            $count === 0 => null,
            $count === 1 => '1 family is waiting for you',
            default => $count.' families are waiting for you',
        };
    }

    public static function waitingCount(Registration $family): int
    {
        $hidden = $family->separatedIds();

        return Connection::query()
            ->where('addressee_id', $family->id)
            ->where('status', Connection::PENDING)
            ->with('requester')
            ->get()
            ->reject(fn (Connection $c) => in_array($c->requester_id, $hidden, true)
                || $c->requester->hasLeft())
            ->count();
    }
}
