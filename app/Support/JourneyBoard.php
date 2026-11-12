<?php

namespace App\Support;

use App\Models\Journey;
use Illuminate\Support\Collection;

/**
 * The Journeys as the home page shows them, with real numbers.
 *
 * This used to be nine titles and nine counts typed into config/content/home,
 * which meant the front page of the site quietly disagreed with the product
 * behind it. A Journey renamed in the admin kept its old name here; a Journey
 * archived still appeared; and "7 families" was a number somebody invented
 * once and nobody could ever correct, because it was copy rather than data.
 *
 * Counts are a deliberate thing to show publicly. They are aggregate, they
 * are the one piece of evidence a visitor has that anybody is here at all,
 * and they are the same number the family sees inside. If that number is
 * small at launch, the honest answer is that it is small.
 */
class JourneyBoard
{
    /**
     * @return Collection<int, array{
     *     title: string, slug: string, families: int, url: string
     * }>
     */
    public static function forHome(): Collection
    {
        $family = auth('founder')->user();
        $joined = $family?->journeys->pluck('id')->all() ?? [];

        return Journey::query()
            ->offered()
            ->withCount('members')
            ->get()
            ->map(fn (Journey $journey) => [
                'title' => $journey->title,
                'slug' => $journey->slug,
                'families' => $journey->members_count,
                'url' => self::urlFor($journey, $family !== null, in_array($journey->id, $joined, true)),
            ]);
    }

    public static function count(): int
    {
        return Journey::query()->offered()->count();
    }

    /**
     * Where a card leads, which depends on who is reading it.
     *
     * /journeys is behind the founder guard, so sending a visitor there from
     * the public home page bounces them to a login screen they have no
     * account for. That is a dead end on the one page meant to persuade them,
     * so a visitor goes to registration instead.
     *
     * A family already in a Journey goes straight into it. One who is not
     * goes to the directory, where joining is a deliberate act with a
     * sentence above it, rather than something that happens by following a
     * link from the front page.
     */
    private static function urlFor(Journey $journey, bool $loggedIn, bool $isMember): string
    {
        return match (true) {
            ! $loggedIn => route('register.start'),
            $isMember => route('journeys.show', $journey),
            default => route('journeys'),
        };
    }
}
