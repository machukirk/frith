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
 * everybody else. That is almost everybody, because registration never asks
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
        // Two buttons, one form, one email field. The link used to live in a
        // form of its own carrying a hidden old('email'), which is empty until
        // something has already failed, so the first press always posted a
        // blank address and the emailed link, the front door of this design,
        // never worked first time.
        if ($request->input('action') === 'link') {
            return $this->sendLink($request);
        }

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
                'password' => 'You have not set a password. Ask us to email you a link instead. The button is just below.',
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

    /**
     * The account for an address, if there is still one to find.
     *
     * present() matters: a family who has pressed "Delete my account" is gone
     * from every screen that shows one family to another, and their password
     * and login token survive until the purge runs a month later. Without this
     * they could sign straight back in and carry on using the site, which is
     * not what the leaving screen told them would happen.
     */
    private function find(string $email): ?Registration
    {
        return Registration::query()
            ->present()
            ->where('email', Registration::normaliseEmail($email))
            ->first();
    }
}
