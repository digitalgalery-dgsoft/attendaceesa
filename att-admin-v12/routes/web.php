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

// ===== DEBUG ROUTES (hapus setelah selesai testing) =====
Route::get('/debug-fcm', function () {
    $employees = \App\Models\Employee::whereNotNull('fcm_token')
        ->where('is_active', true)
        ->select('id', 'full_name', 'email', 'fcm_token')
        ->get();

    $credPath = storage_path('app/firebase-auth.json');
    $credExists = file_exists($credPath);

    return response()->json([
        'credential_file_exists' => $credExists,
        'credential_path' => $credPath,
        'employees_with_token_count' => $employees->count(),
        'employees' => $employees->map(fn($e) => [
            'full_name' => $e->full_name,
            'email' => $e->email,
            'fcm_token_preview' => substr($e->fcm_token, 0, 30) . '...',
        ]),
    ]);
});

Route::get('/test-fcm-send', function () {
    $employees = \App\Models\Employee::whereNotNull('fcm_token')
        ->where('is_active', true)
        ->get();

    if ($employees->isEmpty()) {
        return response()->json(['error' => 'Tidak ada employee dengan fcm_token. Pastikan sudah login ulang di HP.']);
    }

    $tokens = $employees->pluck('fcm_token')->toArray();
    $firebase = new \App\Services\FirebaseService();
    $result = $firebase->sendNotification($tokens, 'Test Notifikasi', 'Ini adalah test notifikasi dari server!');

    return response()->json([
        'tokens_found' => count($tokens),
        'send_result' => $result,
        'check_laravel_log' => 'Cek storage/logs/laravel.log untuk detail error jika gagal',
    ]);
});

Route::get('/reset-passwords', function () {
    $password = \Illuminate\Support\Facades\Hash::make('123456');
    $updated = \App\Models\Employee::query()->update(['password' => $password]);
    
    return "Berhasil mereset password untuk {$updated} karyawan menjadi 123456.";
});
