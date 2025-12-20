<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Player;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreatePlayerNotificationRecords implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $notificationId,
        public ?array $targetUserIds = null,
        public int $chunkSize = 500
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $notification = Notification::findOrFail($this->notificationId);
            $now = now();
            $totalProcessed = 0;

            Log::info('Starting PlayerNotification creation job', [
                'notification_id' => $this->notificationId,
                'target_user_ids' => $this->targetUserIds,
                'chunk_size' => $this->chunkSize
            ]);

            // Build query
            $query = Player::query();
            
            if ($this->targetUserIds !== null && !empty($this->targetUserIds)) {
                $query->whereIn('id', $this->targetUserIds);
            }

            // Process in chunks to avoid memory issues
            $query->chunk($this->chunkSize, function ($players) use ($notification, $now, &$totalProcessed) {
                $insertData = [];

                foreach ($players as $player) {
                    $insertData[] = [
                        'player_id' => $player->id,
                        'notification_id' => $notification->id,
                        'received_at' => $now,
                        'read_at' => null,
                        'delivery_data' => json_encode([
                            'sent_via' => 'fcm',
                            'sent_at' => $now->toISOString(),
                            'queued' => true,
                        ]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($insertData)) {
                    DB::table('player_notifications')->insert($insertData);
                    $totalProcessed += count($insertData);
                    
                    Log::info('PlayerNotification chunk processed', [
                        'notification_id' => $notification->id,
                        'chunk_count' => count($insertData),
                        'total_processed' => $totalProcessed
                    ]);
                }
            });

            Log::info('PlayerNotification creation job completed', [
                'notification_id' => $this->notificationId,
                'total_processed' => $totalProcessed
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create PlayerNotification records in job', [
                'notification_id' => $this->notificationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }
}
