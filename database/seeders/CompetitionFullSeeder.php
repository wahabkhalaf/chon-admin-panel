<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\CompetitionLeaderboard;
use App\Models\CompetitionPlayerAnswer;
use App\Models\PaymentMethod;
use App\Models\Player;
use App\Models\PrizeTier;
use App\Models\Question;
use App\Models\Transaction;
use App\Models\TransactionLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompetitionFullSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if players exist before running PlayerSeeder
        if (Player::count() === 0) {
            $this->call(PlayerSeeder::class);
        }

        // Check if payment methods exist before running PaymentMethodSeeder
        if (PaymentMethod::count() === 0) {
            $this->call(PaymentMethodSeeder::class);
        }

        // Check if questions exist before running QuestionSeeder
        if (Question::count() === 0) {
            $this->call(QuestionSeeder::class);
        }

        // Get payment methods used for transactions
        $paymentMethods = PaymentMethod::where('supports_deposit', true)->get();
        if ($paymentMethods->isEmpty()) {
            throw new \Exception('No payment methods found. Run PaymentMethodSeeder first.');
        }

        // Get players from the database
        $allPlayers = Player::all();
        if ($allPlayers->isEmpty()) {
            throw new \Exception('No players found. Run PlayerSeeder first.');
        }

        // Get existing questions for each type
        $questionsByType = [
            'multi_choice' => Question::where('question_type', 'multi_choice')->inRandomOrder()->take(30)->get(),
            'puzzle' => Question::where('question_type', 'puzzle')->inRandomOrder()->take(20)->get(),
            'pattern_recognition' => Question::where('question_type', 'pattern_recognition')->inRandomOrder()->take(20)->get(),
            'true_false' => Question::where('question_type', 'true_false')->inRandomOrder()->take(30)->get(),
            'math' => Question::where('question_type', 'math')->inRandomOrder()->take(20)->get(),
        ];

        // Define our competition configurations
        $competitions = [
            [
                'name' => 'Trivia Blast',
                'description' => 'Test your knowledge with these trivia questions covering various topics from history to pop culture!',
                'entry_fee' => 5.00,
                'max_users' => 100,
                'game_type' => 'multi_choice',
                'time_modifier' => '-5 days',
                'question_count' => 15,
                'player_count' => 20,
                'prize_tiers' => [
                    ['rank_from' => 1, 'rank_to' => 1, 'prize_type' => 'cash', 'prize_value' => 250.00],
                    ['rank_from' => 2, 'rank_to' => 2, 'prize_type' => 'cash', 'prize_value' => 100.00],
                    ['rank_from' => 3, 'rank_to' => 3, 'prize_type' => 'cash', 'prize_value' => 50.00],
                ]
            ],
            [
                'name' => 'Puzzle Rush',
                'description' => 'Solve these mind-bending puzzles as fast as you can to climb the leaderboard!',
                'entry_fee' => 3.00,
                'max_users' => 200,
                'game_type' => 'puzzle',
                'time_modifier' => '-3 days',
                'question_count' => 10,
                'player_count' => 15,
                'prize_tiers' => [
                    ['rank_from' => 1, 'rank_to' => 1, 'prize_type' => 'cash', 'prize_value' => 300.00],
                    ['rank_from' => 2, 'rank_to' => 10, 'prize_type' => 'points', 'prize_value' => 50.00],
                ]
            ],
            [
                'name' => 'Pattern Master',
                'description' => 'Identify complex patterns and sequences to prove your analytical skills!',
                'entry_fee' => 10.00,
                'max_users' => 50,
                'game_type' => 'pattern_recognition',
                'time_modifier' => '-7 days',
                'question_count' => 12,
                'player_count' => 12,
                'prize_tiers' => [
                    ['rank_from' => 1, 'rank_to' => 3, 'prize_type' => 'cash', 'prize_value' => 150.00],
                    ['rank_from' => 4, 'rank_to' => 10, 'prize_type' => 'points', 'prize_value' => 100.00],
                ]
            ],
            [
                'name' => 'Quick Quiz',
                'description' => 'Fast-paced true/false questions that test your speed and knowledge!',
                'entry_fee' => 2.00,
                'max_users' => 500,
                'game_type' => 'true_false',
                'time_modifier' => '-1 day',
                'question_count' => 20,
                'player_count' => 18,
                'prize_tiers' => [
                    ['rank_from' => 1, 'rank_to' => 1, 'prize_type' => 'cash', 'prize_value' => 500.00],
                ]
            ],
            [
                'name' => 'Math Challenge',
                'description' => 'Put your mathematical skills to the test with these challenging problems!',
                'entry_fee' => 7.00,
                'max_users' => 80,
                'game_type' => 'math',
                'time_modifier' => '-10 days',
                'question_count' => 10,
                'player_count' => 10,
                'prize_tiers' => [
                    ['rank_from' => 1, 'rank_to' => 1, 'prize_type' => 'cash', 'prize_value' => 200.00],
                    ['rank_from' => 2, 'rank_to' => 3, 'prize_type' => 'cash', 'prize_value' => 75.00],
                    ['rank_from' => 4, 'rank_to' => 5, 'prize_type' => 'points', 'prize_value' => 50.00],
                ]
            ],
        ];

        // Verify we have enough questions of each type
        foreach ($questionsByType as $type => $questions) {
            if ($questions->isEmpty()) {
                throw new \Exception("No questions found for type '$type'. Run QuestionSeeder first.");
            }
        }

        // Process each competition
        foreach ($competitions as $config) {
            // Create base timestamps for this competition
            $baseTime = now()->modify($config['time_modifier']);
            $openTime = (clone $baseTime)->subHours(12);
            $startTime = clone $baseTime;
            $endTime = (clone $baseTime)->addHours(2);

            // 1. Create the competition
            $competition = Competition::create([
                'name' => $config['name'],
                'description' => $config['description'],
                'entry_fee' => $config['entry_fee'],
                'open_time' => $openTime,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'max_users' => $config['max_users'],
                'game_type' => $config['game_type'],
                'created_at' => $openTime->copy()->subDays(rand(1, 5)),
                'updated_at' => $openTime->copy()->subDays(rand(1, 5)),
            ]);

            // 2. Assign questions to the competition
            $questions = $questionsByType[$config['game_type']]->take($config['question_count']);
            foreach ($questions as $question) {
                DB::table('competitions_questions')->insert([
                    'competition_id' => $competition->id,
                    'question_id' => $question->id,
                    'created_at' => now(),
                ]);
            }

            // 3. Select random players to join this competition
            $players = $allPlayers->random(min($config['player_count'], count($allPlayers)));

            // 4. Create prize tiers for the competition
            foreach ($config['prize_tiers'] as $tierConfig) {
                PrizeTier::create([
                    'competition_id' => $competition->id,
                    'rank_from' => $tierConfig['rank_from'],
                    'rank_to' => $tierConfig['rank_to'],
                    'prize_type' => $tierConfig['prize_type'],
                    'prize_value' => $tierConfig['prize_value'],
                    'created_at' => $openTime,
                    'updated_at' => $openTime,
                ]);
            }

            // 5. Process each player's participation in this competition
            $playerScores = [];
            $registrationTime = (clone $openTime)->addMinutes(rand(10, 600)); // Random time after registration opened

            foreach ($players as $index => $player) {
                // 5.1 Create a transaction for the player (entry fee payment)
                $paymentMethod = $paymentMethods->random();
                $isSuccessful = rand(1, 10) > 1; // 90% success rate

                $transaction = Transaction::create([
                    'player_id' => $player->id,
                    'competition_id' => $competition->id,
                    'amount' => $competition->entry_fee,
                    'status' => $isSuccessful ? Transaction::STATUS_COMPLETED : Transaction::STATUS_FAILED,
                    'payment_method' => $paymentMethod->code,
                    'payment_provider' => $this->getRandomPaymentProvider(),
                    'payment_details' => $this->getRandomPaymentDetails(),
                    'reference_id' => 'COMP-' . strtoupper(substr(md5(rand()), 0, 10)),
                    'created_at' => $registrationTime,
                    'updated_at' => $registrationTime->copy()->addMinutes(rand(1, 5)),
                ]);

                // Create transaction logs
                TransactionLog::create([
                    'transaction_id' => $transaction->id,
                    'action' => 'created',
                    'reason' => null,
                    'metadata' => [
                        'ip' => fake()->ipv4(),
                        'user_agent' => fake()->userAgent(),
                    ],
                    'created_at' => $transaction->created_at,
                ]);

                if ($isSuccessful) {
                    TransactionLog::create([
                        'transaction_id' => $transaction->id,
                        'action' => 'completed',
                        'reason' => 'Payment processed successfully',
                        'metadata' => [
                            'processor_reference' => 'TXN' . rand(100000, 999999),
                        ],
                        'created_at' => $transaction->updated_at,
                    ]);

                    // 5.2 If the payment was successful, the player answers questions
                    $score = 0;
                    $questionAnswerTime = (clone $startTime)->addMinutes(rand(5, 60)); // Some time after the competition started

                    foreach ($questions as $question) {
                        // Determine if the answer is correct (with 60% chance of being correct)
                        $isCorrect = (rand(1, 100) <= 60);

                        // Generate a player answer
                        $playerAnswer = $isCorrect
                            ? $question->correct_answer
                            : $this->generateIncorrectAnswer($question);

                        // Record the player's answer
                        CompetitionPlayerAnswer::create([
                            'player_id' => $player->id,
                            'competition_id' => $competition->id,
                            'question_id' => $question->id,
                            'player_answer' => $playerAnswer,
                            'correct_answer' => $question->correct_answer,
                            'is_correct' => $isCorrect,
                            'answered_at' => $questionAnswerTime->copy()->addSeconds(rand(10, 30)),
                        ]);

                        if ($isCorrect) {
                            $score++;
                        }

                        // Increment the answer time for each question (to simulate sequential answering)
                        $questionAnswerTime->addSeconds(rand(15, 45));
                    }

                    // Store the player's score for leaderboard ranking
                    $playerScores[$player->id] = $score;
                } else {
                    // Create failed log
                    TransactionLog::create([
                        'transaction_id' => $transaction->id,
                        'action' => 'failed',
                        'reason' => $this->getRandomFailureReason(),
                        'metadata' => [
                            'error_code' => 'ERR' . rand(1000, 9999),
                        ],
                        'created_at' => $transaction->updated_at,
                    ]);
                }
            }

            // 6. Create the leaderboard entries with proper ranking
            if (!empty($playerScores)) {
                // Sort players by score (highest first) and create leaderboard entries
                arsort($playerScores);
                $rank = 1;

                foreach ($playerScores as $playerId => $score) {
                    CompetitionLeaderboard::create([
                        'player_id' => $playerId,
                        'competition_id' => $competition->id,
                        'score' => $score,
                        'rank' => $rank++,
                        'created_at' => $endTime->copy()->addMinutes(rand(5, 15)),
                    ]);
                }
            }
        }
    }

    /**
     * Generate an incorrect answer based on the question type
     */
    private function generateIncorrectAnswer(Question $question): string
    {
        switch ($question->question_type) {
            case 'multi_choice':
                // Get the options array (already decoded by Laravel's cast system)
                $options = $question->options;
                $correctAnswer = $question->correct_answer;

                // Handle different option formats
                if (is_array($options) && !empty($options)) {
                    // The options might be an array of associative arrays with 'option' and 'is_correct' keys
                    if (isset($options[0]['option'])) {
                        $incorrectOptions = array_filter($options, fn($opt) => $opt['option'] !== $correctAnswer);
                        if (!empty($incorrectOptions)) {
                            $selectedOption = $incorrectOptions[array_rand($incorrectOptions)];
                            return $selectedOption['option'];
                        }
                    }
                    // Or just an array of options
                    else {
                        $incorrectOptions = array_filter($options, fn($opt) => $opt !== $correctAnswer);
                        if (!empty($incorrectOptions)) {
                            return $incorrectOptions[array_rand($incorrectOptions)];
                        }
                    }
                }

                // Fallback if we can't extract options properly
                return "Wrong answer";

            case 'true_false':
                return $question->correct_answer === 'true' ? 'false' : 'true';

            case 'pattern_recognition':
            case 'math':
            case 'puzzle':
                // For these types, modify the correct answer to make it incorrect
                if (is_numeric($question->correct_answer)) {
                    $offset = rand(1, 5) * (rand(0, 1) ? 1 : -1);
                    return (string) (intval($question->correct_answer) + $offset);
                } else {
                    // For non-numeric answers, just return a random wrong value
                    $wrongAnswers = ['wrong', 'invalid', 'n/a', 'unknown', 'error'];
                    return $wrongAnswers[array_rand($wrongAnswers)];
                }

            default:
                return 'incorrect';
        }
    }

    /**
     * Get a random payment provider
     */
    private function getRandomPaymentProvider(): string
    {
        $providers = ['zain_cash', 'asia_hawala', 'cash', 'stripe', 'internal'];
        return $providers[array_rand($providers)];
    }

    /**
     * Get random payment details
     */
    private function getRandomPaymentDetails(): array
    {
        $types = ['card', 'mobile', 'wallet'];
        $type = $types[array_rand($types)];

        switch ($type) {
            case 'card':
                return [
                    'card_type' => ['visa', 'mastercard', 'amex'][array_rand(['visa', 'mastercard', 'amex'])],
                    'last4' => rand(1000, 9999),
                    'exp_month' => rand(1, 12),
                    'exp_year' => date('Y') + rand(0, 5),
                ];
            case 'mobile':
                return [
                    'phone_number' => '07' . rand(10000000, 99999999),
                    'provider' => ['Zain', 'Asiacell', 'Korek'][array_rand(['Zain', 'Asiacell', 'Korek'])],
                ];
            case 'wallet':
                return [
                    'account_id' => 'acc_' . bin2hex(random_bytes(5)),
                    'wallet_type' => ['ZainCash', 'AsiaHawala', 'FastPay'][array_rand(['ZainCash', 'AsiaHawala', 'FastPay'])],
                ];
        }
    }

    /**
     * Get a random failure reason for failed transactions
     */
    private function getRandomFailureReason(): string
    {
        $reasons = [
            'Insufficient funds',
            'Payment gateway timeout',
            'User cancelled payment',
            'Authentication failed',
            'Network error during processing',
            'Card declined by issuer',
            'Suspicious activity detected',
        ];

        return $reasons[array_rand($reasons)];
    }
}