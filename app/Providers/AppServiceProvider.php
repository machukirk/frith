<?php

namespace App\Providers;

use App\Support\SearchVisibility;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Signed confirm/unsubscribe links are generated in a queue worker,
        // which has no request to infer the scheme from. Without this they come
        // out as http:// and the signature breaks on redirect to https.
        //
        // Keyed on the configured URL rather than on the environment name: the
        // staging site is served over https too, and while this said
        // "production" every signed link a staging worker produced was broken.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->catchMailOnCopies();
        $this->configureRateLimiting();
    }

    /**
     * On anything that is not the real Frith, send every email to one address.
     *
     * A copy of the site runs against a copy of the database, which is full of
     * real families' addresses. Point it at a real mail service with no guard
     * and the first test registration emails somebody who never asked to hear
     * from a staging server.
     *
     * So: outside production, if FRITH_MAIL_CATCH is set, every message goes
     * there instead, whoever it was addressed to. That makes a real mailer safe
     * to use on staging, which is the only way to see what an email actually
     * looks like when it lands.
     */
    private function catchMailOnCopies(): void
    {
        $catch = config('frith.mail.catch');

        if ($catch && ! SearchVisibility::isRealSite()) {
            Mail::alwaysTo($catch);
        }
    }

    private function configureRateLimiting(): void
    {
        // Registration is many steps, so the limit is per-step generous while
        // still being far too slow to script. Keyed by IP like the waiting list.
        RateLimiter::for('registration', function (Request $request) {
            return Limit::perHour(60)->by($request->ip())->response(
                fn () => back()
                    ->withInput()
                    ->withErrors(['email' => 'That is a lot of tries in a short space of time. Give it an hour, or write to us at '.config('frith.company.contact_email').' and we will sort it out.'])
            );
        });
    }
}
