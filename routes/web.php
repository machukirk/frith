<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PagePreviewController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RegistrationEmailController;
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

/*
 * The public pages. Each reads its own copy through App\Support\PageContent, so
 * there is nothing for a controller to do — the slug is the route name is the
 * content key, and a page is a view plus a file in config/content.
 */
Route::view('/', 'pages.home')->name('home');

foreach ([
    'how-it-works',
    'about',
    'help',
    'meet-up-safety',
    'reporting',
    'community-guidelines',
    'terms',
    'privacy',
    'frith-plus',
] as $page) {
    Route::view("/{$page}", "pages.{$page}")->name($page);
}

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
 * Getting in and back out.
 *
 * The emailed link is the front door — registration never asks for a password,
 * because a parent registering at 1am on a phone will not remember one a
 * fortnight later. The password path is there for anybody who has set one.
 */
Route::middleware('guest:founder')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');

    Route::post('/login', [LoginController::class, 'attempt'])
        ->middleware([ProtectAgainstSpam::class, 'throttle:6,1'])
        ->name('login.attempt');

    Route::view('/forgot', 'pages.auth.forgot')->name('forgot');

    Route::post('/login/link', [LoginController::class, 'sendLink'])
        ->middleware([ProtectAgainstSpam::class, 'throttle:4,1'])
        ->name('login.send-link');

    Route::view('/link-sent', 'pages.auth.link-sent')->name('link-sent');

    Route::get('/login/link/{registration}/{token}', [LoginController::class, 'consume'])
        ->middleware('throttle:10,1')
        ->name('login.link');
});

Route::view('/verified', 'pages.auth.verified')
    ->middleware('auth:founder')
    ->name('verified');

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth:founder')
    ->name('logout');

/*
 * The admin panel's live preview. Behind the panel's own guard, because it
 * renders somebody's unsaved draft.
 */
Route::get('/admin/preview/{slug}', PagePreviewController::class)
    ->middleware(['auth'])
    ->name('admin.preview');

/*
 * The help page's "write to a person" form. Honeypot rather than a CAPTCHA:
 * somebody writing in about a safeguarding worry should not be asked to
 * identify fire hydrants first.
 */
Route::post('/help/contact', [ContactController::class, 'store'])
    ->middleware([ProtectAgainstSpam::class, 'throttle:6,1'])
    ->name('help.contact');

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

    /*
     * The links in the Founder email. Signed rather than session-based: the
     * link is the proof, and somebody guessing a URL gets a 403.
     */
    Route::middleware('signed')->group(function () {
        Route::get('/verify/{registration}', [RegistrationEmailController::class, 'verify'])->name('verify');

        // GET for the link in the body, POST for RFC 8058 one-click, which is
        // what Gmail and Outlook call from their own unsubscribe control.
        Route::match(['get', 'post'], '/unsubscribe/{registration}', [RegistrationEmailController::class, 'unsubscribe'])
            ->name('unsubscribe');

        Route::post('/resubscribe/{registration}', [RegistrationEmailController::class, 'resubscribe'])
            ->name('resubscribe');
    });

    Route::view('/confirmed', 'register.verified-done')->name('verified.done');

    Route::get('/{step}', [RegistrationController::class, 'show'])->name('step');
    Route::post('/{step}', [RegistrationController::class, 'store'])
        ->middleware([ProtectAgainstSpam::class, 'throttle:registration'])
        ->name('step.store');
});
