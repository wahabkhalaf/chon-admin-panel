<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\PrizeTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PrizeTier>
 */
class PrizeTierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PrizeTier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a random rank range
        $rankFrom = $this->faker->numberBetween(1, 10);
        $rankTo = $this->faker->numberBetween($rankFrom, $rankFrom + 5);

        return [
            'competition_id' => Competition::factory(),
            'rank_from' => $rankFrom,
            'rank_to' => $rankTo,
            'prize_type' => $this->faker->randomElement(['cash', 'item', 'points']),
            'prize_value' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }

    /**
     * First place prize tier
     */
    public function firstPlace(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rank_from' => 1,
                'rank_to' => 1,
                'prize_type' => 'cash',
                'prize_value' => $this->faker->randomFloat(2, 100, 2000),
            ];
        });
    }

    /**
     * Second place prize tier
     */
    public function secondPlace(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rank_from' => 2,
                'rank_to' => 2,
                'prize_type' => 'cash',
                'prize_value' => $this->faker->randomFloat(2, 50, 1000),
            ];
        });
    }

    /**
     * Third place prize tier
     */
    public function thirdPlace(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rank_from' => 3,
                'rank_to' => 3,
                'prize_type' => 'cash',
                'prize_value' => $this->faker->randomFloat(2, 20, 500),
            ];
        });
    }

    /**
     * Runner-up places prize tier (4th to 10th)
     */
    public function runnerUp(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rank_from' => 4,
                'rank_to' => 10,
                'prize_type' => 'points',
                'prize_value' => $this->faker->randomFloat(2, 10, 100),
            ];
        });
    }
}