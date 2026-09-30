<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Staging, a preview, anybody's copy: not the real site, and it says
        // so to crawlers on every response. See App\Support\SearchVisibility.
        $middleware->append(\App\Http\Middleware\KeepCopiesOutOfSearch::class);

        // One-click unsubscribe is a POST from Gmail or Outlook, which has no
        // session and therefore no CSRF token. The signature on the URL is what
        // authenticates it, so dropping the token check costs nothing.
        $middleware->validateCsrfTokens(except: [
            'join/unsubscribe/*',
        ]);

        // Behind Cloudways' load balancer the client IP arrives in a forwarded
        // header. Without this, rate limiting and consent evidence would record
        // the proxy for every visitor.
        $middleware->trustProxies(at: '*');

        // Two guards, two front doors. A family who is not logged in belongs
        // at the site's log in page; somebody reaching for the admin panel
        // belongs at its own. Sending a parent to the staff entrance would be
        // both confusing and a small lie about what Frith is.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('filament.admin.auth.login')
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
