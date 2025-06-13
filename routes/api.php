<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserPreferenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('auth/login', [AuthController::class, 'login']);

Route::post('schedules', [ScheduleController::class, 'store']);
Route::get('schedules/{id}', [ScheduleController::class, 'show']);
Route::put('schedules/{id}', [ScheduleController::class, 'update']);
Route::post('schedules/{id}/cancel', [ScheduleController::class, 'cancel']);

// All routes with authentication
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // User routes
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::get('preference', [UserPreferenceController::class, 'show']);
        Route::put('preference', [UserPreferenceController::class, 'update']);
    });

    // Resource routes
    Route::apiResource('availabilities', AvailabilityController::class);
    Route::apiResource('events', EventController::class);
});