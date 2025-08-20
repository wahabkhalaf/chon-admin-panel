<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\TestFcmNotification;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Test FCM connection
Artisan::command('fcm:test', function () {
    $this->info('🧪 Testing FCM Connection...');
    
    try {
        $fcmService = app(\App\Services\FcmNotificationService::class);
        $result = $fcmService->testConnection();
        
        if ($result['success']) {
            $this->info('✅ Firebase connection successful!');
            $this->info('Message ID: ' . ($result['message_id'] ?? 'N/A'));
        } else {
            $this->error('❌ Firebase connection failed: ' . $result['error']);
        }
        
    } catch (\Exception $e) {
        $this->error('❌ Error: ' . $e->getMessage());
    }
})->purpose('Test FCM notification system');
