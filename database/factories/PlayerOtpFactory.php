<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\PlayerOtp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlayerOtp>
 */
class PlayerOtpFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PlayerOtp::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'otp_code' => (string) fake()->numberBetween(100000, 999999),
            'purpose' => fake()->randomElement(['login', 'registration', 'verification']),
            'is_verified' => false,
            'expires_at' => fake()->dateTimeBetween('now', '+30 minutes'),
            'created_at' => now(),
        ];
    }

    /**
     * Define the model's verified state.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function verified(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Define the model's expired state.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function expired(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 day', '-1 minute'),
        ]);
    }

    /**
     * Define the model's login purpose state.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forLogin(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'purpose' => 'login',
        ]);
    }

    /**
     * Define the model's registration purpose state.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forRegistration(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'purpose' => 'registration',
        ]);
    }
}