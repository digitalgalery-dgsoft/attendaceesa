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

Route::get('/app-logo', function () {
    $setting = \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::first() : null;
    $path = $setting?->logo_path;
    if (!$path) abort(404);

    $cleanPath = ltrim(str_replace(['public/', 'storage/'], '', $path), '/');

    $candidates = [
        storage_path('app/public/' . $cleanPath),
        storage_path('app/public/' . $path),
        storage_path('app/public/logos/' . basename($path)),
        storage_path('app/' . $cleanPath),
        storage_path('app/' . $path),
        storage_path('app/logos/' . basename($path)),
        storage_path('app/private/' . $cleanPath),
        storage_path('app/private/' . $path),
        storage_path('app/private/logos/' . basename($path)),
        public_path('storage/' . $cleanPath),
        public_path('storage/' . $path),
        public_path('storage/logos/' . basename($path)),
        public_path($path),
        public_path($cleanPath),
        base_path('storage/app/public/' . $cleanPath),
        base_path('storage/app/public/' . $path),
        base_path('storage/app/' . $cleanPath),
        base_path('storage/app/' . $path),
    ];

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && !is_dir($filePath)) {
            $mime = @mime_content_type($filePath) ?: 'image/png';
            return response()->file($filePath, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    abort(404);
})->name('app.logo');

Route::get('/portal-assets/logo/{id}', function ($id) {
    $principal = \App\Models\Principal::find($id);
    if (!$principal) abort(404);
    
    $path = $principal->logo_path ?: ($principal->logo ?? null);
    
    if (!$path && !empty($principal->subdomain)) {
        $sibling = \App\Models\Principal::where('subdomain', $principal->subdomain)
            ->where(function($q) {
                $q->whereNotNull('logo_path')->where('logo_path', '!=', '')
                  ->orWhereNotNull('logo')->where('logo', '!=', '');
            })
            ->first();
        if ($sibling) {
            $path = $sibling->logo_path ?: $sibling->logo;
        }
    }

    if (!$path) abort(404);

    $cleanPath = ltrim(str_replace(['public/', 'storage/'], '', $path), '/');

    $candidates = [
        storage_path('app/public/' . $cleanPath),
        storage_path('app/public/' . $path),
        storage_path('app/private/' . $cleanPath),
        storage_path('app/private/' . $path),
        storage_path('app/' . $cleanPath),
        storage_path('app/' . $path),
        public_path('storage/' . $cleanPath),
        public_path('storage/' . $path),
        public_path($path),
        public_path($cleanPath),
        base_path('storage/app/public/' . $cleanPath),
        base_path('storage/app/public/' . $path),
        base_path('storage/app/' . $cleanPath),
        base_path('storage/app/' . $path),
    ];

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && !is_dir($filePath)) {
            $mime = @mime_content_type($filePath) ?: 'image/png';
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
    
    if (!$path && !empty($principal->subdomain)) {
        $sibling = \App\Models\Principal::where('subdomain', $principal->subdomain)
            ->where(function($q) {
                $q->whereNotNull('banner_path')->where('banner_path', '!=', '')
                  ->orWhereNotNull('banner')->where('banner', '!=', '');
            })
            ->first();
        if ($sibling) {
            $path = $sibling->banner_path ?: $sibling->banner;
        }
    }

    if (!$path) abort(404);

    $cleanPath = ltrim(str_replace(['public/', 'storage/'], '', $path), '/');

    $candidates = [
        storage_path('app/public/' . $cleanPath),
        storage_path('app/public/' . $path),
        storage_path('app/private/' . $cleanPath),
        storage_path('app/private/' . $path),
        storage_path('app/' . $cleanPath),
        storage_path('app/' . $path),
        public_path('storage/' . $cleanPath),
        public_path('storage/' . $path),
        public_path($path),
        public_path($cleanPath),
        base_path('storage/app/public/' . $cleanPath),
        base_path('storage/app/public/' . $path),
        base_path('storage/app/' . $cleanPath),
        base_path('storage/app/' . $path),
    ];

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && !is_dir($filePath)) {
            $mime = @mime_content_type($filePath) ?: 'image/jpeg';
            return response()->file($filePath, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    abort(404);
})->name('portal.banner');

Route::get('/portal-assets/bap-evidence/{id}', function ($id) {
    $bap = \App\Models\BapRequest::find($id);
    if (!$bap) abort(404);
    
    $path = $bap->evidence_path;
    if (!$path) abort(404);

    $cleanPath = ltrim(str_replace(['public/', 'storage/'], '', $path), '/');

    $candidates = [
        storage_path('app/public/' . $cleanPath),
        storage_path('app/public/' . $path),
        storage_path('app/private/' . $cleanPath),
        storage_path('app/private/' . $path),
        storage_path('app/' . $cleanPath),
        storage_path('app/' . $path),
        public_path('storage/' . $cleanPath),
        public_path('storage/' . $path),
        public_path($path),
        public_path($cleanPath),
        base_path('storage/app/public/' . $cleanPath),
        base_path('storage/app/public/' . $path),
        base_path('storage/app/' . $cleanPath),
        base_path('storage/app/' . $path),
    ];

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && !is_dir($filePath)) {
            $mime = @mime_content_type($filePath) ?: 'image/jpeg';
            return response()->file($filePath, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    abort(404);
})->name('bap.evidence');

Route::get('/attachment-stream/{id}', function ($id) {
    $permit = \App\Models\LeaveRequest::find($id);
    if (!$permit || !$permit->attachment_path) {
        abort(404);
    }

    $path = $permit->attachment_path;
    $cleanPath = ltrim(str_replace(['public/', 'storage/'], '', $path), '/');

    $candidates = [
        storage_path('app/public/' . $cleanPath),
        storage_path('app/public/' . $path),
        storage_path('app/' . $cleanPath),
        storage_path('app/' . $path),
        storage_path('app/private/' . $cleanPath),
        storage_path('app/private/' . $path),
        public_path('storage/' . $cleanPath),
        public_path('storage/' . $path),
        public_path($cleanPath),
        public_path($path),
        base_path('storage/app/public/' . $cleanPath),
        base_path('storage/app/public/' . $path),
    ];

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && !is_dir($filePath)) {
            $mime = @mime_content_type($filePath) ?: 'image/jpeg';
            return response()->file($filePath, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    abort(404);
})->name('permit.attachment');

Route::get('/storage/{path}', function ($path) {
    $cleanPath = ltrim(str_replace(['public/', 'storage/'], '', $path), '/');

    $candidates = [
        storage_path('app/public/' . $cleanPath),
        storage_path('app/public/' . $path),
        storage_path('app/' . $cleanPath),
        storage_path('app/' . $path),
        storage_path('app/private/' . $cleanPath),
        storage_path('app/private/' . $path),
        public_path('storage/' . $cleanPath),
        public_path('storage/' . $path),
        public_path($cleanPath),
        public_path($path),
        base_path('storage/app/public/' . $cleanPath),
        base_path('storage/app/public/' . $path),
    ];

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && !is_dir($filePath)) {
            $mime = @mime_content_type($filePath) ?: 'application/octet-stream';
            return response()->file($filePath, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    abort(404);
})->where('path', '.*')->name('storage.fallback');

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
        return app(\App\Http\Controllers\Auth\TenantAuthController::class)->showLoginForm($request);
    }

    $stats = \Illuminate\Support\Facades\Cache::remember('global_landing_stats_active_v3', 60, function () {
        return [
            'areas'      => Area::count(),
            'principals' => Principal::where('is_active', true)->count(),
            'employees'  => Employee::where('is_active', true)->whereNull('deleted_at')->count(),
            'locations'  => \App\Models\WorkLocation::where('is_active', true)->count(),
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

Route::get('/cek-admin', function () {
    try {
        $users = \App\Models\User::all(['id', 'name', 'email']);
        return response()->json([
            'status' => 'success',
            'users' => $users,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/reset-admin', function () {
    try {
        $user = \App\Models\User::first();
        if ($user) {
            $pass = request('pass', 'AdminAKP2026!');
            $user->password = \Illuminate\Support\Facades\Hash::make($pass);
            $user->save();
            return response()->json([
                'status' => 'success',
                'message' => "Password berhasil direset ke: {$pass}",
                'email' => $user->email,
                'password' => $pass,
            ]);
        }
        return response()->json(['status' => 'error', 'message' => 'User admin belum ada']);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

Route::get('/login-as-admin', function () {
    try {
        $user = \App\Models\User::first();
        if (!$user) {
            return 'User admin tidak ditemukan di database.';
        }
        \Illuminate\Support\Facades\Auth::guard('web')->login($user, true);
        request()->session()->regenerate();
        return redirect('/admin');
    } catch (\Throwable $e) {
        return 'Error: ' . $e->getMessage();
    }
});

Route::get('/test-login', function () {
    try {
        $user = \App\Models\User::first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan']);
        }
        $password = request('pass', 'AdminAKP2026!');
        $check = \Illuminate\Support\Facades\Hash::check($password, $user->password);
        $attempt = \Illuminate\Support\Facades\Auth::guard('web')->attempt([
            'email' => $user->email,
            'password' => $password,
        ]);
        return response()->json([
            'email' => $user->email,
            'password_yang_diuji' => $password,
            'hash_matches' => $check,
            'auth_attempt_success' => $attempt,
            'can_access_panel' => $user->canAccessPanel(filament()->getPanel('admin')),
        ]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

Route::get('/fix-admin-access', function () {
    try {
        // 1. Run migrations
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

        // 2. Run PermissionsSeeder
        $permSeeder = new \Database\Seeders\PermissionsSeeder();
        $permSeeder->run();

        // 3. Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 4. Ensure Super Admin role exists
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        
        // Give all permissions to Super Admin role
        $allPermissions = \Spatie\Permission\Models\Permission::where('guard_name', 'web')->get();
        $role->syncPermissions($allPermissions);

        // 5. Assign to all users (or first user)
        $user = \App\Models\User::first();
        if ($user) {
            $user->assignRole($role);
        }

        // 6. Seed preset templates and products
        try {
            (new \Database\Seeders\ReportTemplatePresetsSeeder())->run();
            (new \Database\Seeders\ProductPresetsSeeder())->run();
        } catch (\Throwable $e) {}

        // 7. Clear caches
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');

        return response()->json([
            'status' => 'success',
            'message' => 'Role Super Admin dan seluruh hak akses menu (permissions) berhasil disinkronkan!',
            'user' => $user?->email,
            'roles' => $user?->getRoleNames(),
            'permissions_count' => $user?->getAllPermissions()->count(),
            'next_step' => 'Silakan buka kembali https://akp.dgsoft.web.id/admin'
        ]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

Route::get('/debug-sidebar', function () {
    try {
        $user = \App\Models\User::first();
        if ($user) {
            \Illuminate\Support\Facades\Auth::login($user);
        }
        
        $panel = filament()->getPanel('admin');
        $resources = $panel->getResources();
        $pages = $panel->getPages();
        $widgets = $panel->getWidgets();
        
        $navData = [];
        try {
            $navigation = $panel->getNavigation();
            foreach ($navigation as $group) {
                $groupLabel = $group->getLabel() ?: 'None';
                $items = [];
                foreach ($group->getItems() as $item) {
                    $items[] = [
                        'label' => $item->getLabel(),
                        'url' => $item->getUrl(),
                        'isActive' => $item->isActive(),
                    ];
                }
                $navData[$groupLabel] = $items;
            }
        } catch (\Throwable $e) {
            $navData = ['error' => $e->getMessage()];
        }
        
        return response()->json([
            'user' => $user?->email,
            'isSuperAdmin' => $user?->isSuperAdmin(),
            'hasRole_Super_Admin' => $user?->hasRole('Super Admin'),
            'permissions_count' => $user?->getAllPermissions()->count(),
            'resources_count' => count($resources),
            'resources_sample' => array_slice($resources, 0, 10),
            'pages_count' => count($pages),
            'widgets_count' => count($widgets),
            'navigation' => $navData,
        ]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
    }
});

Route::get('/check-log', function () {
    $logFile = storage_path('logs/laravel.log');
    if (!file_exists($logFile)) {
        return 'laravel.log does not exist';
    }
    $lines = file($logFile);
    $lastLines = array_slice($lines, -150);
    return '<pre style="background:#111;color:#eee;padding:16px;white-space:pre-wrap;font-family:monospace;">' . htmlspecialchars(implode('', $lastLines)) . '</pre>';
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

    // Poll unread chat messages & tickets for live toast notification in Admin Panel
    Route::get('/admin/unread-helpdesk-chat', function () {
        if (!auth()->check()) {
            return response()->json(['unread_count' => 0, 'latest_message' => null]);
        }

        $latestMsg = \App\Models\ChatMessage::where('sender_type', 'employee')
            ->orderByDesc('id')
            ->first();

        $unreadCount = \App\Models\ChatMessage::where('sender_type', 'employee')
            ->where('is_read', false)
            ->count();

        $latestData = null;
        if ($latestMsg) {
            $conv = $latestMsg->conversation;
            $employee = $conv?->employee;
            $empName = $employee?->full_name ?? ($employee?->name ?? 'Karyawan');
            $nik = $employee?->employee_no ?? '';
            $isTicket = str_contains($latestMsg->message, '[TIKET BANTUAN KARYAWAN]');

            $previewText = $latestMsg->message;
            if ($isTicket) {
                if (preg_match('/Kasus:\s*([^\n\r]+)/', $latestMsg->message, $m)) {
                    $previewText = 'Tiket: ' . trim($m[1]);
                }
            }

            $latestData = [
                'id' => $latestMsg->id,
                'conversation_id' => $latestMsg->conversation_id,
                'employee_name' => $empName,
                'employee_no' => $nik,
                'message' => \Illuminate\Support\Str::limit($previewText, 80),
                'is_ticket' => $isTicket,
                'is_read' => (bool) $latestMsg->is_read,
                'created_at' => $latestMsg->created_at?->diffForHumans() ?? 'Baru saja',
            ];
        }

        return response()->json([
            'unread_count' => $unreadCount,
            'latest_message' => $latestData,
        ]);
    })->name('admin.unread-helpdesk-chat');

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
