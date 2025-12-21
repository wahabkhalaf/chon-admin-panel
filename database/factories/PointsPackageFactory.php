<?php

namespace Database\Factories;

use App\Models\PointsPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PointsPackage>
 */
class PointsPackageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PointsPackage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pointsOptions = [100, 250, 500, 1000, 2500, 5000, 10000];
        $points = $this->faker->randomElement($pointsOptions);

        return [
            'name' => $points . ' Points Package',
            'description' => $this->faker->optional()->sentence(),
            'points_amount' => $points,
            'price_iqd' => $points * $this->faker->numberBetween(8, 12),
            'active' => $this->faker->boolean(90),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the package is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the package is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Create a small package.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Small Package',
            'points_amount' => 100,
            'price_iqd' => 1000,
            'sort_order' => 1,
        ]);
    }

    /**
     * Create a medium package.
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Medium Package',
            'points_amount' => 500,
            'price_iqd' => 4500,
            'sort_order' => 2,
        ]);
    }

    /**
     * Create a large package.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Large Package',
            'points_amount' => 1000,
            'price_iqd' => 8000,
            'sort_order' => 3,
        ]);
    }
}
