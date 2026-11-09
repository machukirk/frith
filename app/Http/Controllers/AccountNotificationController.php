<?php

namespace App\Http\Controllers;

use App\Support\NotificationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Which emails a family wants.
 *
 * The screen a family on the free plan sees is mostly an explanation: Frith
 * is quiet unless you pay, and the honest thing is to say which emails they
 * are not getting and where those requests will be waiting instead.
 */
class AccountNotificationController extends Controller
{
    public function show(Request $request): View
    {
        $family = $request->user('founder');

        return view('pages.account.notifications', [
            'family' => $family,
            'switches' => NotificationSettings::for($family),
            'paces' => NotificationSettings::paces(),
            'hasPlus' => $family->hasPlus(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $family = $request->user('founder');

        $request->validate([
            'pace' => ['nullable', Rule::in(array_keys(NotificationSettings::paces()))],
        ]);

        // Only the switches this family is allowed to change are read. A
        // locked one is rendered so the screen is honest about what it does,
        // and then ignored, so posting the form by hand cannot turn on an
        // email that belongs to a membership somebody does not have.
        $stored = (array) ($family->notification_prefs ?? []);

        foreach (NotificationSettings::editable($family) as $key) {
            $stored[$key] = $request->boolean('prefs.'.$key);
        }

        $changes = [
            'notification_prefs' => $stored,
            'quiet_hours' => $request->boolean('quiet_hours'),
        ];

        // Pace only means anything with Frith+, because on the free plan
        // requests are not emailed at all and so there is no pace to set.
        // Left untouched otherwise rather than written back, so a membership
        // that lapses and returns finds the same answer waiting.
        if ($family->hasPlus() && $request->filled('pace')) {
            $changes['request_pace'] = $request->input('pace');
        }

        $family->forceFill($changes)->save();

        return redirect()
            ->route('account.notifications')
            ->with('status', 'Saved. Changes take effect with the next email.');
    }
}
