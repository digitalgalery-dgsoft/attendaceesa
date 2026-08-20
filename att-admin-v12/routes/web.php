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
    @ignore_user_abort(true);
    @set_time_limit(0);
    @ini_set('max_execution_time', '1800');
    @ini_set('memory_limit', '1024M');
    
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

// Stop User Impersonation and return to Super Admin
Route::middleware(['web', 'auth'])->get('/admin/stop-impersonation', function () {
    if (session()->has('impersonated_by')) {
        $superAdminId = session()->pull('impersonated_by');
        \Illuminate\Support\Facades\Auth::loginUsingId($superAdminId);
        \Filament\Notifications\Notification::make()
            ->title('Kembali ke Akun Utama')
            ->body('Anda telah kembali login sebagai Super Admin.')
            ->success()
            ->send();
    }
    return redirect()->to('/admin');
})->name('impersonation.stop');

