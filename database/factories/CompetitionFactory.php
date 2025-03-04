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
        $endTime = clone $startTime;
        $endTime->modify('+' . rand(1, 48) . ' hours');

        return [
            'name' => $this->faker->words(3, true) . ' Competition',
            'description' => $this->faker->paragraph(),
            'entry_fee' => $this->faker->randomFloat(2, 5, 100),
            'prize_pool' => $this->faker->randomFloat(2, 100, 10000),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_users' => $this->faker->numberBetween(10, 1000),
            'status' => $this->faker->randomElement(['upcoming', 'active', 'completed', 'closed']),
        ];
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'upcoming',
            'start_time' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_time' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }
}
