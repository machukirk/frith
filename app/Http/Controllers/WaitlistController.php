<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWaitlistSignupRequest;
use App\Mail\AlreadyOnTheList;
use App\Mail\ConfirmWaitlistSignup;
use App\Models\WaitlistSignup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class WaitlistController extends Controller
{
    public function show(): View
    {
        return view('coming-soon');
    }

    /**
     * Take a signup.
     *
     * Every path through this method returns the same thing. Whether the
     * address is new, already waiting, or already confirmed, the browser sees
     * one response — because on a waitlist for parents of disabled children,
     * "is this address on the list?" is itself sensitive. An endpoint that
     * answers differently for a known address answers that question for anyone
     * who asks. What differs is which email we send, and only the person
     * holding the mailbox ever learns that.
     */
    public function store(StoreWaitlistSignupRequest $request): RedirectResponse
    {
        $email = WaitlistSignup::normaliseEmail($request->validated('email'));

        $signup = WaitlistSignup::firstOrNew(['email' => $email]);

        if (! $signup->exists) {
            $signup->fill($this->consentEvidence($request));
            $signup->save();

            $this->sendConfirmation($signup);

            return $this->done();
        }

        // Coming back after leaving is fresh consent, so it gets recorded as
        // such: new timestamp, new wording, and the confirmation runs again.
        if ($signup->hasUnsubscribed()) {
            $signup->fill($this->consentEvidence($request));
            $signup->unsubscribed_at = null;
            $signup->confirmed_at = null;
            $signup->save();

            $this->sendConfirmation($signup);

            return $this->done();
        }

        if (! $signup->canSendMail()) {
            return $this->done();
        }

        // Already confirmed: tell them so, in the mailbox rather than the page.
        // Otherwise they'd be told to check their email and nothing would come.
        Mail::to($signup->email)->queue(
            $signup->isConfirmed() ? new AlreadyOnTheList($signup) : new ConfirmWaitlistSignup($signup)
        );

        $signup->forceFill(['confirmation_sent_at' => now()])->save();

        return $this->done();
    }

    public function confirm(WaitlistSignup $signup): View
    {
        // The signed middleware has already proved the link is ours and unexpired.
        if (! $signup->isConfirmed()) {
            $signup->forceFill([
                'confirmed_at' => now(),
                'unsubscribed_at' => null,
            ])->save();
        }

        return view('waitlist.confirmed');
    }

    public function unsubscribe(WaitlistSignup $signup): View
    {
        if (! $signup->hasUnsubscribed()) {
            $signup->forceFill(['unsubscribed_at' => now()])->save();
        }

        return view('waitlist.unsubscribed', [
            'resubscribeUrl' => URL::signedRoute('waitlist.resubscribe', ['signup' => $signup->public_id]),
        ]);
    }

    /**
     * Undo, offered on the unsubscribed page.
     *
     * Corporate mail filters and link scanners follow every URL in a message,
     * so a GET unsubscribe can fire without the person ever tapping it. Rather
     * than make leaving harder to guard against that, leaving stays one tap and
     * coming back is one tap too.
     */
    public function resubscribe(WaitlistSignup $signup): RedirectResponse
    {
        $signup->forceFill([
            'unsubscribed_at' => null,
            'confirmed_at' => $signup->confirmed_at ?? now(),
        ])->save();

        return redirect()
            ->route('coming-soon')
            ->with('waitlist.status', 'confirmed');
    }

    /**
     * @return array<string, mixed>
     */
    private function consentEvidence(Request $request): array
    {
        return [
            'source' => 'coming-soon',
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
            'consent_ip' => $request->ip(),
            'consent_user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ];
    }

    private function sendConfirmation(WaitlistSignup $signup): void
    {
        Mail::to($signup->email)->queue(new ConfirmWaitlistSignup($signup));

        $signup->forceFill(['confirmation_sent_at' => now()])->save();
    }

    private function done(): RedirectResponse
    {
        return redirect()
            ->route('coming-soon')
            ->with('waitlist.status', 'pending-confirmation');
    }
}
