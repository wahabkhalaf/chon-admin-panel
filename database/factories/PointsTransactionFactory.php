<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\PointsTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PointsTransaction>
 */
class PointsTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PointsTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement([
            PointsTransaction::TYPE_PURCHASE,
            PointsTransaction::TYPE_SPEND,
            PointsTransaction::TYPE_ADMIN_CREDIT,
            PointsTransaction::TYPE_REFUND,
        ]);

        $balanceBefore = $this->faker->numberBetween(0, 10000);
        $amount = $this->faker->numberBetween(50, 1000);

        $isCredit = in_array($type, [
            PointsTransaction::TYPE_PURCHASE,
            PointsTransaction::TYPE_ADMIN_CREDIT,
            PointsTransaction::TYPE_REFUND,
        ]);

        $balanceAfter = $isCredit
            ? $balanceBefore + $amount
            : max(0, $balanceBefore - $amount);

        return [
            'player_id' => Player::factory(),
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => $this->faker->optional()->randomElement([
                PointsTransaction::REF_COMPETITION,
                PointsTransaction::REF_PACKAGE_PURCHASE,
                PointsTransaction::REF_ADMIN_ACTION,
            ]),
            'reference_id' => $this->faker->optional()->uuid(),
            'metadata' => $this->faker->optional()->passthrough([
                'note' => $this->faker->sentence(),
            ]),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create a purchase transaction.
     */
    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PointsTransaction::TYPE_PURCHASE,
            'reference_type' => PointsTransaction::REF_PACKAGE_PURCHASE,
        ]);
    }

    /**
     * Create a spend transaction.
     */
    public function spend(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PointsTransaction::TYPE_SPEND,
            'reference_type' => PointsTransaction::REF_COMPETITION,
        ]);
    }

    /**
     * Create an admin credit transaction.
     */
    public function adminCredit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PointsTransaction::TYPE_ADMIN_CREDIT,
            'reference_type' => PointsTransaction::REF_ADMIN_ACTION,
        ]);
    }

    /**
     * Create a refund transaction.
     */
    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PointsTransaction::TYPE_REFUND,
            'reference_type' => PointsTransaction::REF_ADMIN_ACTION,
        ]);
    }
}
