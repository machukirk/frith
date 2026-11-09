<?php

namespace App\Http\Controllers;

use App\Support\Taxonomy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Does this still sound like your family?"
 *
 * Asked again every few months, because a diagnosis arrives, a placement
 * settles, a new worry starts — and what a family is navigating is the single
 * thing matching leans on hardest. A profile that quietly goes out of date
 * produces confident, wrong suggestions.
 *
 * The two answers are weighted the same on purpose. "Yes, this is still us"
 * and "not now" both buy the same quiet. If ignoring the question were the
 * cheaper answer the question would become a nag, and the screen promises it
 * will not be one: "nothing changes if you ignore them".
 */
class AccountExperienceController extends Controller
{
    public function review(Request $request): View
    {
        $family = $request->user('founder')->loadMissing('experiences');

        return view('pages.account.experiences', [
            'family' => $family,
            'areas' => collect($family->orderedSupportAreas())
                ->map(fn (string $area) => [
                    'slug' => $area,
                    'label' => Taxonomy::categoryLabel($area),
                    'items' => $family->experiences
                        ->where('category', $area)
                        ->map(fn ($e) => Taxonomy::itemLabel($e->category, $e->item))
                        ->values()
                        ->all(),
                ]),
            'lastTouched' => $family->experiencesLastTouched(),
        ]);
    }

    /** Confirmed as still true, which is as good as having re-entered it. */
    public function confirm(Request $request): RedirectResponse
    {
        $request->user('founder')->forceFill([
            'experiences_reviewed_at' => now(),
            'experiences_snoozed_at' => null,
        ])->save();

        return redirect()
            ->route('account')
            ->with('status', 'Thank you. We will ask again in about '.config('frith.review.months').' months.');
    }

    /** Not now. Quiet for the same few months, and not counted against them. */
    public function snooze(Request $request): RedirectResponse
    {
        $request->user('founder')->forceFill(['experiences_snoozed_at' => now()])->save();

        return redirect()->route('village');
    }
}
