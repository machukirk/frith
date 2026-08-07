<?php

use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WaitlistController;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

Route::get('/', [WaitlistController::class, 'show'])->name('coming-soon');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::post('/waitlist', [WaitlistController::class, 'store'])
    ->middleware([ProtectAgainstSpam::class, 'throttle:waitlist'])
    ->name('waitlist.store');

Route::get('/waitlist/{signup}/confirm', [WaitlistController::class, 'confirm'])
    ->middleware('signed')
    ->name('waitlist.confirm');

// GET for the link in the email body, POST for RFC 8058 one-click unsubscribe,
// which is what Gmail and Outlook call when someone uses their own control.
Route::match(['get', 'post'], '/waitlist/{signup}/unsubscribe', [WaitlistController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('waitlist.unsubscribe');

// Undo, offered on the unsubscribed page. Link scanners follow GET links, so
// someone can be unsubscribed without ever touching the link themselves.
Route::post('/waitlist/{signup}/resubscribe', [WaitlistController::class, 'resubscribe'])
    ->middleware('signed')
    ->name('waitlist.resubscribe');
