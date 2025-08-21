<?php

namespace Database\Factories;

use App\Models\AppVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppVersion>
 */
class AppVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AppVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $platform = $this->faker->randomElement(['ios', 'android']);
        $buildNumber = $this->faker->numberBetween(1, 100);
        
        return [
            'platform' => $platform,
            'version' => $this->faker->semver(),
            'build_number' => $buildNumber,
            'app_store_url' => $platform === 'ios' 
                ? 'https://apps.apple.com/app/id123456789'
                : 'https://play.google.com/store/apps/details?id=com.chon.app',
            'release_notes' => $this->faker->paragraph(),
            'is_force_update' => $this->faker->boolean(20), // 20% chance of force update
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'released_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the version is for iOS.
     */
    public function ios(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'ios',
            'app_store_url' => 'https://apps.apple.com/app/id123456789',
        ]);
    }

    /**
     * Indicate that the version is for Android.
     */
    public function android(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'android',
            'app_store_url' => 'https://play.google.com/store/apps/details?id=com.chon.app',
        ]);
    }

    /**
     * Indicate that the version is a force update.
     */
    public function forceUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_force_update' => true,
        ]);
    }

    /**
     * Indicate that the version is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
