<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\CompetitionPlayerAnswer;
use App\Models\Player;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompetitionPlayerAnswer>
 */
class CompetitionPlayerAnswerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CompetitionPlayerAnswer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random question to generate appropriate answers
        $question = Question::inRandomOrder()->first() ??
            Question::factory()->create(['question_type' => $this->faker->randomElement(['multi_choice', 'true_false', 'math', 'puzzle'])]);

        // Set correct answer based on question
        $correctAnswer = $question->correct_answer ?? 'Sample answer';

        // Decide if this answer will be correct or not (70% chance of being correct)
        $isCorrect = $this->faker->boolean(70);

        // Generate player answer
        $playerAnswer = $isCorrect ? $correctAnswer : $this->generateIncorrectAnswer($question, $correctAnswer);

        return [
            'player_id' => Player::factory(),
            'competition_id' => Competition::factory(),
            'question_id' => $question->id,
            'player_answer' => $playerAnswer,
            'correct_answer' => $correctAnswer,
            'is_correct' => $isCorrect,
            'answered_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Generate an incorrect answer based on the question type
     */
    private function generateIncorrectAnswer(Question $question, string $correctAnswer): string
    {
        switch ($question->question_type) {
            case 'multi_choice':
                // Return a different option than the correct one
                $options = $question->options ?? [];
                $incorrectOptions = collect($options)
                    ->filter(fn($option) => ($option['option'] ?? '') !== $correctAnswer)
                    ->pluck('option')
                    ->toArray();
                return !empty($incorrectOptions) ? $this->faker->randomElement($incorrectOptions) : $this->faker->word();

            case 'true_false':
                // Return the opposite of the correct answer
                return $correctAnswer === 'true' ? 'false' : 'true';

            case 'math':
                // Return a slightly different number
                $modifier = $this->faker->numberBetween(1, 5);
                $originalValue = intval($correctAnswer);
                return (string) ($originalValue + $modifier);

            case 'puzzle':
            case 'pattern_recognition':
            default:
                // Return a similar but incorrect answer
                return $correctAnswer . $this->faker->randomLetter();
        }
    }
}