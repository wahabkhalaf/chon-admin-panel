<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\CompetitionPlayerAnswer;
use App\Models\Player;
use App\Models\Question;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompetitionPlayerAnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing competitions, players, and questions
        $competitions = Competition::all();
        $players = Player::all();
        $questions = Question::all();

        // If there are no existing data, create some
        if ($competitions->isEmpty()) {
            $competitions = Competition::factory(3)->create();
        }

        if ($players->isEmpty()) {
            $players = Player::factory(10)->create();
        }

        if ($questions->isEmpty()) {
            $questions = Question::factory(15)->create();
        }

        // For each competition, generate answers from players for questions
        foreach ($competitions as $competition) {
            // Attach questions to the competition if not already attached
            $competitionQuestions = $competition->questions;

            if ($competitionQuestions->isEmpty()) {
                $questionIds = $questions->random(min(5, $questions->count()))->pluck('id')->toArray();
                $competition->questions()->attach($questionIds);
                // Refresh competition to get the newly attached questions
                $competition->refresh();
                $competitionQuestions = $competition->questions;
            }

            // For each player, answer some or all questions
            foreach ($players as $player) {
                // Decide if this player participated in this competition (80% chance)
                if (rand(1, 100) <= 80) {
                    // Answer some or all questions
                    $questionsToAnswer = $competitionQuestions->random(
                        rand(1, $competitionQuestions->count())
                    );

                    foreach ($questionsToAnswer as $question) {
                        // Decide if the answer is correct (70% chance)
                        $isCorrect = rand(1, 100) <= 70;

                        // Get correct answer
                        $correctAnswer = $question->correct_answer ?? 'Sample answer';

                        // Generate player answer
                        if ($isCorrect) {
                            $playerAnswer = $correctAnswer;
                        } else {
                            // Generate incorrect answer based on question type
                            switch ($question->question_type) {
                                case 'multi_choice':
                                    $options = $question->options ?? [];
                                    $incorrectOptions = collect($options)
                                        ->filter(fn($opt) => ($opt['option'] ?? '') !== $correctAnswer)
                                        ->pluck('option')
                                        ->toArray();
                                    $playerAnswer = !empty($incorrectOptions) ?
                                        $incorrectOptions[array_rand($incorrectOptions)] :
                                        'Incorrect ' . $correctAnswer;
                                    break;

                                case 'true_false':
                                    $playerAnswer = $correctAnswer === 'true' ? 'false' : 'true';
                                    break;

                                case 'math':
                                    $modifier = rand(1, 5);
                                    $originalValue = intval($correctAnswer);
                                    $playerAnswer = (string) ($originalValue + $modifier);
                                    break;

                                default:
                                    $playerAnswer = $correctAnswer . 'x';
                                    break;
                            }
                        }

                        // Create the player answer
                        CompetitionPlayerAnswer::create([
                            'player_id' => $player->id,
                            'competition_id' => $competition->id,
                            'question_id' => $question->id,
                            'player_answer' => $playerAnswer,
                            'correct_answer' => $correctAnswer,
                            'is_correct' => $isCorrect,
                            'answered_at' => now()->subMinutes(rand(1, 10000)), // Random time in past
                        ]);
                    }
                }
            }
        }
    }
}