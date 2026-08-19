<?php

namespace App\Http\Controllers;

use App\Jobs\SyncSignupToMailerLite;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * What the links in the Founder email do.
 *
 * Every route here is signed, so the link itself is the proof of identity —
 * there is no session, and somebody guessing a URL gets a 403 rather than
 * somebody else's registration.
 */
class RegistrationEmailController extends Controller
{
    /**
     * Confirms the address is theirs, which is what puts them on the list the
     * launch email will be sent from.
     */
    public function verify(Registration $registration): View
    {
        if ($registration->email_verified_at === null) {
            $registration->forceFill([
                'email_verified_at' => now(),
                // Clicking the link is a clear signal they want to hear from
                // us, so it also undoes an unsubscribe.
                'unsubscribed_at' => null,
            ])->save();
        }

        SyncSignupToMailerLite::dispatch($registration);

        return view('register.verified', ['registration' => $registration]);
    }

    public function unsubscribe(Registration $registration): View
    {
        if ($registration->unsubscribed_at === null) {
            $registration->forceFill(['unsubscribed_at' => now()])->save();

            SyncSignupToMailerLite::dispatch($registration, subscribed: false);
        }

        return view('register.unsubscribed', [
            'registration' => $registration,
            'resubscribeUrl' => URL::signedRoute('register.resubscribe', ['registration' => $registration->public_id]),
        ]);
    }

    /**
     * Undo, offered on the unsubscribed page.
     *
     * Corporate mail filters follow every link in a message, so somebody can be
     * unsubscribed without ever tapping it. Rather than make leaving harder to
     * guard against that, leaving is one tap and coming back is one tap.
     */
    public function resubscribe(Registration $registration): RedirectResponse
    {
        $registration->forceFill(['unsubscribed_at' => null])->save();

        SyncSignupToMailerLite::dispatch($registration);

        return redirect()->route('register.verified.done');
    }
}
