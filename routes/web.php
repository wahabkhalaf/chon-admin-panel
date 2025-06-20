<?php

use App\Http\Controllers\Api\LanguageController;
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
