<?php

namespace Database\Factories;

use App\Models\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompetitionFactory extends Factory
{
    protected $model = Competition::class;

    public function definition(): array
    {
        $openTime = $this->faker->dateTimeBetween('now', '+1 month');
        $startTime = (clone $openTime)->modify('+' . rand(1, 7) . ' days');
        $endTime = (clone $startTime)->modify('+' . rand(2, 48) . ' hours'); // Ensure minimum 2 hours difference

        return [
            'name' => $this->faker->words(3, true) . ' Competition',
            'description' => $this->faker->paragraph(),
            'entry_fee' => $this->faker->randomFloat(2, 30, 100),
            'open_time' => $openTime,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_users' => $this->faker->numberBetween(50, 1000),
            'game_type' => $this->faker->randomElement(['action', 'strategy', 'puzzle', 'racing', 'sports', 'rpg', 'other']),
        ];
    }

    /**
     * Competition that hasn't opened for registration yet
     */
    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $openTime = $this->faker->dateTimeBetween('+1 day', '+1 month');
            $startTime = (clone $openTime)->modify('+' . rand(1, 7) . ' days');

            return [
                'open_time' => $openTime,
                'start_time' => $startTime,
                'end_time' => (clone $startTime)->modify('+' . rand(2, 24) . ' hours'),
            ];
        });
    }

    /**
     * Competition that is open for registration but hasn't started
     */
    public function openForRegistration(): static
    {
        return $this->state(function (array $attributes) {
            $openTime = $this->faker->dateTimeBetween('-1 day', 'now');
            $startTime = $this->faker->dateTimeBetween('+1 day', '+1 week');

            return [
                'open_time' => $openTime,
                'start_time' => $startTime,
                'end_time' => (clone $startTime)->modify('+' . rand(2, 24) . ' hours'),
            ];
        });
    }

    /**
     * Competition that is currently active
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $openTime = $this->faker->dateTimeBetween('-1 week', '-1 day');
            $startTime = $this->faker->dateTimeBetween('-1 day', 'now');

            return [
                'open_time' => $openTime,
                'start_time' => $startTime,
                'end_time' => (clone $startTime)->modify('+' . rand(2, 12) . ' hours'),
            ];
        });
    }

    /**
     * Competition that has completed
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $openTime = $this->faker->dateTimeBetween('-2 months', '-1 month');
            $startTime = $this->faker->dateTimeBetween('-1 month', '-1 week');
            $endTime = $this->faker->dateTimeBetween('-1 week', '-1 day');

            return [
                'open_time' => $openTime,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }
}