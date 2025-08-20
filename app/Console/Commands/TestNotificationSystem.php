<?php

namespace App\Console\Commands;

use App\Services\FcmNotificationService;
use Illuminate\Console\Command;

class TestNotificationSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the notification system with FCM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing FCM Notification Service...');

        try {
            $fcmService = app(FcmNotificationService::class);

            $this->info('Sending test notification...');

            $result = $fcmService->sendNotification([
                'title' => 'Test from Laravel',
                'message' => 'This notification was sent from Laravel admin panel at ' . now()->format('Y-m-d H:i:s'),
                'type' => 'general',
                'priority' => 'normal',
                'data' => [
                    'test' => true,
                    'timestamp' => now()->toISOString(),
                    'source' => 'laravel-admin'
                ]
            ]);

            $this->info('Result:');
            $this->line(json_encode($result, JSON_PRETTY_PRINT));

            if ($result['success']) {
                $this->info('✅ SUCCESS: Notification sent successfully!');
                return 0;
            } else {
                $this->error('❌ FAILED: ' . ($result['error'] ?? 'Unknown error'));
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ ERROR: ' . $e->getMessage());
            return 1;
        }
    }
}
