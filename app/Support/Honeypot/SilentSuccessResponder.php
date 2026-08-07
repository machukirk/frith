<?php

namespace App\Support\Honeypot;

use Closure;
use Illuminate\Http\Request;
use Spatie\Honeypot\SpamResponder\SpamResponder;

/**
 * Answers spam with the same page a real person gets.
 *
 * Two reasons. A bot learns nothing about whether it was caught, so it has no
 * signal to tune against. And on the rarer occasion the honeypot fires on a
 * real person — a password manager that fills every field it can see, an
 * accessibility tool that submits faster than a human could — they get a normal
 * outcome instead of the blank page the package ships by default.
 *
 * The trade is that a false positive is silent: they think they signed up and
 * no email arrives. The success copy tells people to write to us if nothing
 * turns up, which is the recovery path.
 */
class SilentSuccessResponder implements SpamResponder
{
    public function respond(Request $request, Closure $next)
    {
        return redirect()
            ->route('coming-soon')
            ->with('waitlist.status', 'pending-confirmation');
    }
}
