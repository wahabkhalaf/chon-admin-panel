<?php

namespace Database\Factories;

use App\Models\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompetitionFactory extends Factory
{
    protected $model = Competition::class;

    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('now', '+2 months');
        $endTime = (clone $startTime)->modify('+' . rand(2, 48) . ' hours'); // Ensure minimum 2 hours difference

        return [
            'name' => $this->faker->words(3, true) . ' Competition',
            'description' => $this->faker->paragraph(),
            'entry_fee' => $this->faker->randomFloat(2, 3000, 10000),
            'prize_pool' => $this->faker->randomFloat(2, 5000000, 10000000),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_users' => $this->faker->numberBetween(50000, 100000),
            'status' => $this->faker->randomElement(['upcoming', 'active', 'completed', 'closed']),
        ];
    }

    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $startTime = $this->faker->dateTimeBetween('now', '+1 month');
            return [
                'status' => 'upcoming',
                'start_time' => $startTime,
                'end_time' => (clone $startTime)->modify('+' . rand(2, 24) . ' hours'),
            ];
        });
    }

    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $startTime = $this->faker->dateTimeBetween('-1 day', 'now');
            return [
                'status' => 'active',
                'start_time' => $startTime,
                'end_time' => (clone $startTime)->modify('+' . rand(2, 12) . ' hours'),
            ];
        });
    }
}