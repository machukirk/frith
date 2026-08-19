<?php

use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RegistrationExperienceController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WebManifestController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Spatie\Honeypot\ProtectAgainstSpam;

// The view reads its own content through App\Support\PageContent, so there is
// nothing for a controller to do here.
Route::view('/', 'coming-soon')->name('coming-soon');

/*
 * The sitemap and manifest are public, identical for every visitor, and have no
 * form on them. Left in the web group they start a session and set cookies,
 * and Varnish will not cache a response carrying Set-Cookie — so they opt out.
 */
Route::withoutMiddleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
])->group(function () {
    Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
    Route::get('/site.webmanifest', WebManifestController::class)->name('manifest');
});

/*
 * Frith Founders registration. Every step is a real POST that saves before it
 * redirects, so a visitor who answers one screen and then puts the phone down
 * is still registered.
 */
Route::prefix('join')->name('register.')->group(function () {
    Route::get('/', [RegistrationController::class, 'start'])->name('start');

    Route::get('/experiences', [RegistrationExperienceController::class, 'start'])->name('experiences');
    Route::get('/experiences/{category}', [RegistrationExperienceController::class, 'show'])->name('experiences.show');
    Route::post('/experiences/{category}', [RegistrationExperienceController::class, 'store'])
        ->middleware('throttle:registration')
        ->name('experiences.store');

    Route::get('/done', [RegistrationExperienceController::class, 'done'])->name('done');

    Route::get('/{step}', [RegistrationController::class, 'show'])->name('step');
    Route::post('/{step}', [RegistrationController::class, 'store'])
        ->middleware([ProtectAgainstSpam::class, 'throttle:registration'])
        ->name('step.store');
});
