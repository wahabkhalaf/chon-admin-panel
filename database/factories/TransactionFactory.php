<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\Player;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'competition_id' => Competition::factory(),
            'amount' => fake()->randomFloat(2, 5, 50),
            'status' => fake()->randomElement([
                Transaction::STATUS_PENDING,
                Transaction::STATUS_COMPLETED,
                Transaction::STATUS_FAILED,
            ]),
            'reference_id' => fake()->boolean(80) ? fake()->uuid() : null,
            'notes' => fake()->boolean(60) ? fake()->sentence() : null,
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-1 month', 'now') : null,
        ];
    }

    /**
     * Define a completed transaction.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function completed(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'status' => Transaction::STATUS_COMPLETED,
            'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Define a pending transaction.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pending(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'status' => Transaction::STATUS_PENDING,
            'updated_at' => null,
        ]);
    }

    /**
     * Define a failed transaction.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function failed(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'status' => Transaction::STATUS_FAILED,
            'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}