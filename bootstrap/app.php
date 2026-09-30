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

        // There is no site-wide login yet, so the framework's default redirect
        // target does not exist and an auth-protected route throws rather than
        // sending anybody anywhere. The admin panel's own login is where they
        // should end up.
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
