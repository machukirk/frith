<?php

namespace App\Http\Controllers;

use App\Support\Badges;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The badges screen.
 *
 * Two things a family can change here, and nothing else: whether they are
 * happy to be asked for advice, and which of their badges show on their
 * profile. There is nothing to claim and nothing to collect — see
 * App\Support\Badges for why that is deliberate.
 */
class AccountBadgeController extends Controller
{
    public function show(Request $request): View
    {
        $family = $request->user('founder');

        return view('pages.account.badges', [
            'family' => $family,
            'groups' => Badges::groups(),
            'badges' => Badges::all($family)->groupBy('group'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $family = $request->user('founder');

        // The form posts the badges a family wants shown. Anything held and
        // not posted is hidden, which is the right way round: a checkbox
        // nobody touches should not change what other families see.
        $shown = array_values(array_filter(
            (array) $request->input('shown', []),
            fn ($slug) => is_string($slug) && Badges::exists($slug),
        ));

        $hidden = Badges::all($family)
            ->filter(fn (array $badge) => $badge['held'] && ! in_array($badge['slug'], $shown, true))
            ->pluck('slug')
            ->values()
            ->all();

        $family->forceFill([
            'hidden_badges' => $hidden,
            'helping_hand' => $request->boolean('helping_hand'),
        ])->save();

        return redirect()
            ->route('account.badges')
            ->with('status', 'Saved. Nobody is told what you show or hide.');
    }
}
