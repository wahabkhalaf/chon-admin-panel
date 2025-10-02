<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\PaymentMethod;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Get questions in the specified language
     */
    public function getQuestions(Request $request): JsonResponse
    {
        $language = $request->get('language', 'en');
        $questions = Question::all();

        $data = $questions->map(function ($question) use ($language) {
            return [
                'id' => $question->id,
                'question_text' => $question->getQuestionText($language),
                'options' => $question->getOptions($language),
                'correct_answer' => $question->getCorrectAnswer($language),
                'question_type' => $question->question_type,
                'level' => $question->level,
                'has_kurdish' => $question->hasKurdishTranslation(),
                'has_arabic' => $question->hasArabicTranslation(),
                'has_kurmanji' => $question->hasKurmanjiTranslation(),
                'available_languages' => $question->getAvailableLanguages(),
            ];
        });

        return response()->json([
            'success' => true,
            'language' => $language,
            'data' => $data,
        ]);
    }

    /**
     * Get competitions in the specified language
     */
    public function getCompetitions(Request $request): JsonResponse
    {
        $language = $request->get('language', 'en');
        $competitions = Competition::all();

        $data = $competitions->map(function ($competition) use ($language) {
            return [
                'id' => $competition->id,
                'name' => $competition->getName($language),
                'description' => $competition->getDescription($language),
                'entry_fee' => $competition->entry_fee,
                'game_type' => $competition->game_type,
                'status' => $competition->getStatus(),
                'has_kurdish' => $competition->hasKurdishTranslation(),
                'has_arabic' => $competition->hasArabicTranslation(),
                'has_kurmanji' => $competition->hasKurmanjiTranslation(),
                'available_languages' => $competition->getAvailableLanguages(),
            ];
        });

        return response()->json([
            'success' => true,
            'language' => $language,
            'data' => $data,
        ]);
    }

    /**
     * Get payment methods in the specified language
     */
    public function getPaymentMethods(Request $request): JsonResponse
    {
        $language = $request->get('language', 'en');
        $paymentMethods = PaymentMethod::all();

        $data = $paymentMethods->map(function ($paymentMethod) use ($language) {
            return [
                'id' => $paymentMethod->id,
                'name' => $paymentMethod->getName($language),
                'instructions' => $paymentMethod->getInstructions($language),
                'code' => $paymentMethod->code,
                'provider' => $paymentMethod->provider,
                'is_active' => $paymentMethod->is_active,
                'has_kurdish' => $paymentMethod->hasKurdishTranslation(),
                'available_languages' => $paymentMethod->getAvailableLanguages(),
            ];
        });

        return response()->json([
            'success' => true,
            'language' => $language,
            'data' => $data,
        ]);
    }

    /**
     * Get available languages for all content types
     */
    public function getAvailableLanguages(): JsonResponse
    {
        $questionsKu = Question::whereNotNull('question_text_kurdish')->count();
        $questionsAr = Question::whereNotNull('question_text_arabic')->count();
        $questionsKm = Question::whereNotNull('question_text_kurmanji')->count();

        $competitionsKu = Competition::whereNotNull('name_kurdish')->count();
        $competitionsAr = Competition::whereNotNull('name_arabic')->count();
        $competitionsKm = Competition::whereNotNull('name_kurmanji')->count();
        $paymentMethods = PaymentMethod::whereNotNull('name_kurdish')->count();

        return response()->json([
            'success' => true,
            'available_languages' => [
                'en' => 'English',
                'ku' => 'Kurdish',
                'ar' => 'Arabic',
                'km' => 'Kurmanji',
            ],
            'translation_stats' => [
                'questions_with_kurdish' => $questionsKu,
                'questions_with_arabic' => $questionsAr,
                'questions_with_kurmanji' => $questionsKm,
                'competitions_with_kurdish' => $competitionsKu,
                'competitions_with_arabic' => $competitionsAr,
                'competitions_with_kurmanji' => $competitionsKm,
                'payment_methods_with_kurdish' => $paymentMethods,
            ],
        ]);
    }
}
