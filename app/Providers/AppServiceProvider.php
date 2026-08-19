<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // Generous enough that a family behind a shared school or mobile
        // network IP won't hit it, tight enough that the endpoint is not a
        // usable way to send mail to strangers.
        RateLimiter::for('waitlist', function (Request $request) {
            return Limit::perHour(10)->by($request->ip())->response(
                fn () => back()
                    ->withInput()
                    ->withErrors(['email' => 'That’s a few tries in a short space of time. Give it an hour, or write to us at '.config('frith.company.contact_email').' and we’ll add you ourselves.'])
            );
        });

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
