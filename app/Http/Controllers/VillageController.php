<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Journey;
use App\Support\Suggestions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The village: what a family sees once they are in.
 *
 * Registration never asks what other families should call them, because it is
 * no use until there are other families to be called it by. So the village
 * asks once, the first time it is opened, and waits until it has an answer.
 *
 * Five blocks, in the order the design puts them, and the order is the
 * argument: the journeys they chose, then anything waiting on them, then the
 * families they already have, then who else is there. What somebody owes a
 * reply to comes before anything new to look at.
 */
class VillageController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $family = $request->user('founder');

        if (! $family->hasProfile()) {
            return redirect()->route('village.setup');
        }

        $family->loadMissing(['children', 'experiences']);

        $joined = $family->journeys()
            ->offered()
            ->withCount('members')
            ->orderBy('position')
            ->get();

        $hidden = $family->separatedIds();

        $connections = Connection::query()
            ->for($family)
            ->with(['requester', 'addressee', 'messages'])
            ->get()
            ->reject(fn (Connection $c) => in_array($c->otherThan($family)->id, $hidden, true)
                || $c->otherThan($family)->hasLeft());

        return view('pages.village.show', [
            'family' => $family,
            'joined' => $joined,
            'rest' => Journey::query()
                ->offered()
                ->whereNotIn('id', $joined->pluck('id'))
                ->get(),

            // The two numbers on the requests card, which are different
            // things: what is waiting on this family, and what they are
            // waiting on. Only the first is anybody's to act on.
            'waitingOnYou' => $connections
                ->filter(fn (Connection $c) => $c->addressee_id === $family->id && $c->status === Connection::PENDING)
                ->count(),
            'waitingOnThem' => $connections
                ->filter(fn (Connection $c) => $c->requester_id === $family->id && $c->status === Connection::PENDING)
                ->count(),

            'connected' => $connections
                ->filter(fn (Connection $c) => $c->isAccepted())
                ->sortByDesc(fn (Connection $c) => $c->messages->last()?->created_at ?? $c->responded_at)
                ->values(),

            'suggested' => Suggestions::for($family),

            // Asked at most every few months, and saying "not now" is a real
            // answer rather than a way of putting it off.
            'review' => $family->needsExperienceReview(),
        ]);
    }

    public function setup(Request $request): View|RedirectResponse
    {
        $family = $request->user('founder');

        if ($family->hasProfile()) {
            return redirect()->route('village');
        }

        return view('pages.village.setup', ['family' => $family]);
    }

    public function store(Request $request): RedirectResponse
    {
        $family = $request->user('founder');

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:60'],
        ], [
            'display_name.required' => 'We need something to call you. Whatever your family goes by is fine.',
            'display_name.max' => 'That is a little long. Sixty characters or fewer, please.',
        ]);

        $family->forceFill(['display_name' => trim($data['display_name'])])->save();

        return redirect()->route('village');
    }
}
