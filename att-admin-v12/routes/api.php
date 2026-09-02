<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ItineraryController;
use App\Http\Controllers\Api\BlastInfoController;
use App\Http\Controllers\Api\PermitController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SalesReportController;
use App\Http\Controllers\Api\DashboardApiController;

Route::get('/ping', function () {
    return response()->json(['status' => 'pong', 'time' => now()->timestamp]);
});

Route::get('/health', function () {
    return response()->json(['status' => 'healthy', 'time' => now()->timestamp]);
});

Route::match(['get', 'post'], '/check', function () {
    return response()->json(['status' => 'ok', 'app' => 'ESA Attendance']);
});

Route::get('/settings', function () {
    $data = null;
    try {
        $data = \Illuminate\Support\Facades\Cache::remember('public_app_system_setting_array_v2', 86400, function () {
            $st = \App\Models\Setting::first();
            return $st ? $st->toArray() : null;
        });
    } catch (\Throwable $e) {
        // Fallback jika database sedang busy
    }

    if (empty($data) || !is_array($data) || empty($data['app_name'])) {
        $data = [
            'id' => 1,
            'app_name' => 'ESA Solutions',
            'theme_color' => '#0A192F',
            'logo_path' => 'logos/01M1GJWJSCB0E7WCPPWZXBFB9F.png',
            'require_checkin_photo' => true,
            'require_checkout_photo' => true,
            'require_visit_photo' => true,
            'use_roster_principle' => false,
            'lock_roster' => true,
            'global_distance_lock' => 50,
            'mobile_app_url' => null,
            'tracking_interval_minutes' => 5,
            'tracking_distance_meters' => 10,
            'dark_mode_enabled' => true,
            'dark_mode_theme' => 'dark_navy',
        ];
    }

    return response()->json([
        'status' => 'success',
        'data' => $data
    ]);
});

// Helpdesk & Real-Time Login Assistance Routes (Public - NIK verified)
Route::post('/helpdesk/check-nik', [\App\Http\Controllers\Api\HelpdeskApiController::class, 'checkNik']);
Route::post('/helpdesk/initiate-chat', [\App\Http\Controllers\Api\HelpdeskApiController::class, 'initiateChat']);
Route::get('/helpdesk/messages', [\App\Http\Controllers\Api\HelpdeskApiController::class, 'getMessages']);
Route::post('/helpdesk/send-message', [\App\Http\Controllers\Api\HelpdeskApiController::class, 'sendMessage']);
Route::post('/helpdesk/mark-read', [\App\Http\Controllers\Api\HelpdeskApiController::class, 'markAsRead']);

