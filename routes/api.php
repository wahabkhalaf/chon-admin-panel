<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppUpdateController;
use App\Http\Controllers\Api\PlayerController;

// App Update Routes
Route::prefix('app-updates')->group(function () {
    // Public route - Flutter app calls this
    Route::post('/check', [AppUpdateController::class, 'checkForUpdates']);
    
    // Admin routes - Protected by middleware
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/', [AppUpdateController::class, 'index']);
        Route::post('/', [AppUpdateController::class, 'store']);
        Route::put('/{appVersion}', [AppUpdateController::class, 'update']);
        Route::delete('/{appVersion}', [AppUpdateController::class, 'destroy']);
        Route::get('/statistics', [AppUpdateController::class, 'statistics']);
    });
});

// Player Routes - API v1
Route::prefix('v1/player')->group(function () {
    // FCM Token Registration/Update
    // POST /api/v1/player/fcm-token
    Route::post('/fcm-token', [PlayerController::class, 'updateFcmToken']);
});
