<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\TransactionLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionLog>
 */
class TransactionLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TransactionLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'completed', 'failed', 'cancelled']),
            'reason' => fake()->boolean(70) ? fake()->sentence() : null,
            'metadata' => fake()->boolean(50) ? [
                'ip' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'device' => fake()->randomElement(['mobile', 'desktop', 'tablet']),
            ] : null,
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Define a creation log.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function creation(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'action' => 'created',
            'reason' => null,
        ]);
    }

    /**
     * Define a completion log.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function completion(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'action' => 'completed',
            'reason' => 'Transaction processed successfully',
        ]);
    }

    /**
     * Define a failure log.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function failure(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'action' => 'failed',
            'reason' => fake()->randomElement([
                'Insufficient funds',
                'Payment gateway error',
                'User cancelled',
                'Timeout',
                'Verification failed',
            ]),
        ]);
    }
}