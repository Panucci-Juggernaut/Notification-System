<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use Illuminate\Support\Facades\Route;

/*
| Public Routes
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
| Protected Routes (Sanctum Auth)
*/

Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/test', [NotificationController::class, 'triggerTest']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/logs', [NotificationController::class, 'logs']);

    // Notification Preferences
    Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index']);
    Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update']);

    // Device Tokens (Firebase Push)
    Route::get('/device-tokens', [DeviceTokenController::class, 'index']);
    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens/{id}', [DeviceTokenController::class, 'destroy']);
});
