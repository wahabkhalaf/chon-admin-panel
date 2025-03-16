<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\PlayerWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlayerWallet>
 */
class PlayerWalletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PlayerWallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'balance' => fake()->randomFloat(2, 0, 5000),
            'last_updated' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Define a wallet with zero balance.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function empty(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'balance' => 0,
        ]);
    }

    /**
     * Define a wallet with high balance.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function highBalance(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'balance' => fake()->randomFloat(2, 1000, 10000),
        ]);
    }
}