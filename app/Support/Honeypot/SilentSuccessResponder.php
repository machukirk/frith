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
 * The trade is that a false positive sends a real person back to the first
 * screen with nothing to explain it. The timing check is a full second, which
 * no human types faster than, so it should stay rare.
 */
class SilentSuccessResponder implements SpamResponder
{
    public function respond(Request $request, Closure $next)
    {
        return redirect()->route('register.start');
    }
}
