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

Route::get('/seed-templates-now', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    \Illuminate\Support\Facades\Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\ReportTemplatePresetsSeeder',
        '--force' => true,
    ]);
    $output = \Illuminate\Support\Facades\Artisan::output();
    $templates = \App\Models\ReportTemplate::with('principals')->get()->map(function ($t) {
        return [
            'id' => $t->id,
            'title' => $t->title,
            'code' => $t->code,
            'principals' => $t->principals->pluck('name'),
            'fields_count' => $t->fields()->count(),
        ];
    });
    return response()->json([
        'status' => 'success',
        'output' => $output,
        'templates' => $templates,
    ]);
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

// User Impersonation Routes for Super Admin
Route::middleware(['web'])->group(function () {
    // Start Impersonation
    Route::get('/admin/impersonate/{user}', function (\App\Models\User $user) {
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        $isAuthorized = ($currentUser && $currentUser->isSuperAdmin()) || session()->has('impersonated_by');

        if (!$isAuthorized) {
            abort(403, 'Hanya Super Admin yang diizinkan untuk beralih akun.');
        }

        $originalSuperAdminId = session()->get('impersonated_by', $currentUser?->id);

        \Illuminate\Support\Facades\Auth::guard('web')->login($user, true);
        \Filament\Facades\Filament::auth()->login($user, true);
        session()->put('impersonated_by', $originalSuperAdminId);
        session()->put('password_hash_web', $user->getAuthPassword());
        session()->save();

        return redirect()->to('/admin');
    })->name('impersonation.start');

    // Stop Impersonation
    Route::get('/admin/stop-impersonation', function () {
        if (session()->has('impersonated_by')) {
            $superAdminId = session()->pull('impersonated_by');
            $superAdmin = \App\Models\User::find($superAdminId);
            if ($superAdmin) {
                \Illuminate\Support\Facades\Auth::guard('web')->login($superAdmin, true);
                \Filament\Facades\Filament::auth()->login($superAdmin, true);
                session()->put('password_hash_web', $superAdmin->getAuthPassword());
                session()->save();
            }
        }
        return redirect()->to('/admin');
    })->name('impersonation.stop');
});

