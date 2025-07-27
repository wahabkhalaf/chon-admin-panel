<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['general', 'competition', 'announcement', 'maintenance', 'update'];
        $priorities = ['low', 'normal', 'high'];
        $statuses = ['pending', 'sent', 'failed'];

        return [
            'title' => $this->faker->sentence(3),
            'message' => $this->faker->paragraph(2),
            'type' => $this->faker->randomElement($types),
            'priority' => $this->faker->randomElement($priorities),
            'data' => [
                'test' => true,
                'timestamp' => now()->toISOString(),
            ],
            'status' => $this->faker->randomElement($statuses),
            'scheduled_at' => $this->faker->optional()->dateTimeBetween('now', '+1 week'),
            'sent_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'api_response' => $this->faker->optional()->randomElement([
                ['success' => true, 'status_code' => 200],
                ['success' => false, 'error' => 'API timeout', 'status_code' => 500],
            ]),
        ];
    }

    /**
     * Indicate that the notification is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'sent_at' => null,
        ]);
    }

    /**
     * Indicate that the notification is sent.
     */
    public function sent(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
            'api_response' => ['success' => true, 'status_code' => 200],
        ]);
    }

    /**
     * Indicate that the notification failed.
     */
    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'failed',
            'sent_at' => null,
            'api_response' => ['success' => false, 'error' => 'API timeout', 'status_code' => 500],
        ]);
    }

    /**
     * Indicate that the notification is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'scheduled_at' => now()->addMinutes(5),
            'sent_at' => null,
        ]);
    }

    /**
     * Indicate that the notification is ready to send.
     */
    public function readyToSend(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'scheduled_at' => now()->subMinutes(5),
            'sent_at' => null,
        ]);
    }
}
