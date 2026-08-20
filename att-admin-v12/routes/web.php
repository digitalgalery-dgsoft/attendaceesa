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

// User Impersonation Routes for Super Admin
Route::middleware(['web', 'auth'])->group(function () {
    // Start Impersonation
    Route::get('/admin/impersonate/{user}', function (\App\Models\User $user) {
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        $isAuthorized = $currentUser->isSuperAdmin() || session()->has('impersonated_by');

        if (!$isAuthorized) {
            abort(403, 'Hanya Super Admin yang diizinkan untuk beralih akun.');
        }

        $originalSuperAdminId = session()->get('impersonated_by', $currentUser->id);

        \Filament\Facades\Filament::auth()->login($user);
        request()->session()->regenerate();
        session()->put('impersonated_by', $originalSuperAdminId);
        session()->save();

        \Filament\Notifications\Notification::make()
            ->title('Berhasil Beralih Akun')
            ->body("Anda sekarang login dan melihat sistem sebagai {$user->name} ({$user->email}).")
            ->success()
            ->send();

        return redirect()->to('/admin');
    })->name('impersonation.start');

    // Stop Impersonation
    Route::get('/admin/stop-impersonation', function () {
        if (session()->has('impersonated_by')) {
            $superAdminId = session()->pull('impersonated_by');
            $superAdmin = \App\Models\User::find($superAdminId);
            if ($superAdmin) {
                \Filament\Facades\Filament::auth()->login($superAdmin);
                request()->session()->regenerate();
                session()->save();

                \Filament\Notifications\Notification::make()
                    ->title('Kembali ke Akun Utama')
                    ->body('Anda telah kembali login sebagai Super Admin.')
                    ->success()
                    ->send();
            }
        }
        return redirect()->to('/admin');
    })->name('impersonation.stop');
});

