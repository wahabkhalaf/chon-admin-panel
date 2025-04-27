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

                    // For item prizes, add item details
                    $itemDetails = null;
                    if ($prizeType === 'item') {
                        $itemTypes = ['smartphone', 'laptop', 'tablet', 'car', 'watch', 'gift_card', 'gaming_console', 'other'];
                        $itemType = $itemTypes[array_rand($itemTypes)];

                        $itemNames = [
                            'smartphone' => ['iPhone 15 Pro', 'Samsung Galaxy S24', 'Google Pixel 8', 'Xiaomi 14 Pro'],
                            'laptop' => ['MacBook Pro', 'Dell XPS 15', 'HP Spectre x360', 'Lenovo ThinkPad X1'],
                            'tablet' => ['iPad Pro', 'Samsung Galaxy Tab S9', 'Microsoft Surface Pro'],
                            'car' => ['Honda Civic', 'Toyota Corolla', 'Ford Mustang', 'Tesla Model 3'],
                            'watch' => ['Apple Watch Ultra', 'Samsung Galaxy Watch', 'Garmin Fenix 7'],
                            'gift_card' => ['Amazon Gift Card', 'Steam Wallet', 'Google Play Card'],
                            'gaming_console' => ['PlayStation 5', 'Xbox Series X', 'Nintendo Switch OLED'],
                            'other' => ['Smart TV', 'Wireless Headphones', 'Drone', 'Digital Camera'],
                        ];

                        $itemName = $itemNames[$itemType][array_rand($itemNames[$itemType])];
                        $quantity = $rankFrom === 1 ? 1 : rand(1, 3);

                        $itemDetails = [
                            'type' => $itemType,
                            'name' => $itemName,
                            'quantity' => $quantity,
                            'estimated_value' => rand(100, 5000),
                            'description' => "A premium " . strtolower($itemType) . " prize for competition winners.",
                        ];
                    }

                    $tierData = [
                        'competition_id' => $competition->id,
                        'rank_from' => $rankFrom,
                        'rank_to' => $rankTo,
                        'prize_type' => $prizeType,
                        'prize_value' => $prizeValue,
                    ];

                    if ($itemDetails) {
                        $tierData['item_details'] = $itemDetails;
                    }

                    PrizeTier::create($tierData);
                }
            }
        }
    }
}