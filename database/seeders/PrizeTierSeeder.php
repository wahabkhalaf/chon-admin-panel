<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\PrizeTier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrizeTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing competitions
        $competitions = Competition::all();

        // If no competitions exist, create some
        if ($competitions->isEmpty()) {
            $competitions = Competition::factory(3)->create();
        }

        // For each competition, create prize tiers
        foreach ($competitions as $competition) {
            // Skip if competition already has prize tiers
            if ($competition->prizeTiers()->exists()) {
                continue;
            }

            // For active and completed competitions, create standard prize tiers
            if ($competition->isActive() || $competition->isCompleted()) {
                // First place (1st)
                PrizeTier::create([
                    'competition_id' => $competition->id,
                    'rank_from' => 1,
                    'rank_to' => 1,
                    'prize_type' => 'cash',
                    'prize_value' => rand(500, 2000),
                ]);

                // Second place (2nd)
                PrizeTier::create([
                    'competition_id' => $competition->id,
                    'rank_from' => 2,
                    'rank_to' => 2,
                    'prize_type' => 'cash',
                    'prize_value' => rand(300, 1000),
                ]);

                // Third place (3rd)
                PrizeTier::create([
                    'competition_id' => $competition->id,
                    'rank_from' => 3,
                    'rank_to' => 3,
                    'prize_type' => 'cash',
                    'prize_value' => rand(100, 500),
                ]);

                // Top 10 (4th-10th)
                PrizeTier::create([
                    'competition_id' => $competition->id,
                    'rank_from' => 4,
                    'rank_to' => 10,
                    'prize_type' => 'points',
                    'prize_value' => rand(50, 200),
                ]);
            }
            // For upcoming and open competitions, create randomized prize tiers
            else {
                // Random number of prize tiers (1-4)
                $tierCount = rand(1, 4);
                $lastRank = 0;

                for ($i = 0; $i < $tierCount; $i++) {
                    $rankFrom = $lastRank + 1;
                    $tierWidth = rand(1, 5);
                    $rankTo = $rankFrom + $tierWidth - 1;
                    $lastRank = $rankTo;

                    // Determine prize type
                    $prizeType = rand(1, 10) <= 7 ? 'cash' : (rand(0, 1) ? 'item' : 'points');

                    // Determine prize value based on rank and type
                    $prizeValue = match ($prizeType) {
                        'cash' => rand(100, 1000) / ($rankFrom * 0.5),
                        'points' => rand(50, 500) / ($rankFrom * 0.5),
                        'item' => rand(1, 5),
                    };

                    PrizeTier::create([
                        'competition_id' => $competition->id,
                        'rank_from' => $rankFrom,
                        'rank_to' => $rankTo,
                        'prize_type' => $prizeType,
                        'prize_value' => $prizeValue,
                    ]);
                }
            }
        }
    }
}