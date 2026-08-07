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
