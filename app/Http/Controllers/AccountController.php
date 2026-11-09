<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Support\AnswerSheet;
use App\Support\Badges;
use App\Support\ProfileSections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * A family's own account: their profile, and changing it.
 *
 * The profile screen is a list of the six questions registration asked, with
 * what they answered and a way back in. Editing reuses the registration
 * screens themselves rather than a second set of forms, so a family changing
 * their mind about something sees the same words, the same options and the
 * same help text they saw the first time.
 */
class AccountController extends Controller
{
    /**
     * The screens a family can re-open from their profile.
     *
     * The same six sections registration asked about, but the first one is
     * the account's own: a display name and an area, rather than a first
     * name, an email and an area. The other five are the wizard's views,
     * rendered unchanged.
     *
     * @var array<int, string>
     */
    private const EDITABLE = ['name', 'family', 'hopes', 'areas', 'experiences', 'interests'];

    public function show(Request $request): View
    {
        $family = $request->user('founder');

        return view('pages.account.profile', [
            'family' => $family,
            'sections' => ProfileSections::for($family),
            'tally' => ProfileSections::tally($family),
            'badges' => Badges::visible($family),
        ]);
    }

    /**
     * One registration screen, re-opened from the account.
     *
     * The same Blade view the wizard renders. It is given the account's own
     * shell instead of the wizard's: no step counter, because this is not a
     * journey through six screens, and a Cancel that goes back to the list.
     */
    public function edit(Request $request, string $step): View
    {
        abort_unless(in_array($step, self::EDITABLE, true), 404);

        $family = $request->user('founder')->loadMissing(['children', 'experiences']);

        return view($step === 'name' ? 'pages.account.name' : "register.{$step}", [
            'registration' => $family,
            'step' => $step,
            'editing' => true,
            'children' => $this->childRows($request, $family),
            // The wizard's shell reads these. In an edit there is no step
            // number to show and nowhere to go back to but the list.
            'stepNumber' => null,
            'totalSteps' => null,
            'previous' => route('account'),
        ]);
    }

    public function update(Request $request, string $step): RedirectResponse
    {
        abort_unless(in_array($step, self::EDITABLE, true), 404);

        $family = $request->user('founder')->loadMissing(['children', 'experiences']);

        // "Add another child" and "Remove" are submits, so the screen works
        // without JavaScript. They re-render rather than saving.
        if ($step === 'family' && $request->input('action') !== 'continue') {
            return $this->adjustChildRows($request);
        }

        // Taking every area off the form means there is nothing left to ask
        // about underneath them, so the statements go too.
        if ($step === 'areas' && $request->input('action') === 'skip') {
            AnswerSheet::clearAreas($family);

            return redirect()->route('account')->with('status', 'Saved. Nothing is recorded about areas of family life.');
        }

        $data = Validator::make($request->all(), ...AnswerSheet::rules($step))->validate();

        AnswerSheet::apply($family, $step, $data);

        // Changing what you are navigating is an answer to the review, so the
        // nudge goes quiet for another few months without being asked twice.
        if (in_array($step, ['areas', 'experiences'], true)) {
            $family->forceFill(['experiences_reviewed_at' => now(), 'experiences_snoozed_at' => null])->save();
        }

        return redirect()->route('account')->with('status', 'Saved. Your profile is up to date.');
    }

    /**
     * The rows to render: whatever is mid-edit in the session, else what is
     * saved, else one empty row to start from.
     *
     * @return array<int, array{birth_month: string|int, birth_year: string|int}>
     */
    private function childRows(Request $request, Registration $family): array
    {
        if ($request->session()->has('account.child_rows')) {
            return $request->session()->pull('account.child_rows');
        }

        if ($family->children->isNotEmpty()) {
            return $family->children
                ->map(fn ($c) => ['birth_month' => $c->birth_month, 'birth_year' => $c->birth_year])
                ->all();
        }

        return [['birth_month' => '', 'birth_year' => '']];
    }

    private function adjustChildRows(Request $request): RedirectResponse
    {
        $rows = array_values($request->input('children', []));

        if ($request->input('action') === 'add') {
            $rows[] = ['birth_month' => '', 'birth_year' => ''];
        }

        if (str_starts_with((string) $request->input('action'), 'remove:')) {
            unset($rows[(int) explode(':', $request->input('action'))[1]]);
            $rows = array_values($rows);
        }

        $request->session()->put('account.child_rows', $rows);
        $request->session()->flash('registration.family_structures', $request->input('family_structures', []));

        return redirect()->route('account.edit', 'family');
    }
}
