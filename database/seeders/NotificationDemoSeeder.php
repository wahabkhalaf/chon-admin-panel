<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Notification;
use App\Models\Player;
use App\Services\FcmNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class NotificationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding demo data and sending notifications...');

        // 0) Ensure we have some players to send to
        if (Player::count() === 0) {
            $this->command?->warn('No players found. Creating 3 demo players...');
            Player::create([
                'whatsapp_number' => '+10000000001',
                'nickname' => 'Demo One',
                'total_score' => 0,
                'level' => 1,
                'experience_points' => 0,
                'is_verified' => true,
                'language' => 'en',
            ]);
            Player::create([
                'whatsapp_number' => '+10000000002',
                'nickname' => 'Demo Two',
                'total_score' => 0,
                'level' => 1,
                'experience_points' => 0,
                'is_verified' => true,
                'language' => 'ku',
            ]);
            Player::create([
                'whatsapp_number' => '+10000000003',
                'nickname' => 'Demo Three',
                'total_score' => 0,
                'level' => 1,
                'experience_points' => 0,
                'is_verified' => true,
                'language' => 'en',
            ]);
        }

        // 1) Create a bilingual competition (Observer will broadcast now + schedule T-5m reminder)
        $start = Carbon::now()->addMinutes(10);
        $open = Carbon::now();

        $competition = Competition::create([
            'name' => 'Demo Cup',
            'name_kurdish' => 'کۆپا دێمو',
            'description' => 'Quick demo competition',
            'description_kurdish' => 'پێشبڕکێکی نمونەیی بۆ تاقیکردنەوە',
            'entry_fee' => 0,
            'open_time' => $open,
            'start_time' => $start,
            'end_time' => $start->copy()->addMinutes(30),
            'max_users' => 1000,
            'game_type' => 'trivia',
        ]);
        $this->command?->info("Created competition '{$competition->name}' (observer will send now + schedule reminder)");

        // 2) Send a bilingual broadcast notification immediately
        $fcmService = app(FcmNotificationService::class);
        $broadcastPayload = [
            'title' => 'Welcome to Chon',
            'title_kurdish' => 'بەخێربێن بۆ Chôn',
            'message' => 'This is a bilingual broadcast to all players.',
            'message_kurdish' => 'ئەمە پەیامێکی دوو زمانە بۆ هەموو یاریزانان.',
            'type' => 'announcement',
            'priority' => 'normal',
            'data' => [
                'cta' => 'open_app',
            ],
        ];
        // Send using direct FCM broadcast
        $result = $fcmService->sendBroadcastNotification($broadcastPayload);
        Notification::create([
            'title' => $broadcastPayload['title'],
            'title_kurdish' => $broadcastPayload['title_kurdish'],
            'message' => $broadcastPayload['message'],
            'message_kurdish' => $broadcastPayload['message_kurdish'],
            'type' => $broadcastPayload['type'],
            'priority' => $broadcastPayload['priority'],
            'data' => $broadcastPayload['data'],
            'status' => $result['success'] ? 'sent' : 'failed',
            'api_response' => $result,
            'sent_at' => now(),
        ]);
        $this->command?->info('Broadcast-now notification sent via direct FCM: ' . json_encode($result));

        // 3) Create a bilingual scheduled notification for +5 minutes (process via command)
        $scheduled = Notification::create([
            'title' => 'Starting Soon',
            'title_kurdish' => 'بەم دوایە دەستپێدەکات',
            'message' => 'A scheduled notification that will be sent shortly.',
            'message_kurdish' => 'پەیامێکی خشتەکراو کە لە ماوەیەکی کەمدا دەگات.',
            'type' => 'general',
            'priority' => 'low',
            'data' => ['demo' => true],
            'scheduled_at' => Carbon::now()->addMinutes(5),
            'status' => 'pending',
        ]);
        $this->command?->info('Created scheduled notification for +5 minutes (run: artisan notifications:process-scheduled)');
    }
}


