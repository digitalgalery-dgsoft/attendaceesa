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
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        
        $seeder = new \Database\Seeders\ReportTemplatePresetsSeeder();
        $seeder->run();

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
            'count' => $templates->count(),
            'templates' => $templates,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
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

        if ($currentUser && $currentUser->id === $user->id) {
            return redirect()->back();
        }

        if (!session()->has('impersonated_by') && $currentUser) {
            session()->put('impersonated_by', $currentUser->id);
        }

        \Illuminate\Support\Facades\Auth::login($user);

        return redirect('/admin');
    })->name('admin.impersonate');

    // Stop Impersonation
    Route::get('/admin/impersonate-leave', function () {
        if (!session()->has('impersonated_by')) {
            return redirect('/admin');
        }

        $originalUserId = session()->pull('impersonated_by');
        $originalUser = \App\Models\User::find($originalUserId);

        if ($originalUser) {
            \Illuminate\Support\Facades\Auth::login($originalUser);
        }

        return redirect('/admin');
    })->name('admin.impersonate.leave');
});
