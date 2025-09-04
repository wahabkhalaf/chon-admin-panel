<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestScheduledNotifications extends Command
{
    protected $signature = 'notifications:test-scheduled {--competition-id=}';
    protected $description = 'Test scheduled notifications by creating test notifications';

    public function handle()
    {
        $this->info('🧪 Testing Scheduled Notifications System');
        $this->info('==========================================');

        // Get competition ID from option or find the most recent one
        $competitionId = $this->option('competition-id');
        
        if ($competitionId) {
            $competition = Competition::find($competitionId);
        } else {
            $competition = Competition::latest()->first();
        }

        if (!$competition) {
            $this->error('❌ No competition found. Please create a competition first or specify --competition-id');
            return;
        }

        $this->info("Using competition: {$competition->name} (ID: {$competition->id})");
        $this->info("Start time: {$competition->start_time}");

        // Create test notifications for 1, 5, and 15 minutes from now
        $now = now();
        $oneMinFromNow = $now->copy()->addMinute();
        $fiveMinFromNow = $now->copy()->addMinutes(5);
        $fifteenMinFromNow = $now->copy()->addMinutes(15);

        $this->info("Creating test notifications:");
        $this->info("- 1 minute reminder at: {$oneMinFromNow->format('Y-m-d H:i:s')}");
        $this->info("- 5 minute reminder at: {$fiveMinFromNow->format('Y-m-d H:i:s')}");
        $this->info("- 15 minute reminder at: {$fifteenMinFromNow->format('Y-m-d H:i:s')}");

        // Create 1-minute test notification
        $oneMinNotification = Notification::create([
            'title' => 'TEST: Competition Starting in 1 Minute! 🚨',
            'title_kurdish' => 'تێست: پێشبڕکێ لە ١ خولەکدا دەستپێدەکات! 🚨',
            'message' => "TEST: \"{$competition->name}\" starts in 1 minute! Get ready!",
            'message_kurdish' => "تێست: \"" . ($competition->name_kurdish ?: $competition->name) . "\" لە ١ خولەکدا دەستپێدەکات! ئامادە بە!",
            'type' => 'competition',
            'priority' => 'high',
            'data' => [
                'competitionId' => $competition->id,
                'competitionName' => $competition->name,
                'test' => true,
            ],
            'scheduled_at' => $oneMinFromNow,
            'status' => 'pending',
        ]);

        // Create 5-minute test notification
        $fiveMinNotification = Notification::create([
            'title' => 'TEST: Competition Starting Soon! ⏰',
            'title_kurdish' => 'تێست: بەم دوایە پێشبڕکێ دەستپێدەکات! ⏰',
            'message' => "TEST: \"{$competition->name}\" starts in 5 minutes! Join now!",
            'message_kurdish' => "تێست: \"" . ($competition->name_kurdish ?: $competition->name) . "\" لە ٥ خولەکدا دەستپێدەکات! ئێستا بەشدار ببە!",
            'type' => 'competition',
            'priority' => 'high',
            'data' => [
                'competitionId' => $competition->id,
                'competitionName' => $competition->name,
                'test' => true,
            ],
            'scheduled_at' => $fiveMinFromNow,
            'status' => 'pending',
        ]);

        // Create 15-minute test notification
        $fifteenMinNotification = Notification::create([
            'title' => 'TEST: Competition Starting in 15 Minutes! 🎯',
            'title_kurdish' => 'تێست: پێشبڕکێ لە ١٥ خولەکدا دەستپێدەکات! 🎯',
            'message' => "TEST: \"{$competition->name}\" starts in 15 minutes! Don't miss out!",
            'message_kurdish' => "تێست: \"" . ($competition->name_kurdish ?: $competition->name) . "\" لە ١٥ خولەکدا دەستپێدەکات! لەدەست مەدە!",
            'type' => 'competition',
            'priority' => 'high',
            'data' => [
                'competitionId' => $competition->id,
                'competitionName' => $competition->name,
                'test' => true,
            ],
            'scheduled_at' => $fifteenMinFromNow,
            'status' => 'pending',
        ]);

        $this->info("✅ Test notifications created:");
        $this->info("   - 1-minute notification ID: {$oneMinNotification->id}");
        $this->info("   - 5-minute notification ID: {$fiveMinNotification->id}");
        $this->info("   - 15-minute notification ID: {$fifteenMinNotification->id}");

        $this->info("");
        $this->info("🔄 To process these notifications, run:");
        $this->info("   php artisan notifications:process-scheduled");
        $this->info("");
        $this->info("📊 To check notification status, run:");
        $this->info("   php artisan tinker");
        $this->info("   >>> App\\Models\\Notification::whereIn('id', [{$oneMinNotification->id}, {$fiveMinNotification->id}, {$fifteenMinNotification->id}])->get(['id', 'title', 'status', 'scheduled_at', 'sent_at']);");
        $this->info("");
        $this->info("⏰ The notifications will be sent at their scheduled times if the scheduler is running.");
    }
}
