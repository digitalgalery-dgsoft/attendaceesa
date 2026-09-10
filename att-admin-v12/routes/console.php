<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notify:missed-checkin')->dailyAt('08:30');

// Automated Odoo Synchronization (runs every 30 minutes)
Schedule::command('odoo:sync --trigger=cron')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Artisan::command('dulux:sync-stock-end', function () {
    $this->info("Syncing Dulux Stock End template and healing submissions...");
    \App\Models\ReportTemplate::syncDuluxMergedStockEnd();
    $this->info("Done! Template synced and submissions healed.");
})->purpose('Sync Dulux Stock End template and heal submissions');

Artisan::command('dulux:check-products {--fix}', function () {
    $this->info("Running dulux:check-products command...");
    $fix = $this->option('fix');
    if ($fix) {
        $this->info("Running FIX: Syncing Dulux Offtake template...");
        \App\Models\ReportTemplate::syncDuluxOfftakeTemplate();
        $this->info("Running FIX: Syncing Dulux Stock End template and healing submissions...");
        \App\Models\ReportTemplate::syncDuluxMergedStockEnd();
        $this->info("Done with fix!");
    }
})->purpose('Check Dulux products and templates with fix option');

