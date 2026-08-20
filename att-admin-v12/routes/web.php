<?php

use Illuminate\Support\Facades\Route;
use App\Models\Setting;
use App\Models\Area;
use App\Models\Principal;
use App\Models\Employee;

Route::get('/', function () {
    $setting = Setting::first();
    $stats = [
        'areas' => Area::count(),
        'principals' => Principal::count(),
        'employees' => Employee::count(),
    ];
    return view('landing', compact('setting', 'stats'));
});

Route::middleware(\App\Http\Middleware\RedirectIfInstalled::class)->group(function () {
    Route::get('/install', [\App\Http\Controllers\InstallController::class, 'index'])->name('install.index');
    Route::post('/install', [\App\Http\Controllers\InstallController::class, 'process'])->name('install.process');
});

Route::get('/migrate-now', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    return \Illuminate\Support\Facades\Artisan::output();
});

// Web Cron Endpoint for Odoo Sync
Route::get('/cron/odoo-sync', function () {
    try {
        $results = \App\Services\OdooSyncService::syncAllConfiguredCompanies('cron');
        return response()->json([
            'status' => 'success',
            'message' => 'Odoo sync executed successfully',
            'data' => $results
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Debug endpoint to check latest error logs
Route::get('/debug-log', function () {
    try {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            return "No laravel.log file found at: " . $logPath;
        }
        $content = file_get_contents($logPath);
        $lines = explode("\n", $content);
        $lastLines = array_slice($lines, -200);
        return response('<pre style="font-family: monospace; font-size: 12px; background: #1e1e1e; color: #fff; padding: 16px;">' . htmlspecialchars(implode("\n", $lastLines)) . '</pre>');
    } catch (\Throwable $e) {
        return "Error reading log: " . $e->getMessage();
    }
});

