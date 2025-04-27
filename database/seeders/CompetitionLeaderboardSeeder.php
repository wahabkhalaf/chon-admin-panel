<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Player;
use App\Models\SessionLeaderboard;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompetitionLeaderboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing competitions and players
        $competitions = Competition::all();
        $players = Player::all();

        // If there are no existing data, create some
        if ($competitions->isEmpty()) {
            $competitions = Competition::factory(3)->create();
        }

        if ($players->isEmpty()) {
            $players = Player::factory(10)->create();
        }

        // For each competition, generate leaderboard entries
        foreach ($competitions as $competition) {
            // Only add leaderboards for completed or active competitions
            if ($competition->isCompleted() || $competition->isActive()) {
                // Determine how many players will be on the leaderboard (between 5 and 10)
                $playerCount = min($players->count(), rand(5, 10));
                $leaderboardPlayers = $players->random($playerCount);

                // Create scores and rank them
                $scores = [];
                foreach ($leaderboardPlayers as $player) {
                    $scores[$player->id] = rand(10, 1000);
                }

                // Sort scores in descending order
                arsort($scores);

                // Assign ranks and create leaderboard entries
                $rank = 1;
                foreach ($scores as $playerId => $score) {
                    SessionLeaderboard::create([
                        'competition_id' => $competition->id,
                        'player_id' => $playerId,
                        'score' => $score,
                        'rank' => $rank,
                    ]);
                    $rank++;
                }
            }
        }
    }
}