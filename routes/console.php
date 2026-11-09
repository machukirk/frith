<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * The other half of the promise on the leaving screen: the profile goes when
 * somebody presses the button, and what we hold about them goes a month
 * later. Early morning, when nobody is reading anything.
 */
Schedule::command('frith:purge-departed')->dailyAt('03:20');
