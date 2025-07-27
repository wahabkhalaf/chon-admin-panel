<?php

namespace Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Player>
 */
class PlayerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Player::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'whatsapp_number' => '+' . fake()->unique()->numerify('##########'),
            'nickname' => fake()->userName(),
            'total_score' => fake()->numberBetween(0, 10000),
            'level' => fake()->numberBetween(1, 50),
            'experience_points' => fake()->numberBetween(0, 5000),
            'is_verified' => fake()->boolean(80), // 80% chance of being verified
            'language' => fake()->randomElement(['en', 'ku', 'ar']), // English, Kurdish, Arabic
            'joined_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Define the model's newbie state.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function newbie(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'total_score' => 0,
            'level' => 1,
            'experience_points' => 0,
            'joined_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Define the model's advanced state.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function advanced(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'total_score' => fake()->numberBetween(5000, 15000),
            'level' => fake()->numberBetween(20, 50),
            'experience_points' => fake()->numberBetween(2000, 5000),
            'joined_at' => fake()->dateTimeBetween('-1 year', '-3 months'),
        ]);
    }
}