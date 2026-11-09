<?php

namespace App\Support;

use App\Models\Connection;
use App\Models\Registration;
use Illuminate\Support\Collection;

/**
 * Who a family is shown, and why they are allowed to be shown.
 *
 * The visibility rule, in one place, because it is a promise rather than a
 * feature. The Journeys screen tells a family: "Joining one makes your
 * profile visible to the others in it — and nowhere else." So the pool of
 * families anybody can be suggested is exactly the families they share a
 * Journey with. Not everybody nearby, not everybody with similar answers.
 *
 * That is why joining is a POST with a sentence above it rather than a
 * filter: it is the moment a family chooses to be visible, and it is the only
 * one.
 *
 * Note for later: the hi-fi has an empty state reading "No families within 5
 * miles yet. Widen your search to 15 miles." A radius search over everybody
 * registered would break the sentence above, so it is not built. If it is
 * wanted, it needs its own consent at registration, not a slider here.
 */
class Suggestions
{
    /**
     * Families this one may be shown, best first.
     *
     * @return Collection<int, FamilyCard>
     */
    public static function for(Registration $family, int $limit = 6): Collection
    {
        $family->loadMissing(['children', 'experiences', 'journeys']);

        $journeyIds = $family->journeys->pluck('id');

        if ($journeyIds->isEmpty()) {
            return collect();
        }

        $hidden = $family->separatedIds();
        $hidden[] = $family->id;

        // Anybody already asked, or who has already asked, is not a
        // suggestion — including a declined one. Somebody who said no once
        // should not reappear on the first screen a family opens.
        $spokenFor = Connection::query()
            ->for($family)
            ->get()
            ->map(fn (Connection $c) => $c->otherThan($family)->id);

        return Registration::query()
            ->whereHas('journeys', fn ($query) => $query->whereIn('journeys.id', $journeyIds))
            ->whereNotIn('id', array_merge($hidden, $spokenFor->all()))
            ->present()
            ->whereNotNull('display_name')
            ->with(['children', 'experiences'])
            ->get()
            ->map(fn (Registration $other) => FamilyCard::make($family, $other))
            ->sortByDesc(fn (FamilyCard $card) => [$card->score, $card->inCommon])
            ->take($limit)
            ->values();
    }
}
