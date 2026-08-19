<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notify:missed-checkin')->dailyAt('08:30');

// Automated Odoo Synchronization (runs daily at 02:00 AM)
Schedule::command('odoo:sync --trigger=cron')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();