Route::get('/permit/print/{id}', [PermitController::class, 'downloadPdf'])->name('api.permit.download')->middleware('signed');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);

    // Schedule & Itinerary hari ini
    Route::get('/today-schedule', [AttendanceController::class, 'todaySchedule']);

    // Dashboard stats
    Route::get('/dashboard/stats', [DashboardApiController::class, 'stats']);
    Route::get('/dashboard/team-stats', [DashboardApiController::class, 'teamStats']);
    Route::get('/dashboard/team-unchecked', [DashboardApiController::class, 'teamUnchecked']);

    // Attendance routes
    Route::get('/work-locations', [AttendanceController::class, 'workLocations']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::post('/attendance/visit-report', [AttendanceController::class, 'storeVisitReport']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);
    
    // Tracking routes
    Route::post('/tracking', [\App\Http\Controllers\Api\TrackingController::class, 'store']);
    Route::get('/tracking/history', [\App\Http\Controllers\Api\TrackingController::class, 'history']);

    // Itinerary routes
    Route::get('/itineraries/work-locations', [ItineraryController::class, 'availableWorkLocations']);
    Route::get('/itineraries/principals', [ItineraryController::class, 'getPrincipals']);
    Route::get('/itineraries', [ItineraryController::class, 'index']);
    Route::post('/itineraries', [ItineraryController::class, 'store']);
    Route::delete('/itineraries/{id}', [ItineraryController::class, 'destroy']);

    // Location Request routes (Request New Location by Employee)
    Route::get('/location-requests', [\App\Http\Controllers\Api\LocationRequestApiController::class, 'index']);
    Route::post('/location-requests', [\App\Http\Controllers\Api\LocationRequestApiController::class, 'store']);
    Route::post('/location-requests/parse-maps-url', [\App\Http\Controllers\Api\LocationRequestApiController::class, 'parseMapsUrl']);
    
    // BAP routes (Berita Acara Presensi / Bukti Absensi Manual)
    Route::get('/baps/eligible-dates', [\App\Http\Controllers\Api\BapApiController::class, 'eligibleDates']);
    Route::post('/baps', [\App\Http\Controllers\Api\BapApiController::class, 'store']);
    Route::get('/baps/history', [\App\Http\Controllers\Api\BapApiController::class, 'history']);
    
    // Blast Infos
    Route::get('/blast-infos', [BlastInfoController::class, 'index']);

    // Permit routes
    Route::get('/permits', [PermitController::class, 'index']);
    Route::post('/permits', [PermitController::class, 'store']);
    Route::get('/permits/leave-quota', [PermitController::class, 'leaveQuota']);
    Route::get('/permits/cuti-peraturan-types', [PermitController::class, 'cutiPeraturanTypes']);

    // Overtime / Extra Hour routes
    Route::get('/overtime/status', [\App\Http\Controllers\Api\ExtraHourController::class, 'status']);
    Route::post('/overtime/start', [\App\Http\Controllers\Api\ExtraHourController::class, 'start']);
    Route::post('/overtime/finish', [\App\Http\Controllers\Api\ExtraHourController::class, 'finish']);

    // Sales Report routes
    Route::get('/sales-reports', [SalesReportController::class, 'index']);
    Route::post('/sales-reports', [SalesReportController::class, 'store']);
    Route::put('/sales-reports/{id}', [SalesReportController::class, 'update']);
    Route::post('/sales-reports/{id}/analyze', [SalesReportController::class, 'analyze']);

    // Sales Pipeline routes
    Route::get('/sales-pipelines', [\App\Http\Controllers\Api\SalesPipelineController::class, 'index']);
    Route::put('/sales-pipelines/{id}', [\App\Http\Controllers\Api\SalesPipelineController::class, 'update']);

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);

    // Payslip routes
    Route::get('/payslips', [\App\Http\Controllers\Api\PayslipApiController::class, 'getPayslips']);

    // Live Chat routes
    Route::get('/chat/messages', [\App\Http\Controllers\Api\ChatController::class, 'getMessages']);
    Route::post('/chat/send', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage']);
    Route::post('/chat/read', [\App\Http\Controllers\Api\ChatController::class, 'markAsRead']);
    Route::get('/chat/unread', [\App\Http\Controllers\Api\ChatController::class, 'getUnreadCount']);

    // Meeting Attendance routes
    Route::get('/meetings/today', [\App\Http\Controllers\Api\MeetingController::class, 'today']);
    Route::post('/meetings/meet-in', [\App\Http\Controllers\Api\MeetingController::class, 'meetIn']);
    Route::post('/meetings/meet-out', [\App\Http\Controllers\Api\MeetingController::class, 'meetOut']);
    Route::get('/meetings/history', [\App\Http\Controllers\Api\MeetingController::class, 'history']);
    Route::get('/meetings/{id}', [\App\Http\Controllers\Api\MeetingController::class, 'show']);

    // Dynamic Principal Reporting routes
    Route::get('/reporting/templates', [\App\Http\Controllers\Api\ReportingApiController::class, 'templates']);
    Route::get('/reporting/stores', [\App\Http\Controllers\Api\ReportingApiController::class, 'stores']);
    Route::post('/reporting/submit', [\App\Http\Controllers\Api\ReportingApiController::class, 'submit']);
    Route::get('/reporting/history', [\App\Http\Controllers\Api\ReportingApiController::class, 'history']);
    Route::get('/reporting/submissions/{id}', [\App\Http\Controllers\Api\ReportingApiController::class, 'show']);
    Route::post('/reporting/submissions/{id}', [\App\Http\Controllers\Api\ReportingApiController::class, 'update']);

    // Cross-Entity Hierarchy & Approval routes
    Route::get('/v1/cross-entity/subordinates', [\App\Http\Controllers\Api\ServerGatewayController::class, 'crossEntitySubordinates']);
    Route::post('/v1/cross-entity/approve', [\App\Http\Controllers\Api\ServerGatewayController::class, 'crossEntityApproval']);
});

// Multi-Server Gateway & Discovery Routes (Public)
Route::post('/v1/gateway/discover', [\App\Http\Controllers\Api\ServerGatewayController::class, 'discover']);
Route::post('/v1/gateway/login', [\App\Http\Controllers\Api\ServerGatewayController::class, 'login']);

// Cross-Server Template Synchronization Routes (Secure Token)
Route::prefix('v1/sync')->group(function () {
    Route::post('/report-template', [\App\Http\Controllers\Api\TemplateSyncController::class, 'syncReportTemplate']);
    Route::get('/ping', [\App\Http\Controllers\Api\TemplateSyncController::class, 'ping']);
});



