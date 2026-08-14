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

Route::post('/login', [AuthController::class, 'login']);

Route::get('/settings', function () {
    return response()->json([
        'status' => 'success',
        'data' => \App\Models\Setting::first()
    ]);
});

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
    Route::get('/itineraries', [ItineraryController::class, 'index']);
    Route::post('/itineraries', [ItineraryController::class, 'store']);
    
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
});
