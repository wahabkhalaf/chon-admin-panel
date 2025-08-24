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
        $this->info('Processing scheduled notifications...');

        // Get notifications that are scheduled to be sent now or in the past
        $scheduledNotifications = Notification::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
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

                // Prepare notification data for FCM
                $notificationData = [
                    'title' => $notification->title,
                    'title_kurdish' => $notification->title_kurdish,
                    'message' => $notification->message,
                    'message_kurdish' => $notification->message_kurdish,
                    'type' => $notification->type,
                    'priority' => $notification->priority,
                    'data' => $notification->data,
                ];

                // Send via FCM
                $result = $this->fcmService->sendBroadcastNotification($notificationData);

                // Update notification status
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
                
                // Mark as failed
                $notification->update([
                    'status' => 'failed',
                    'api_response' => ['error' => $e->getMessage()],
                ]);
            }
        }

        $this->info('Finished processing scheduled notifications.');
    }
}
