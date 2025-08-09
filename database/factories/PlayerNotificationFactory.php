<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Player;
use App\Models\PlayerNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlayerNotification>
 */
class PlayerNotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PlayerNotification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'notification_id' => Notification::factory(),
            'received_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'read_at' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'delivery_data' => [
                'delivered' => true,
                'timestamp' => now()->toISOString(),
                'channel' => 'websocket',
            ],
        ];
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the notification is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the notification was received recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'received_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the notification delivery failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_data' => [
                'delivered' => false,
                'error' => 'Connection timeout',
                'timestamp' => now()->toISOString(),
                'channel' => 'websocket',
            ],
        ]);
    }
}
