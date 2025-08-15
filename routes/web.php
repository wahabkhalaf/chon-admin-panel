<?php

use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\AdvertisingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

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

// Serve advertisement images with CORS headers
Route::get('storage/advertisements/{filename}', function ($filename) {
    $path = storage_path('app/public/advertisements/' . $filename);
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    $file = file_get_contents($path);
    $type = mime_content_type($path);
    
    return response($file, 200)
        ->header('Content-Type', $type)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type');
})->where('filename', '.*');

// Handle CORS preflight for advertisement images
Route::options('storage/advertisements/{filename}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type');
})->where('filename', '.*');
