<?php

namespace App\Http\Controllers;

use App\Mail\LoginLink as LoginLinkMail;
use App\Models\Registration;
use App\Support\LoginLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Getting back in.
 *
 * Two ways: a password, for anybody who has set one, and an emailed link for
 * everybody else — which is almost everybody, because registration never asks
 * for a password. The design leads with the link and says why.
 *
 * Neither path ever says whether an address is registered. Being on a list of
 * families raising children with SEND is itself sensitive, so "we have sent
 * you a link" is the answer whether or not there was anybody to send it to.
 */
class LoginController extends Controller
{
    public function show(): View
    {
        return view('pages.auth.login');
    }

    public function attempt(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'We need the email address you registered with.',
            'password.required' => 'Enter your password, or ask us to email you a link instead.',
        ]);

        $registration = $this->find($data['email']);

        // Somebody who never set a password is not somebody with a wrong
        // password. Telling them to look for one they do not have is how an
        // evening gets wasted.
        if ($registration && ! $registration->hasPassword()) {
            throw ValidationException::withMessages([
                'password' => 'You have not set a password. Ask us to email you a link instead — the button is just below.',
            ]);
        }

        if (! $registration || ! Hash::check($data['password'], $registration->password)) {
            throw ValidationException::withMessages([
                'email' => 'That email address and password do not go together.',
            ]);
        }

        Auth::guard('founder')->login($registration, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('verified'));
    }

    /** Emails a link. Says the same thing whether or not anybody was found. */
    public function sendLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
        ], [
            'email.required' => 'We need the email address you registered with.',
            'email.email' => 'That email does not look right. It should look like name@example.com.',
        ]);

        $registration = $this->find($data['email']);

        if ($registration) {
            Mail::to($registration->email)->send(
                new LoginLinkMail($registration, LoginLink::issue($registration)),
            );
        }

        return redirect()
            ->route('link-sent')
            // Only to print it back on the next screen. It is their own address.
            ->with('link-sent.email', $data['email']);
    }

    public function consume(Request $request, string $registration, string $token): RedirectResponse
    {
        $found = LoginLink::claim($registration, $token);

        if (! $found) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'That link has been used already, or it has expired. Ask for another and we will send one.']);
        }

        // Following the link proves they read the inbox, which is the same
        // thing verifying proves. Somebody arriving this way is verified.
        if ($found->email_verified_at === null) {
            $found->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::guard('founder')->login($found, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('verified'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('founder')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function find(string $email): ?Registration
    {
        return Registration::query()
            ->where('email', Registration::normaliseEmail($email))
            ->first();
    }
}
