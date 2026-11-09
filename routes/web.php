<?php

use App\Http\Controllers\AccountBadgeController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountDataController;
use App\Http\Controllers\AccountExperienceController;
use App\Http\Controllers\AccountNotificationController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\JourneyController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PagePreviewController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RegistrationEmailController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SafetyController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\VillageController;
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
    Route::get('/robots.txt', RobotsController::class)->name('robots');
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
 * The product. Everything behind here needs a family logged in, because all
 * of it is either about them or about somebody who has agreed to be seen by
 * them.
 */
Route::middleware('auth:founder')->group(function () {
    Route::get('/village', [VillageController::class, 'show'])->name('village');

    // Asked once, the first time the village is opened. The village itself
    // waits until there is an answer.
    Route::get('/village/setup', [VillageController::class, 'setup'])->name('village.setup');
    Route::post('/village/setup', [VillageController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('village.setup.store');

    Route::get('/journeys', [JourneyController::class, 'index'])->name('journeys');

    Route::post('/journeys/{journey}/join', [JourneyController::class, 'join'])
        ->middleware('throttle:30,1')
        ->name('journeys.join');

    Route::post('/journeys/{journey}/leave', [JourneyController::class, 'leave'])
        ->middleware('throttle:30,1')
        ->name('journeys.leave');

    Route::get('/journeys/{journey}', [JourneyController::class, 'show'])->name('journeys.show');

    // Reached by public_id, not by a slug of their name: two families can
    // both be "Fun House", and a name can change.
    Route::get('/f/{family}', [FamilyController::class, 'show'])->name('family');

    Route::get('/requests', [ConnectionController::class, 'index'])->name('requests');

    Route::post('/f/{family}/ask', [ConnectionController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('connections.ask');

    Route::post('/requests/{connection}/accept', [ConnectionController::class, 'accept'])
        ->middleware('throttle:30,1')
        ->name('connections.accept');

    Route::post('/requests/{connection}/decline', [ConnectionController::class, 'decline'])
        ->middleware('throttle:30,1')
        ->name('connections.decline');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages');
    Route::get('/messages/{connection}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{connection}', [MessageController::class, 'store'])
        ->middleware([ProtectAgainstSpam::class, 'throttle:60,1'])
        ->name('messages.send');

    // Blocking needs no reason and tells nobody. Reporting is read by a
    // person, and blocks as well, because nobody should have to do it twice.
    Route::get('/f/{family}/block', [SafetyController::class, 'blockForm'])->name('block');
    Route::post('/f/{family}/block', [SafetyController::class, 'block'])
        ->middleware('throttle:20,1')
        ->name('block.store');

    Route::get('/f/{family}/report', [SafetyController::class, 'reportForm'])->name('report');
    Route::post('/f/{family}/report', [SafetyController::class, 'report'])
        ->middleware('throttle:10,1')
        ->name('report.store');

    /*
     * The account. Their own profile, the settings behind it, and the way
     * out. Editing reuses the registration screens rather than a second set
     * of forms. See App\Support\AnswerSheet for why.
     */
    Route::get('/account', [AccountController::class, 'show'])->name('account');

    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/edit/{step}', [AccountController::class, 'edit'])->name('edit');
        Route::post('/edit/{step}', [AccountController::class, 'update'])
            ->middleware([ProtectAgainstSpam::class, 'throttle:30,1'])
            ->name('edit.store');

        // "Does this still sound like your family?" Both answers buy the same
        // quiet, so neither is the cheap one.
        Route::get('/experiences/review', [AccountExperienceController::class, 'review'])->name('experiences.review');
        Route::post('/experiences/review', [AccountExperienceController::class, 'confirm'])
            ->middleware('throttle:30,1')
            ->name('experiences.confirm');
        Route::post('/experiences/snooze', [AccountExperienceController::class, 'snooze'])
            ->middleware('throttle:30,1')
            ->name('experiences.snooze');

        Route::get('/badges', [AccountBadgeController::class, 'show'])->name('badges');
        Route::post('/badges', [AccountBadgeController::class, 'update'])
            ->middleware('throttle:30,1')
            ->name('badges.update');

        Route::get('/notifications', [AccountNotificationController::class, 'show'])->name('notifications');
        Route::post('/notifications', [AccountNotificationController::class, 'update'])
            ->middleware('throttle:30,1')
            ->name('notifications.update');

        Route::get('/data', [AccountDataController::class, 'show'])->name('data');
        Route::post('/data/request', [AccountDataController::class, 'request'])
            ->middleware('throttle:6,1')
            ->name('data.request');

        // Leaving is its own screen with its own confirmation, because what
        // it does to messages already sent cannot be undone.
        Route::get('/leave', [AccountDataController::class, 'confirmDelete'])->name('delete');
        Route::post('/leave', [AccountDataController::class, 'delete'])
            ->middleware('throttle:6,1')
            ->name('delete.store');

        Route::get('/blocked/{family}', [AccountDataController::class, 'blocked'])->name('blocked');
        Route::post('/blocked/{family}/unblock', [AccountDataController::class, 'unblock'])
            ->middleware('throttle:20,1')
            ->name('unblock');
    });
});

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

    Route::get('/welcome', [RegistrationController::class, 'welcome'])->name('welcome');

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
