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
        // Check if Firebase packages are installed
        if (!class_exists('Kreait\Firebase\Contract\Messaging')) {
            $this->error('❌ Firebase packages not installed!');
            $this->info('Run: composer require kreait/laravel-firebase');
            return;
        }
        
        // Check if FCM service exists
        if (!class_exists('App\Services\FcmNotificationService')) {
            $this->error('❌ FCM service not found!');
            $this->info('Check if FcmNotificationService.php exists');
            return;
        }
        
        // Check Firebase configuration
        if (!file_exists(config_path('firebase.php'))) {
            $this->error('❌ Firebase config not found!');
            $this->info('Run: php artisan vendor:publish --provider="Kreait\Laravel\Firebase\ServiceProvider" --tag=config');
            return;
        }
        
        // Check Firebase credentials
        $credentialsPath = storage_path('app/firebase/firebase-service-account.json');
        if (!file_exists($credentialsPath)) {
            $this->error('❌ Firebase credentials not found!');
            $this->info('Place firebase-service-account.json in: storage/app/firebase/');
            return;
        }
        
        $this->info('✅ Firebase packages installed');
        $this->info('✅ FCM service found');
        $this->info('✅ Firebase config exists');
        $this->info('✅ Firebase credentials found');
        
        // Try to create FCM service
        $this->info('🔄 Testing FCM service creation...');
        $fcmService = app(\App\Services\FcmNotificationService::class);
        $this->info('✅ FCM service created successfully');
        
        // Test connection
        $this->info('🔄 Testing Firebase connection...');
        $result = $fcmService->testConnection();
        
        if ($result['success']) {
            $this->info('✅ Firebase connection successful!');
            $this->info('Message ID: ' . ($result['message_id'] ?? 'N/A'));
        } else {
            $this->error('❌ Firebase connection failed: ' . $result['error']);
        }
        
    } catch (\Exception $e) {
        $this->error('❌ Error: ' . $e->getMessage());
        $this->error('Stack trace: ' . $e->getTraceAsString());
    }
})->purpose('Test FCM notification system');
