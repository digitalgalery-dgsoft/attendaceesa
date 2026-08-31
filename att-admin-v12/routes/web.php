<?php

use Illuminate\Support\Facades\Route;
use App\Models\Setting;
use App\Models\Area;
use App\Models\Principal;
use App\Models\Employee;

Route::get('/.well-known/acme-challenge/{token}', function ($token) {
    $possiblePaths = [
        public_path(".well-known/acme-challenge/{$token}"),
        base_path(".well-known/acme-challenge/{$token}"),
        "/www/wwwroot/appsend.my.id/.well-known/acme-challenge/{$token}",
        "/www/wwwroot/appsend.my.id/public/.well-known/acme-challenge/{$token}",
        "/www/server/stop/.well-known/acme-challenge/{$token}",
        "/www/server/total/.well-known/acme-challenge/{$token}",
    ];

    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return response(file_get_contents($path), 200, [
                'Content-Type' => 'text/plain',
            ]);
        }
    }

    return response('acme challenge handler active', 404);
});

Route::get('/portal-assets/logo/{id}', function ($id) {
    $principal = \App\Models\Principal::find($id);
    if (!$principal) abort(404);
    
    $path = $principal->logo_path ?: ($principal->logo ?? null);
    if (!$path) abort(404);

    $candidates = [
        storage_path('app/public/' . $path),
        storage_path('app/private/' . $path),
        storage_path('app/' . $path),
        public_path('storage/' . $path),
        base_path('storage/app/public/' . $path),
        base_path('storage/app/' . $path),
    ];

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && !is_dir($filePath)) {
            $mime = mime_content_type($filePath) ?: 'image/png';
            return response()->file($filePath, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    abort(404);
})->name('portal.logo');

Route::get('/portal-assets/banner/{id}', function ($id) {
    $principal = \App\Models\Principal::find($id);
    if (!$principal) abort(404);
    
    $path = $principal->banner_path ?: ($principal->banner ?? null);
    if (!$path) abort(404);

    $candidates = [
        storage_path('app/public/' . $path),
        storage_path('app/private/' . $path),
        storage_path('app/' . $path),
        public_path('storage/' . $path),
        base_path('storage/app/public/' . $path),
        base_path('storage/app/' . $path),
    ];

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && !is_dir($filePath)) {
            $mime = mime_content_type($filePath) ?: 'image/jpeg';
            return response()->file($filePath, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    abort(404);
})->name('portal.banner');

Route::get('/', function (\Illuminate\Http\Request $request) {
    $setting = Setting::first();
    
    // Cek apakah ada tenant principal dari subdomain (misal: dulux.appsend.my.id)
    $tenantPrincipal = $request->attributes->get('tenant_principal') 
                    ?? (app()->bound('current_tenant_principal') ? app('current_tenant_principal') : null);
    $tenantPrincipalIds = $request->attributes->get('tenant_principal_ids') 
                       ?? (app()->bound('current_tenant_principal_ids') ? app('current_tenant_principal_ids') : []);
    $tenantPrincipalsAll = $request->attributes->get('tenant_principals_all') 
                        ?? (app()->bound('current_tenant_principals_all') ? app('current_tenant_principals_all') : collect());

    if ($tenantPrincipal) {
        $scopedPrincipalIds = !empty($tenantPrincipalIds) ? $tenantPrincipalIds : [$tenantPrincipal->id];
        
        $stats = \Illuminate\Support\Facades\Cache::remember("tenant_stats_{$tenantPrincipal->id}_v2", 30, function () use ($scopedPrincipalIds) {
            $areasCount = Employee::whereIn('principal_id', $scopedPrincipalIds)
                ->whereNotNull('branch_id')
                ->distinct('branch_id')
                ->count('branch_id');

            if ($areasCount === 0) {
                $areasCount = Employee::whereIn('principal_id', $scopedPrincipalIds)
                    ->whereNotNull('area_id')
                    ->distinct('area_id')
                    ->count('area_id');
            }

            if ($areasCount === 0) {
                $areasCount = Employee::whereIn('principal_id', $scopedPrincipalIds)
                    ->whereNotNull('work_location_id')
                    ->distinct('work_location_id')
                    ->count('work_location_id');
            }

            return [
                'employees' => Employee::whereIn('principal_id', $scopedPrincipalIds)->count(),
                'areas' => $areasCount,
                'templates' => \App\Models\ReportTemplate::whereHas('principals', function($q) use ($scopedPrincipalIds) {
                    $q->whereIn('principals.id', $scopedPrincipalIds);
                })->where('is_active', true)->count(),
                'submissions' => \App\Models\ReportSubmission::whereIn('principal_id', $scopedPrincipalIds)->count(),
            ];
        });

        $activeTemplates = \App\Models\ReportTemplate::whereHas('principals', function($q) use ($scopedPrincipalIds) {
            $q->whereIn('principals.id', $scopedPrincipalIds);
        })->where('is_active', true)->with('fields')->orderBy('id')->get();

        return view('landing_tenant', compact('setting', 'stats', 'tenantPrincipal', 'activeTemplates', 'tenantPrincipalsAll'));
    }

    $stats = \Illuminate\Support\Facades\Cache::remember('global_landing_stats', 120, function () {
        return [
            'areas' => Area::count(),
            'principals' => Principal::count(),
            'employees' => Employee::count(),
        ];
    });

    return view('landing', compact('setting', 'stats'));
});

// Whitelabel Tenant Portal Auth Routes
Route::get('/login', [\App\Http\Controllers\Auth\TenantAuthController::class, 'showLoginForm'])->name('tenant.login');
Route::post('/login', [\App\Http\Controllers\Auth\TenantAuthController::class, 'login'])->name('tenant.login.submit');
Route::post('/logout', [\App\Http\Controllers\Auth\TenantAuthController::class, 'logout'])->name('tenant.logout');

// Principal Reporting Portal Routes
Route::middleware(['web'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'dashboard'])->name('dashboard');
    
    // Master Data Products / SKU
    Route::get('/products', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'productsList'])->name('products');
    Route::post('/products', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'storeProduct'])->name('products.store');
    Route::put('/products/{id}', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'updateProduct'])->name('products.update');
    Route::delete('/products/{id}', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'destroyProduct'])->name('products.destroy');
    Route::get('/products/template-import', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'downloadTemplateImport'])->name('products.template');
    Route::post('/products/import', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'importProducts'])->name('products.import');

    // Attendance & Time Management Portal Routes
    Route::get('/attendances', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'attendances'])->name('attendances');
    Route::get('/attendances/export', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'exportAttendances'])->name('attendances.export');
    Route::get('/schedules', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'schedulesList'])->name('schedules');
    Route::post('/schedules', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'storeSchedule'])->name('schedules.store');
    Route::post('/schedules/working-group', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'generateFromWorkingGroup'])->name('schedules.working_group');
    Route::put('/schedules/{id}', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'updateSchedule'])->name('schedules.update');
    Route::delete('/schedules/{id}', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'destroySchedule'])->name('schedules.destroy');
    Route::get('/schedules/template-import', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'downloadScheduleTemplate'])->name('schedules.template');
    Route::post('/schedules/import', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'importSchedules'])->name('schedules.import');

    Route::get('/leaves', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'leavesList'])->name('leaves');
    Route::get('/extra-hours', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'extraHoursList'])->name('extra_hours');
    Route::get('/unchecked', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'uncheckedMonitoring'])->name('unchecked');

    // Master Data Portal Routes
    Route::get('/employees', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'employeesList'])->name('employees');
    Route::get('/work-locations', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'workLocationsList'])->name('work_locations');
    Route::get('/shifts', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'shiftsList'])->name('shifts');
    Route::get('/areas', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'areasList'])->name('areas');

    // Field Operations & Sales
    Route::get('/itineraries', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'itinerariesList'])->name('itineraries');
    Route::post('/itineraries', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'storeItinerary'])->name('itineraries.store');
    Route::put('/itineraries/{id}', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'updateItinerary'])->name('itineraries.update');
    Route::delete('/itineraries/{id}', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'destroyItinerary'])->name('itineraries.destroy');
    Route::get('/itineraries/template-import', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'downloadItineraryTemplate'])->name('itineraries.template');
    Route::post('/itineraries/import', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'importItineraries'])->name('itineraries.import');
    Route::get('/visit-reports', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'visitReportsList'])->name('visit_reports');

    // Reports & Analytics
    Route::get('/manpower-report', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'manpowerReport'])->name('manpower_report');
    Route::get('/mandays-report', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'mandaysReport'])->name('mandays_report');
    Route::get('/turnover-report', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'turnoverReport'])->name('turnover_report');

    // Dynamic Report Templates
    Route::get('/report/{code}', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'reportDetail'])->name('report.detail');
    Route::post('/report/{code}/dashboard-config', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'saveDashboardConfig'])->name('report.dashboard.save');
    Route::post('/report/{code}/dashboard-reset', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'resetDashboardConfig'])->name('report.dashboard.reset');
    Route::get('/report/{code}/submission/{id}', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'submissionDetail'])->name('report.submission');
    Route::post('/report/{code}/submission/{id}/status', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'updateSubmissionStatus'])->name('report.submission.status');
    Route::get('/report/{code}/export', [\App\Http\Controllers\Portal\PrincipalPortalController::class, 'exportReport'])->name('report.export');
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
        
        $permSeeder = new \Database\Seeders\PermissionsSeeder();
        $permSeeder->run();

        $seeder = new \Database\Seeders\ReportTemplatePresetsSeeder();
        $seeder->run();

        $prodSeeder = new \Database\Seeders\ProductPresetsSeeder();
        $prodSeeder->run();

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
    // Realtime SSE Terminal Streaming Route for Odoo Sync
    Route::get('/admin/odoo-sync/stream', [\App\Http\Controllers\OdooSyncStreamController::class, 'stream'])->name('admin.odoo-sync.stream');

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

    // Route alias for impersonation.start
    Route::get('/admin/impersonate-start/{user}', function (\App\Models\User $user) {
        return redirect()->route('admin.impersonate', ['user' => $user->id]);
    })->name('impersonation.start');

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

    // Route alias for impersonation.leave
    Route::get('/admin/impersonate-stop', function () {
        return redirect()->route('admin.impersonate.leave');
    })->name('impersonation.leave');
});
