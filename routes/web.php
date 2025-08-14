<?php

use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\AdvertisingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API routes for language functionality
Route::prefix('api/language')->group(function () {
    Route::get('/questions', [LanguageController::class, 'getQuestions']);
    Route::get('/competitions', [LanguageController::class, 'getCompetitions']);
    Route::get('/payment-methods', [LanguageController::class, 'getPaymentMethods']);
    Route::get('/available-languages', [LanguageController::class, 'getAvailableLanguages']);
});

// API routes for player notifications
Route::prefix('api/player-notifications')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\PlayerNotificationController::class, 'index']);
    Route::get('/unread-count', [App\Http\Controllers\Api\PlayerNotificationController::class, 'unreadCount']);
    Route::post('/mark-as-read', [App\Http\Controllers\Api\PlayerNotificationController::class, 'markAsRead']);
    Route::post('/mark-all-as-read', [App\Http\Controllers\Api\PlayerNotificationController::class, 'markAllAsRead']);
    Route::get('/{notificationId}', [App\Http\Controllers\Api\PlayerNotificationController::class, 'show']);
});

// API routes for advertising
Route::prefix('api/advertising')->group(function () {
    Route::get('/', [AdvertisingController::class, 'index']);
    Route::get('/random', [AdvertisingController::class, 'random']);
    Route::get('/image', [AdvertisingController::class, 'image']);           // NEW: Get only image
    Route::get('/image-base64', [AdvertisingController::class, 'imageBase64']); // NEW: Get base64 image
    Route::get('/{advertising}', [AdvertisingController::class, 'show']);
});
