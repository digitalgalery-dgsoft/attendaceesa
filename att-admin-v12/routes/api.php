<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Attendance routes
    Route::get('/work-locations', [AttendanceController::class, 'workLocations']);
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [AttendanceController::class, 'checkOut']);
    
    Route::post('/visit-in', [AttendanceController::class, 'visitIn']);
    Route::post('/visit-out', [AttendanceController::class, 'visitOut']);
    
    Route::get('/history', [AttendanceController::class, 'history']);
});
