<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\PaymentMethod;
use App\Models\Question;
use Illuminate\Database\Seeder;

class KurdishLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add Kurdish translations to existing questions
        $questions = Question::whereNull('question_text_kurdish')->take(5)->get();

        foreach ($questions as $index => $question) {
            $kurdishTranslations = [
                [
                    'question_text_kurdish' => 'پرسیارێک لە کوردی: ' . $question->question_text,
                    'options_kurdish' => $this->translateOptions($question->options),
                    'correct_answer_kurdish' => $question->correct_answer ? 'وەڵامی کوردی: ' . $question->correct_answer : null,
                ],
                [
                    'question_text_kurdish' => 'سووڵێک بە کوردی: ' . $question->question_text,
                    'options_kurdish' => $this->translateOptions($question->options),
                    'correct_answer_kurdish' => $question->correct_answer ? 'وەڵامی ڕاستەقینە بە کوردی: ' . $question->correct_answer : null,
                ],
                [
                    'question_text_kurdish' => 'پرسیارێک لە زمانی کوردی: ' . $question->question_text,
                    'options_kurdish' => $this->translateOptions($question->options),
                    'correct_answer_kurdish' => $question->correct_answer ? 'وەڵامی دروست بە کوردی: ' . $question->correct_answer : null,
                ],
                [
                    'question_text_kurdish' => 'پرسیارێک لە کوردی: ' . $question->question_text,
                    'options_kurdish' => $this->translateOptions($question->options),
                    'correct_answer_kurdish' => $question->correct_answer ? 'وەڵامی ڕاست بە کوردی: ' . $question->correct_answer : null,
                ],
                [
                    'question_text_kurdish' => 'سووڵێک لە کوردی: ' . $question->question_text,
                    'options_kurdish' => $this->translateOptions($question->options),
                    'correct_answer_kurdish' => $question->correct_answer ? 'وەڵامی دروست بە کوردی: ' . $question->correct_answer : null,
                ],
            ];

            $translation = $kurdishTranslations[$index % count($kurdishTranslations)];
            $question->update($translation);
        }

        // Add Kurdish translations to existing competitions
        $competitions = Competition::whereNull('name_kurdish')->take(3)->get();

        foreach ($competitions as $index => $competition) {
            $kurdishNames = [
                'پێشبڕکێی ' . $competition->name,
                'یاری ' . $competition->name,
                'پێشبڕکێی ' . $competition->name . ' بە کوردی',
            ];

            $kurdishDescriptions = [
                'وەسفی پێشبڕکێ بە کوردی: ' . ($competition->description ?? 'پێشبڕکێیەک بۆ یاریزانان'),
                'دەربارەی پێشبڕکێ بە کوردی: ' . ($competition->description ?? 'یارییەک بۆ یاریزانان'),
                'وەسفی یاری بە کوردی: ' . ($competition->description ?? 'پێشبڕکێیەک بۆ یاریزانان'),
            ];

            $competition->update([
                'name_kurdish' => $kurdishNames[$index % count($kurdishNames)],
                'description_kurdish' => $kurdishDescriptions[$index % count($kurdishDescriptions)],
            ]);
        }

        $this->command->info('Kurdish language translations have been added successfully!');
    }

    /**
     * Translate options to Kurdish format
     */
    private function translateOptions($options): ?array
    {
        if (!$options || empty($options)) {
            return null;
        }

        // Handle simple string arrays
        if (is_array($options) && isset($options[0]) && is_string($options[0])) {
            return array_map(function ($option) {
                return 'بژاردەی کوردی: ' . $option;
            }, $options);
        }

        // Handle complex option arrays with 'option' and 'is_correct' keys
        if (is_array($options) && isset($options[0]['option'])) {
            return array_map(function ($option) {
                return [
                    'option' => 'بژاردەی کوردی: ' . $option['option'],
                    'is_correct' => $option['is_correct'] ?? false
                ];
            }, $options);
        }

        return null;
    }
}