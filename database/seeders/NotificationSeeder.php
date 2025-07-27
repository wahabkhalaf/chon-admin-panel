<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sent notifications
        Notification::create([
            'title' => 'Welcome to the Platform! 🎉',
            'message' => 'We are excited to have you join our community. Explore new competitions and start winning!',
            'type' => 'general',
            'priority' => 'normal',
            'data' => [
                'welcome_bonus' => 100,
                'featured_competitions' => 3,
                'source' => 'seeder'
            ],
            'scheduled_at' => null,
            'sent_at' => now()->subDays(2),
            'status' => 'sent',
            'api_response' => [
                'success' => true,
                'message' => 'Notification sent successfully to all players',
                'status_code' => 200
            ],
        ]);

        Notification::create([
            'title' => 'New Competition Available! 🏆',
            'message' => '"Quick Quiz" created by Admin - Fast-paced true/false questions that test your speed and knowledge!',
            'type' => 'competition',
            'priority' => 'high',
            'data' => [
                'competitionId' => 1,
                'competitionName' => 'Quick Quiz',
                'entryFee' => '2.00',
                'startTime' => now()->addHours(2)->toISOString(),
                'gameType' => 'true_false',
                'creator' => [
                    'id' => 'admin',
                    'nickname' => 'Admin'
                ],
                'source' => 'seeder'
            ],
            'scheduled_at' => null,
            'sent_at' => now()->subHours(1),
            'status' => 'sent',
            'api_response' => [
                'success' => true,
                'message' => 'Notification sent successfully to all players',
                'status_code' => 200
            ],
        ]);

        Notification::create([
            'title' => 'Upcoming Maintenance Notice 🔧',
            'message' => 'Our services will be temporarily unavailable on August 1st from 2 AM to 4 AM UTC for scheduled maintenance.',
            'type' => 'maintenance',
            'priority' => 'high',
            'data' => [
                'downtime_start' => '2025-08-01 02:00:00',
                'downtime_end' => '2025-08-01 04:00:00',
                'affected_services' => ['competitions', 'payments', 'leaderboards'],
                'source' => 'seeder'
            ],
            'scheduled_at' => null,
            'sent_at' => now()->subDays(1),
            'status' => 'sent',
            'api_response' => [
                'success' => true,
                'message' => 'Notification sent successfully to all players',
                'status_code' => 200
            ],
        ]);

        // Create some pending/scheduled notifications
        Notification::create([
            'title' => 'Weekly Tournament Announcement 🎯',
            'message' => 'Join our weekly tournament starting this Friday! Special prizes for top performers.',
            'type' => 'competition',
            'priority' => 'normal',
            'data' => [
                'tournament_type' => 'weekly',
                'start_date' => now()->addDays(3)->toISOString(),
                'prize_pool' => 500,
                'source' => 'seeder'
            ],
            'scheduled_at' => now()->addDays(2),
            'sent_at' => null,
            'status' => 'pending',
            'api_response' => null,
        ]);

        Notification::create([
            'title' => 'New Features Available! ✨',
            'message' => 'Check out our latest features: improved leaderboards, better payment options, and enhanced game modes!',
            'type' => 'update',
            'priority' => 'normal',
            'data' => [
                'new_features' => [
                    'enhanced_leaderboards',
                    'multiple_payment_methods',
                    'advanced_game_modes'
                ],
                'version' => '2.1.0',
                'source' => 'seeder'
            ],
            'scheduled_at' => now()->addHours(6),
            'sent_at' => null,
            'status' => 'pending',
            'api_response' => null,
        ]);

        // Create some failed notifications for testing
        Notification::create([
            'title' => 'Failed Test Notification ❌',
            'message' => 'This notification failed to send due to API connection issues.',
            'type' => 'general',
            'priority' => 'low',
            'data' => [
                'test_type' => 'failure_simulation',
                'source' => 'seeder'
            ],
            'scheduled_at' => null,
            'sent_at' => null,
            'status' => 'failed',
            'api_response' => [
                'success' => false,
                'error' => 'API connection timeout',
                'status_code' => 500
            ],
        ]);

        // Create some immediate notifications (ready to send)
        Notification::create([
            'title' => 'Flash Sale Alert! 💰',
            'message' => '50% off entry fees for the next hour! Don\'t miss this amazing deal!',
            'type' => 'announcement',
            'priority' => 'high',
            'data' => [
                'discount_percentage' => 50,
                'valid_until' => now()->addHour()->toISOString(),
                'source' => 'seeder'
            ],
            'scheduled_at' => now()->subMinutes(5), // Past time, ready to send
            'sent_at' => null,
            'status' => 'pending',
            'api_response' => null,
        ]);

        // Create some using the factory
        Notification::factory()->count(5)->create();

        $this->command->info('✅ NotificationSeeder completed successfully!');
        $this->command->info('Created 8 specific notifications + 5 factory notifications = 13 total notifications');
    }
}
