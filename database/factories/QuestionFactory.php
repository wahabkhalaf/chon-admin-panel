<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $questionTypes = array_keys(Question::TYPES);
        $questionType = $this->faker->randomElement($questionTypes);

        return match ($questionType) {
            'multi_choice' => $this->multiChoiceQuestion(),
            'puzzle' => $this->puzzleQuestion(),
            'pattern_recognition' => $this->patternQuestion(),
            'true_false' => $this->trueFalseQuestion(),
            'math' => $this->mathQuestion(),
            default => $this->multiChoiceQuestion(),
        };
    }

    /**
     * Generate a multiple choice question.
     */
    protected function multiChoiceQuestion(): array
    {
        $options = [];
        $correctIndex = $this->faker->numberBetween(0, 3);

        for ($i = 0; $i < 4; $i++) {
            $options[] = [
                'option' => $this->faker->sentence(3),
                'is_correct' => ($i === $correctIndex)
            ];
        }

        return [
            'question_text' => $this->faker->sentence(10) . '?',
            'question_type' => 'multi_choice',
            'options' => $options,
            'correct_answer' => $options[$correctIndex]['option'],
            'level' => $this->faker->randomElement(['easy', 'medium', 'hard']),
        ];
    }

    /**
     * Generate a puzzle question.
     */
    protected function puzzleQuestion(): array
    {
        $words = ['programming', 'algorithm', 'database', 'interface', 'function', 'variable'];
        $word = $this->faker->randomElement($words);

        return [
            'question_text' => 'Unscramble the following letters: ' . str_shuffle($word),
            'question_type' => 'puzzle',
            'options' => [],
            'correct_answer' => $word,
            'level' => $this->faker->randomElement(['easy', 'medium', 'hard']),
        ];
    }

    /**
     * Generate a pattern recognition question.
     */
    protected function patternQuestion(): array
    {
        $patterns = [
            ['sequence' => '2, 4, 6, 8, ?', 'answer' => '10'],
            ['sequence' => '1, 3, 6, 10, ?', 'answer' => '15'],
            ['sequence' => '1, 2, 4, 8, ?', 'answer' => '16'],
            ['sequence' => '3, 6, 9, 12, ?', 'answer' => '15'],
        ];

        $pattern = $this->faker->randomElement($patterns);

        return [
            'question_text' => 'What comes next in this sequence? ' . $pattern['sequence'],
            'question_type' => 'pattern_recognition',
            'options' => [],
            'correct_answer' => $pattern['answer'],
            'level' => $this->faker->randomElement(['easy', 'medium', 'hard']),
        ];
    }

    /**
     * Generate a true/false question.
     */
    protected function trueFalseQuestion(): array
    {
        $statements = [
            ['text' => 'The sky is blue.', 'answer' => 'true'],
            ['text' => 'Humans have three legs.', 'answer' => 'false'],
            ['text' => 'Water boils at 100 degrees Celsius at sea level.', 'answer' => 'true'],
            ['text' => 'The Earth is flat.', 'answer' => 'false'],
        ];

        $statement = $this->faker->randomElement($statements);

        return [
            'question_text' => $statement['text'],
            'question_type' => 'true_false',
            'options' => [],
            'correct_answer' => $statement['answer'],
            'level' => $this->faker->randomElement(['easy', 'medium', 'hard']),
        ];
    }

    /**
     * Generate a math question.
     */
    protected function mathQuestion(): array
    {
        $num1 = $this->faker->numberBetween(1, 20);
        $num2 = $this->faker->numberBetween(1, 20);
        $operations = ['+', '-', '*'];
        $operation = $this->faker->randomElement($operations);

        $answer = match ($operation) {
            '+' => $num1 + $num2,
            '-' => $num1 - $num2,
            '*' => $num1 * $num2,
            default => $num1 + $num2,
        };

        return [
            'question_text' => "Calculate: $num1 $operation $num2",
            'question_type' => 'math',
            'options' => [],
            'correct_answer' => (string) $answer,
            'level' => $this->faker->randomElement(['easy', 'medium', 'hard']),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (Question $question) {
            // Additional configuration if needed
        })->afterCreating(function (Question $question) {
            // Additional actions after creation if needed
        });
    }
}
