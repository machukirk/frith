<?php

namespace App\Support;

use App\Models\Connection;
use App\Models\Registration;
use Illuminate\Support\Collection;

/**
 * The small markers on a family's profile.
 *
 * Read the design copy before changing anything here, because the rules are
 * the point: "Badges are descriptive, not rewards. They exist so a family
 * deciding whether to reach out has a little more to go on, not to encourage
 * you to use Frith more."
 *
 * So, deliberately:
 *
 *  - Nothing is counted. There is no score, no total and no leaderboard.
 *  - Nothing expires, and nothing is lost.
 *  - A badge is derived from facts we already hold rather than stored as a
 *    prize, so there is no awarding and nothing to award at the wrong moment.
 *  - A family is never told what another family has not got.
 *  - Any of them can be hidden, and hiding one tells nobody.
 *
 * One exception to "derived": the Helping hand is a choice rather than a fact
 * about the past, so it has a column. It is still not earned.
 *
 * Two methods, and the split matters. visible() is what another family sees:
 * a slug and a title, nothing else. all() is the family's own screen, and it
 * carries the explanations, including, for the first connection, who it was
 * with. That sentence is theirs and must never travel, which is why it is
 * not in the shape that crosses between families.
 */
class Badges
{
    /** @var array<string, string> */
    private const TITLES = [
        'first-frith-family' => 'First Frith Family',
        'one-year' => 'One year',
        'profile-complete' => 'Profile complete',
        'first-connection' => 'First connection',
        'helping-hand' => 'Helping hand',
    ];

    /** The groups the Badges screen is organised into, in order. */
    public static function groups(): array
    {
        return [
            'founding' => [
                'title' => 'Founding and milestones',
                'blurb' => 'How long you have been part of Frith.',
            ],
            'connections' => [
                'title' => 'Connections',
                'blurb' => 'What has happened between you and other families.',
            ],
            'contribution' => [
                'title' => 'Contribution',
                'blurb' => 'Things you choose to tell other families about yourself.',
            ],
        ];
    }

    public static function exists(string $slug): bool
    {
        return array_key_exists($slug, self::TITLES);
    }

    /**
     * What another family sees. A slug and a title, and nothing more.
     *
     * @return Collection<int, array{slug: string, title: string}>
     */
    public static function visible(Registration $family): Collection
    {
        $hidden = (array) ($family->hidden_badges ?? []);

        return collect(self::held($family))
            ->filter(fn (bool $held, string $slug) => $held && ! in_array($slug, $hidden, true))
            ->map(fn (bool $held, string $slug) => ['slug' => $slug, 'title' => self::TITLES[$slug]])
            ->values();
    }

    /**
     * Which badges a family has, as cheaply as the facts allow.
     *
     * @return array<string, bool>
     */
    public static function held(Registration $family): array
    {
        return [
            'first-frith-family' => $family->isFounder(),
            'one-year' => $family->created_at?->copy()->addYear()->isPast() ?? false,
            'profile-complete' => ProfileSections::for($family)->every(fn (array $section) => $section['complete']),
            'first-connection' => Connection::query()
                ->for($family)
                ->where('status', Connection::ACCEPTED)
                ->exists(),
            'helping-hand' => (bool) $family->helping_hand,
        ];
    }

    /**
     * Every badge, held or not, for the family's own Badges screen.
     *
     * The not-yet ones are shown with what would bring them about, because
     * the screen's job is to explain the set, not to set homework.
     *
     * @return Collection<int, array{
     *     slug: string, group: string, title: string, what: string,
     *     note: string, held: bool, hidden: bool, choice: bool
     * }>
     */
    public static function all(Registration $family): Collection
    {
        $hidden = (array) ($family->hidden_badges ?? []);
        $held = self::held($family);

        $joined = $family->created_at;
        $anniversary = $joined?->copy()->addYear();
        $missing = ProfileSections::for($family)->firstWhere('complete', false);

        $first = Connection::query()
            ->for($family)
            ->where('status', Connection::ACCEPTED)
            ->orderBy('responded_at')
            ->first();

        $badges = [
            [
                'slug' => 'first-frith-family',
                'group' => 'founding',
                'what' => 'You registered before Frith opened. Frith+ is free for your family, for good.',
                'note' => 'Only families who joined before launch can have this. It will never be awarded again.',
            ],
            [
                'slug' => 'one-year',
                'group' => 'founding',
                'what' => 'A year since you joined. Then three, five and ten.',
                'note' => match (true) {
                    $joined === null => 'A year after you joined.',
                    $anniversary->isPast() => 'You joined in '.$joined->format('F Y').'.',
                    default => 'You joined in '.$joined->format('F Y')
                        .', so this arrives in '.$anniversary->format('F Y').'.',
                },
            ],
            [
                'slug' => 'profile-complete',
                'group' => 'founding',
                'what' => 'Every section of your profile filled in, so your matches are as good as they can be.',
                'note' => $missing === null
                    ? 'Every section is filled in.'
                    : 'One section left: '.lcfirst($missing['title']).'.',
            ],
            [
                'slug' => 'first-connection',
                'group' => 'connections',
                'what' => 'You and another family both said yes.',
                // Theirs to read and nobody else's. See the class comment.
                'note' => $first === null
                    ? 'When you and another family both say yes.'
                    : $first->otherThan($family)->displayName()
                        .', in '.($first->responded_at?->format('F Y') ?? 'the past').'.',
            ],
            [
                'slug' => 'helping-hand',
                'group' => 'contribution',
                'what' => 'You are happy for families to ask you for advice, whether or not you have connected.',
                'note' => 'You choose this. Turn it off at any time, with no explanation needed, and nobody is told.',
            ],
        ];

        return collect($badges)->map(fn (array $badge) => [
            ...$badge,
            'title' => self::TITLES[$badge['slug']],
            'held' => $held[$badge['slug']],
            'hidden' => in_array($badge['slug'], $hidden, true),
            'choice' => $badge['slug'] === 'helping-hand',
        ]);
    }
}
