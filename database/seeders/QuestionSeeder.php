<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 5 questions of each type
        foreach (array_keys(Question::TYPES) as $type) {
            Question::factory()->count(30)->create([
                'question_type' => $type
            ]);
        }

        // Create some specific questions
        Question::create([
            'question_text' => 'What is the capital of France?',
            'question_type' => 'multi_choice',
            'options' => [
                ['option' => 'London', 'is_correct' => false],
                ['option' => 'Paris', 'is_correct' => true],
                ['option' => 'Berlin', 'is_correct' => false],
                ['option' => 'Madrid', 'is_correct' => false],
            ],
            'correct_answer' => 'Paris',
            'level' => 'easy',
        ]);

        Question::create([
            'question_text' => 'Unscramble this word: MPOCUTRE',
            'question_type' => 'puzzle',
            'options' => [],
            'correct_answer' => 'COMPUTER',
            'level' => 'medium',
        ]);

        Question::create([
            'question_text' => 'Is water wet?',
            'question_type' => 'true_false',
            'options' => [],
            'correct_answer' => 'true',
            'level' => 'easy',
        ]);
    }
}
