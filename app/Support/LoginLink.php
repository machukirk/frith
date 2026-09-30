<?php

namespace App\Support;

use App\Models\Registration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The emailed way in.
 *
 * The design leads with this rather than a password, and says why: a parent
 * registering at 1am on a phone will not remember a password a fortnight
 * later. So the link is the front door and a password is optional.
 *
 * One use, one hour. Stored hashed, because a readable token in the database
 * is a password for whoever can read it — and this database holds what
 * families have told us about their children.
 */
class LoginLink
{
    public const LIFETIME_MINUTES = 60;

    /** Returns the URL to email. The plain token exists only in this method. */
    public static function issue(Registration $registration): string
    {
        $token = Str::random(48);

        $registration->forceFill([
            'login_token' => hash('sha256', $token),
            'login_token_expires_at' => now()->addMinutes(self::LIFETIME_MINUTES),
        ])->save();

        return route('login.link', [
            'registration' => $registration->public_id,
            'token' => $token,
        ]);
    }

    /**
     * The registration this token belongs to, if it is still good for one use.
     *
     * Compared in constant time against the stored hash, so the time a
     * comparison takes says nothing about how much of the token was right.
     */
    public static function claim(string $publicId, string $token): ?Registration
    {
        $registration = Registration::query()->where('public_id', $publicId)->first();

        if (! $registration || blank($registration->login_token)) {
            return null;
        }

        if ($registration->login_token_expires_at?->isPast() ?? true) {
            return null;
        }

        if (! hash_equals($registration->login_token, hash('sha256', $token))) {
            return null;
        }

        // Spent. A link that works twice is a link somebody forwarded.
        $registration->forceFill([
            'login_token' => null,
            'login_token_expires_at' => null,
        ])->save();

        return $registration;
    }
}
