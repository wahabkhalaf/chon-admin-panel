<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $questions = [
            [
                'question_text' => 'What is the capital city of France?',
                'question_type' => 'multi_choice',
                'options' => ['Paris', 'London', 'Berlin', 'Madrid'],
                'correct_answer' => 'Paris',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who wrote the play "Romeo and Juliet"?',
                'question_type' => 'multi_choice',
                'options' => ['William Shakespeare', 'Charles Dickens', 'Jane Austen', 'Mark Twain'],
                'correct_answer' => 'William Shakespeare',
                'level' => 'easy',
            ],
            [
                'question_text' => 'What is the largest planet in our Solar System?',
                'question_type' => 'multi_choice',
                'options' => ['Earth', 'Jupiter', 'Saturn', 'Mars'],
                'correct_answer' => 'Jupiter',
                'level' => 'easy',
            ],
            [
                'question_text' => 'In which year did World War II end?',
                'question_type' => 'multi_choice',
                'options' => ['1945', '1939', '1918', '1965'],
                'correct_answer' => '1945',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the chemical symbol for gold?',
                'question_type' => 'multi_choice',
                'options' => ['Au', 'Ag', 'Gd', 'Go'],
                'correct_answer' => 'Au',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who painted the Mona Lisa?',
                'question_type' => 'multi_choice',
                'options' => ['Leonardo da Vinci', 'Pablo Picasso', 'Vincent van Gogh', 'Claude Monet'],
                'correct_answer' => 'Leonardo da Vinci',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which language is the most spoken worldwide?',
                'question_type' => 'multi_choice',
                'options' => ['English', 'Mandarin Chinese', 'Spanish', 'Hindi'],
                'correct_answer' => 'Mandarin Chinese',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the smallest prime number?',
                'question_type' => 'multi_choice',
                'options' => ['1', '2', '3', '5'],
                'correct_answer' => '2',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which continent is the Sahara Desert located on?',
                'question_type' => 'multi_choice',
                'options' => ['Africa', 'Asia', 'Australia', 'South America'],
                'correct_answer' => 'Africa',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who is known as the father of computers?',
                'question_type' => 'multi_choice',
                'options' => ['Charles Babbage', 'Alan Turing', 'Bill Gates', 'Steve Jobs'],
                'correct_answer' => 'Charles Babbage',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the hardest natural substance on Earth?',
                'question_type' => 'multi_choice',
                'options' => ['Diamond', 'Gold', 'Iron', 'Quartz'],
                'correct_answer' => 'Diamond',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which planet is known as the Red Planet?',
                'question_type' => 'multi_choice',
                'options' => ['Mars', 'Venus', 'Jupiter', 'Mercury'],
                'correct_answer' => 'Mars',
                'level' => 'easy',
            ],
            [
                'question_text' => 'What is the main ingredient in sushi?',
                'question_type' => 'multi_choice',
                'options' => ['Rice', 'Fish', 'Seaweed', 'Soy Sauce'],
                'correct_answer' => 'Rice',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Who discovered penicillin?',
                'question_type' => 'multi_choice',
                'options' => ['Alexander Fleming', 'Marie Curie', 'Isaac Newton', 'Albert Einstein'],
                'correct_answer' => 'Alexander Fleming',
                'level' => 'medium',
            ],
            [
                'question_text' => 'Which country gifted the Statue of Liberty to the USA?',
                'question_type' => 'multi_choice',
                'options' => ['France', 'England', 'Germany', 'Italy'],
                'correct_answer' => 'France',
                'level' => 'medium',
            ],
            [
                'question_text' => 'What is the boiling point of water at sea level in Celsius?',
                'question_type' => 'multi_choice',
                'options' => ['100', '90', '80', '120'],
                'correct_answer' => '100',
                'level' => 'easy',
            ],
            [
                'question_text' => 'Which organ in the human body is responsible for pumping blood?',
                'question_type' => 'multi_choice',
                'options' => ['Heart', 'Liver', 'Lungs', 'Kidney'],
                'correct_answer' => 'Heart',
                'level' => 'easy',
            ],
            [
                'question_text' => 'What is the largest mammal in the world?',
                'question_type' => 'multi_choice',
                'options' => ['Blue Whale', 'Elephant', 'Giraffe', 'Hippopotamus'],
                'correct_answer' => 'Blue Whale',
                'level' => 'medium',
            ],
            [
                'question_text' => 'Who developed the theory of relativity?',
                'question_type' => 'multi_choice',
                'options' => ['Albert Einstein', 'Isaac Newton', 'Galileo Galilei', 'Nikola Tesla'],
                'correct_answer' => 'Albert Einstein',
                'level' => 'medium',
            ],
            [
                'question_text' => 'Which gas do plants absorb from the atmosphere?',
                'question_type' => 'multi_choice',
                'options' => ['Carbon Dioxide', 'Oxygen', 'Nitrogen', 'Hydrogen'],
                'correct_answer' => 'Carbon Dioxide',
                'level' => 'easy',
            ],
        ];

        foreach ($questions as $question) {
            DB::table('questions')->insert([
                'question_text' => $question['question_text'],
                'question_type' => $question['question_type'],
                'options' => json_encode($question['options']),
                'correct_answer' => $question['correct_answer'],
                'level' => $question['level'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
