<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\TestFcmNotification;
use Illuminate\Support\Facades\Http; // Added for Http facade

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Test FCM connection
Artisan::command('fcm:test', function () {
    $this->info('🧪 Testing FCM Connection...');
    
    try {
        // Check if Firebase packages are installed
        $firebaseClasses = [
            'Kreait\Firebase\Contract\Messaging',
            'Kreait\Firebase\Messaging',
            'Kreait\Firebase\Messaging\CloudMessage',
            'Kreait\Firebase\Messaging\Notification'
        ];
        
        $foundClasses = [];
        foreach ($firebaseClasses as $class) {
            if (class_exists($class)) {
                $foundClasses[] = $class;
            }
        }
        
        if (empty($foundClasses)) {
            $this->error('❌ Firebase packages not installed!');
            $this->info('Run: composer require kreait/laravel-firebase');
            return;
        }
        
        $this->info('✅ Firebase classes found: ' . implode(', ', $foundClasses));
        
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
        
        try {
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
            $this->error('❌ FCM service error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
        }
        
    } catch (\Exception $e) {
        $this->error('❌ Error: ' . $e->getMessage());
        $this->error('Stack trace: ' . $e->getTraceAsString());
    }
})->purpose('Test FCM notification system');

// Test App Update API
Artisan::command('app-update:test', function () {
    $this->info('🧪 Testing App Update API...');
    
    try {
        // Test 1: Check for updates with old version
        $this->info('Test 1: Checking for updates with old version...');
        $response = Http::post('http://localhost:8000/api/app-updates/check', [
            'platform' => 'android',
            'current_version' => '0.9.0',
            'current_build_number' => 0,
            'app_version' => '0.9.0',
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            $this->info('✅ Update check successful');
            $this->line("Response: " . json_encode($data, JSON_PRETTY_PRINT));
        } else {
            $this->error('❌ Update check failed: ' . $response->status());
        }
        
        // Test 2: Check for updates with current version
        $this->info('Test 2: Checking for updates with current version...');
        $response = Http::post('http://localhost:8000/api/app-updates/check', [
            'platform' => 'android',
            'current_version' => '1.0.0',
            'current_build_number' => 1,
            'app_version' => '1.0.0',
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            $this->info('✅ Current version check successful');
            $this->line("Response: " . json_encode($data, JSON_PRETTY_PRINT));
        } else {
            $this->error('❌ Current version check failed: ' . $response->status());
        }
        
        // Test 3: Check iOS platform
        $this->info('Test 3: Checking iOS platform...');
        $response = Http::post('http://localhost:8000/api/app-updates/check', [
            'platform' => 'ios',
            'current_version' => '0.9.0',
            'current_build_number' => 0,
            'app_version' => '0.9.0',
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            $this->info('✅ iOS check successful');
            $this->line("Response: " . json_encode($data, JSON_PRETTY_PRINT));
        } else {
            $this->error('❌ iOS check failed: ' . $response->status());
        }
        
        $this->info('🎉 App Update API test completed!');
        
    } catch (\Exception $e) {
        $this->error('❌ Test failed: ' . $e->getMessage());
    }
})->purpose('Test the App Update API endpoints');
