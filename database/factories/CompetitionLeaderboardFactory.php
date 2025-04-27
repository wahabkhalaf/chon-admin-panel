<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\Player;
use App\Models\CompetitionLeaderboard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompetitionLeaderboard>
 */
class CompetitionLeaderboardFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CompetitionLeaderboard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'player_id' => Player::factory(),
            'score' => $this->faker->numberBetween(0, 1000),
            'rank' => $this->faker->numberBetween(1, 20),
        ];
    }
}