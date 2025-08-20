<?php

namespace App\Console\Commands;

use App\Services\FcmNotificationService;
use Illuminate\Console\Command;

class TestFcmNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:test {--title=Test Title} {--message=Test Message} {--token=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test FCM notification system';

    /**
     * Execute the console command.
     */
    public function handle(FcmNotificationService $fcmService)
    {
        $this->info('🧪 Testing FCM Notification System...');

        // Test connection first
        $this->info('1. Testing Firebase connection...');
        $connectionResult = $fcmService->testConnection();
        
        if ($connectionResult['success']) {
            $this->info('✅ Firebase connection successful!');
        } else {
            $this->error('❌ Firebase connection failed: ' . $connectionResult['error']);
            return 1;
        }

        // Test notification sending
        $this->info('2. Testing notification sending...');
        
        $title = $this->option('title');
        $message = $this->option('message');
        $token = $this->option('token');

        $notificationData = [
            'title' => $title,
            'title_kurdish' => 'تاقیکردنەوەی نوتیفیکەیشن',
            'message' => $message,
            'message_kurdish' => 'ئەمە تاقیکردنەوەیەکە',
            'type' => 'test',
            'priority' => 'normal',
            'data' => [
                'test' => true,
                'timestamp' => now()->toISOString()
            ]
        ];

        if ($token) {
            // Send to specific token
            $this->info("Sending to specific FCM token: {$token}");
            $result = $fcmService->sendNotification($notificationData, [$token]);
        } else {
            // Send to topic (broadcast)
            $this->info('Sending to topic: all_users');
            $result = $fcmService->sendNotification($notificationData);
        }

        if ($result['success']) {
            $this->info('✅ Notification sent successfully!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Status', 'Success'],
                    ['Total Sent', $result['data']['total_sent'] ?? 'N/A'],
                    ['Total Failed', $result['data']['total_failed'] ?? 'N/A'],
                    ['Message ID', $result['data']['message_id'] ?? 'N/A']
                ]
            );
        } else {
            $this->error('❌ Notification failed: ' . $result['error']);
            return 1;
        }

        $this->info('🎉 FCM test completed successfully!');
        return 0;
    }
}
