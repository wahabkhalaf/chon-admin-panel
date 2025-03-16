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
            'competition_id' => fake()->boolean(70) ? Competition::factory() : null,
            'amount' => fake()->randomFloat(2, 5, 1000),
            'transaction_type' => fake()->randomElement([
                Transaction::TYPE_DEPOSIT,
                Transaction::TYPE_WITHDRAWAL,
                Transaction::TYPE_ENTRY_FEE,
                Transaction::TYPE_PRIZE,
                Transaction::TYPE_BONUS,
                Transaction::TYPE_REFUND,
            ]),
            'status' => fake()->randomElement([
                Transaction::STATUS_PENDING,
                Transaction::STATUS_COMPLETED,
                Transaction::STATUS_FAILED,
                Transaction::STATUS_CANCELLED,
                Transaction::STATUS_REFUNDED,
            ]),
            'reference_id' => fake()->boolean(80) ? fake()->uuid() : null,
            'notes' => fake()->boolean(60) ? fake()->sentence() : null,
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-1 month', 'now') : null,
        ];
    }

    /**
     * Define a deposit transaction.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function deposit(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'transaction_type' => Transaction::TYPE_DEPOSIT,
            'competition_id' => null,
            'amount' => fake()->randomFloat(2, 10, 500),
        ]);
    }

    /**
     * Define a withdrawal transaction.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withdrawal(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'transaction_type' => Transaction::TYPE_WITHDRAWAL,
            'competition_id' => null,
            'amount' => fake()->randomFloat(2, 10, 300),
        ]);
    }

    /**
     * Define an entry fee transaction.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function entryFee(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'transaction_type' => Transaction::TYPE_ENTRY_FEE,
            'competition_id' => Competition::factory(),
            'amount' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    /**
     * Define a prize transaction.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function prize(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'transaction_type' => Transaction::TYPE_PRIZE,
            'competition_id' => Competition::factory(),
            'amount' => fake()->randomFloat(2, 20, 1000),
        ]);
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
}