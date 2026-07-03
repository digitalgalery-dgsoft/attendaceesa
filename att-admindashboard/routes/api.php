<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/attendance', [\App\Http\Controllers\Api\AttendanceController::class, 'store']);
    Route::get('/attendance/history', [\App\Http\Controllers\Api\AttendanceController::class, 'history']);
    Route::get('/work-locations', [\App\Http\Controllers\Api\AttendanceController::class, 'workLocations']);
});
