<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\ExpressApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendScheduledNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function handle(ExpressApiClient $apiClient)
    {
        try {
            $notificationData = [
                'title' => $this->notification->title,
                'title_kurdish' => $this->notification->title_kurdish,
                'message' => $this->notification->message,
                'message_kurdish' => $this->notification->message_kurdish,
                'type' => $this->notification->type,
                'priority' => $this->notification->priority,
                'data' => $this->notification->data ?? [],
            ];

            $result = $apiClient->sendNotification($notificationData);

            $this->notification->update([
                'status' => $result['success'] ? 'sent' : 'failed',
                'api_response' => $result,
                'sent_at' => now(),
            ]);

            if (!$result['success']) {
                Log::error('Failed to send scheduled notification', [
                    'notification_id' => $this->notification->id,
                    'error' => $result['error']
                ]);
            }

        } catch (\Exception $e) {
            $this->notification->update([
                'status' => 'failed',
                'api_response' => ['error' => $e->getMessage()],
            ]);

            Log::error('Exception in scheduled notification job', [
                'notification_id' => $this->notification->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
