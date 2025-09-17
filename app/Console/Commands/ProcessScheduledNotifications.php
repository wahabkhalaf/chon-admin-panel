<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\FcmNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessScheduledNotifications extends Command
{
    protected $signature = 'notifications:process-scheduled';
    protected $description = 'Process and send scheduled notifications';

    protected FcmNotificationService $fcmService;

    public function __construct(FcmNotificationService $fcmService)
    {
        parent::__construct();
        $this->fcmService = $fcmService;
    }

    public function handle()
    {
        // Check if auto notifications are enabled
        if (!config('app.auto_notifications', true)) {
            $this->info('Auto notifications are disabled. Skipping scheduled notification processing.');
            return;
        }

        $this->info('Processing scheduled notifications...');
        $now = now();
        $scheduledNotifications = Notification::where('status', 'pending')
            ->where('scheduled_at', '<=', $now)
            ->where('scheduled_at', '>=', $now->copy()->subMinute())
            ->whereNotNull('scheduled_at')
            ->get();

        if ($scheduledNotifications->isEmpty()) {
            $this->info('No scheduled notifications to process.');
            return;
        }

        $this->info("Found {$scheduledNotifications->count()} scheduled notifications to process.");

        foreach ($scheduledNotifications as $notification) {
            try {
                $this->info("Processing notification ID: {$notification->id} - {$notification->title}");

                $notificationData = [
                    'title' => $notification->title,
                    'title_kurdish' => $notification->title_kurdish,
                    'message' => $notification->message,
                    'message_kurdish' => $notification->message_kurdish,
                    'type' => $notification->type,
                    'priority' => $notification->priority,
                    'data' => $notification->data,
                ];

                $result = $this->fcmService->sendBroadcastNotification($notificationData);

                $notification->update([
                    'status' => $result['success'] ? 'sent' : 'failed',
                    'api_response' => $result,
                    'sent_at' => now(),
                ]);

                if ($result['success']) {
                    $this->info("✓ Notification sent successfully");
                } else {
                    $this->error("✗ Failed to send notification: " . json_encode($result));
                }

            } catch (\Exception $e) {
                $this->error("Error processing notification ID {$notification->id}: " . $e->getMessage());
                
                $notification->update([
                    'status' => 'failed',
                    'api_response' => ['error' => $e->getMessage()],
                ]);
            }
        }

        $this->info('Finished processing scheduled notifications.');
    }
    
}
