<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Support\FamilyCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Journeys a family can join.
 *
 * Joining is the one action on this screen, and it is not a small one: it is
 * what makes a family's profile visible to the others in that Journey. So it
 * is a POST, it is reversible from the same screen, and the page says what
 * it does before anybody presses it.
 */
class JourneyController extends Controller
{
    public function index(Request $request): View
    {
        $family = $request->user('founder');
        $search = trim((string) $request->query('q'));

        $journeys = Journey::query()
            ->offered()
            ->withCount('members')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->get();

        return view('pages.journeys.index', [
            'journeys' => $journeys,
            'joined' => $family->journeys->pluck('id')->all(),
            'search' => $search,
            'total' => Journey::query()->offered()->count(),
        ]);
    }

    /**
     * Who else is in here, most in common first.
     *
     * Only visible to a family who is in it themselves: joining is what makes
     * you visible to the others, so it is also what lets you see them.
     */
    public function show(Request $request, Journey $journey): View
    {
        $family = $request->user('founder')->loadMissing(['children', 'experiences']);

        abort_unless($family->journeys->contains($journey), 403);

        $hidden = $family->separatedIds();
        $hidden[] = $family->id;

        $cards = $journey->members()
            ->with(['children', 'experiences'])
            ->whereNotIn('registrations.id', $hidden)
            ->present()
            ->get()
            ->map(fn ($other) => FamilyCard::make($family, $other))
            ->sortByDesc(fn (FamilyCard $card) => [$card->score, $card->inCommon])
            ->values();

        return view('pages.journeys.show', [
            'journey' => $journey,
            'cards' => $cards,
            'memberCount' => $journey->members()->count(),
        ]);
    }

    public function join(Request $request, Journey $journey): RedirectResponse
    {
        abort_if($journey->isArchived(), 404);

        // syncWithoutDetaching rather than attach: pressing Join twice, or on
        // a connection that retried, must not make a second membership.
        $request->user('founder')->journeys()->syncWithoutDetaching([
            $journey->id => ['joined_at' => now()],
        ]);

        return back()->with('status', 'You are in '.$journey->title.'.');
    }

    public function leave(Request $request, Journey $journey): RedirectResponse
    {
        $request->user('founder')->journeys()->detach($journey->id);

        return back()->with('status', 'You have left '.$journey->title.'.');
    }
}
