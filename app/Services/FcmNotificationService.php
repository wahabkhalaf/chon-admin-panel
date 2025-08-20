<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Contract\Messaging;

class FcmNotificationService
{
    protected Messaging $messaging;
    protected string $projectId;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
        $this->projectId = 'chon-1114a'; // From your service account
    }

    /**
     * Send notification to specific players by their FCM tokens
     */
    public function sendNotification(array $notificationData, array $fcmTokens = []): array
    {
        try {
            // Log the incoming data for debugging
            \Log::info('FCM sendNotification called', [
                'notification_data' => $notificationData,
                'fcm_tokens_count' => count($fcmTokens),
                'fcm_tokens' => $fcmTokens, // Log actual tokens for debugging
                'notification_id' => $notificationData['id'] ?? 'unknown',
                'data_types' => [
                    'title' => gettype($notificationData['title'] ?? ''),
                    'message' => gettype($notificationData['message'] ?? ''),
                    'data' => gettype($notificationData['data'] ?? [])
                ]
            ]);

            // Validate and clean notification data before processing
            $cleanedNotificationData = $this->validateAndCleanNotificationData($notificationData);

            // If we have specific tokens, send to those only
            // If no tokens, send to topic (but be careful about duplicates)
            if (empty($fcmTokens)) {
                \Log::info('No FCM tokens provided, sending to topic');
                return $this->sendToTopic($cleanedNotificationData);
            }

            // Send to specific tokens only (not to topic to avoid duplicates)
            \Log::info('Sending to specific FCM tokens only');
            
            // Remove duplicate tokens to prevent multiple notifications
            $uniqueTokens = array_unique($fcmTokens);
            if (count($uniqueTokens) !== count($fcmTokens)) {
                \Log::info('Removed duplicate FCM tokens', [
                    'original_count' => count($fcmTokens),
                    'unique_count' => count($uniqueTokens)
                ]);
            }
            
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($uniqueTokens as $token) {
                try {
                    $result = $this->sendToToken($cleanedNotificationData, $token);
                    $results[] = $result;

                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }
                } catch (\Exception $e) {
                    \Log::error('Error sending to token', [
                        'token' => $token,
                        'error' => $e->getMessage()
                    ]);
                    $results[] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'token' => $token
                    ];
                    $failureCount++;
                }
            }

            \Log::info('FCM notification sent to tokens', [
                'total' => count($fcmTokens),
                'success' => $successCount,
                'failed' => $failureCount,
                'results' => $results
            ]);

            return [
                'success' => $successCount > 0,
                'data' => [
                    'total_sent' => $successCount,
                    'total_failed' => $failureCount,
                    'results' => $results
                ],
                'status_code' => 200
            ];

        } catch (\Exception $e) {
            \Log::error('Failed to send FCM notification', [
                'notification_data' => $notificationData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'FCM Error: ' . (string) $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Send notification to a specific FCM token
     */
    protected function sendToToken(array $notificationData, string $token): array
    {
        try {
            \Log::info('Building FCM message for token', [
                'token' => $token,
                'notification_data' => $notificationData
            ]);

            $message = $this->buildMessage($notificationData);
            $message = $message->toToken($token);

            \Log::info('Sending FCM message', [
                'message_class' => get_class($message)
            ]);

            $result = $this->messaging->send($message);

            \Log::info('FCM send result', [
                'result' => $result,
                'result_type' => gettype($result),
                'result_class' => is_object($result) ? get_class($result) : 'not_object'
            ]);

            // Ensure result is a string
            $messageId = is_string($result) ? $result : (string) $result;

            return [
                'success' => true,
                'message_id' => $messageId,
                'token' => $token
            ];

        } catch (\Exception $e) {
            \Log::error('Failed to send FCM notification to token', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Token Error: ' . (string) $e->getMessage(),
                'token' => $token
            ];
        }
    }

    /**
     * Send notification to a topic (broadcast)
     */
    protected function sendToTopic(array $notificationData): array
    {
        try {
            \Log::info('Sending to topic: all_users', [
                'title' => $notificationData['title'] ?? 'NO_TITLE',
                'message' => $notificationData['message'] ?? 'NO_MESSAGE',
                'data_types' => [
                    'title' => gettype($notificationData['title'] ?? ''),
                    'message' => gettype($notificationData['message'] ?? ''),
                    'data' => gettype($notificationData['data'] ?? [])
                ]
            ]);

            // Validate notification data before building message
            if (empty($notificationData['title']) || empty($notificationData['message'])) {
                throw new \Exception('Title and message are required for notifications');
            }

            // Build the message with strict validation
            $message = $this->buildMessage($notificationData);
            
            // Set topic
            $message = $message->toTopic('all_users');

            \Log::info('Sending FCM message to topic', [
                'message_class' => get_class($message)
            ]);

            $result = $this->messaging->send($message);

            \Log::info('FCM send result for topic', [
                'result' => $result,
                'result_type' => gettype($result),
                'result_class' => is_object($result) ? get_class($result) : 'not_object'
            ]);

            // Ensure result is a string
            $messageId = is_string($result) ? $result : (string) $result;

            \Log::info('FCM notification sent to topic', [
                'topic' => 'all_users',
                'message_id' => $messageId
            ]);

            return [
                'success' => true,
                'data' => [
                    'message_id' => $messageId,
                    'topic' => 'all_users'
                ],
                'status_code' => 200
            ];

        } catch (\Exception $e) {
            \Log::error('Failed to send FCM notification to topic', [
                'topic' => 'all_users',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'notification_data' => $notificationData
            ]);

            return [
                'success' => false,
                'error' => 'Topic Error: ' . (string) $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Build FCM message with proper configuration
     */
    protected function buildMessage(array $notificationData): CloudMessage
    {
        try {
            // Create a very simple message - no complex configs that could cause issues
            $message = CloudMessage::new();

            // Ensure title and message are strings - be very strict about this
            $title = $notificationData['title'] ?? '';
            $messageText = $notificationData['message'] ?? '';
            
            // Force convert everything to string with better error handling
            $title = $this->ensureString($title, 'title');
            $messageText = $this->ensureString($messageText, 'message');

            // Create basic notification only
            $notification = Notification::create($title, $messageText);
            $message = $message->withNotification($notification);

            // Only add data if it's simple and safe
            if (!empty($notificationData['data']) && is_array($notificationData['data'])) {
                $safeData = $this->cleanDataForFcm($notificationData['data']);
                if (!empty($safeData)) {
                    $message = $message->withData($safeData);
                }
            }

            return $message;
            
        } catch (\Exception $e) {
            \Log::error('Error building FCM message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'notification_data' => $notificationData
            ]);
            throw $e;
        }
    }

    /**
     * Validate and clean notification data to prevent array to string conversion errors
     */
    private function validateAndCleanNotificationData(array $notificationData): array
    {
        $cleaned = [];
        
        // Clean title
        $cleaned['title'] = $this->ensureString($notificationData['title'] ?? '', 'title');
        
        // Clean message
        $cleaned['message'] = $this->ensureString($notificationData['message'] ?? '', 'message');
        
        // Clean type
        $cleaned['type'] = $this->ensureString($notificationData['type'] ?? 'general', 'type');
        
        // Clean priority
        $cleaned['priority'] = $this->ensureString($notificationData['priority'] ?? 'normal', 'priority');
        
        // Clean data
        if (!empty($notificationData['data']) && is_array($notificationData['data'])) {
            $cleaned['data'] = $this->cleanDataForFcm($notificationData['data']);
        } else {
            $cleaned['data'] = [];
        }
        
        // Copy any other fields that might be present
        foreach ($notificationData as $key => $value) {
            if (!isset($cleaned[$key])) {
                $cleaned[$key] = $this->ensureString($value, $key);
            }
        }
        
        \Log::info('Notification data cleaned for FCM', [
            'original' => $notificationData,
            'cleaned' => $cleaned
        ]);
        
        return $cleaned;
    }

    /**
     * Ensure a value is a string, converting arrays and objects to JSON
     */
    private function ensureString($value, string $fieldName): string
    {
        if (is_string($value)) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        if (is_array($value)) {
            $jsonString = json_encode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $jsonString;
            } else {
                \Log::warning("Failed to convert array to JSON for field: {$fieldName}", [
                    'value' => $value,
                    'json_error' => json_last_error_msg()
                ]);
                return '[' . implode(', ', array_map('strval', $value)) . ']';
            }
        }
        
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            
            $jsonString = json_encode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $jsonString;
            } else {
                \Log::warning("Failed to convert object to JSON for field: {$fieldName}", [
                    'value' => $value,
                    'json_error' => json_last_error_msg()
                ]);
                return get_class($value);
            }
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_null($value)) {
            return '';
        }
        
        // Fallback for any other type
        return (string) $value;
    }

    /**
     * Clean data for FCM - ensure all values are strings
     */
    private function cleanDataForFcm(array $data): array
    {
        $cleanData = [];
        foreach ($data as $key => $value) {
            try {
                $cleanData[$key] = $this->ensureString($value, $key);
            } catch (\Exception $e) {
                \Log::warning("Failed to clean data for key: {$key}", [
                    'value' => $value,
                    'error' => $e->getMessage()
                ]);
                // Skip this key if we can't convert it
                continue;
            }
        }
        return $cleanData;
    }

    /**
     * Convert priority to Android priority
     */
    protected function getAndroidPriority(string $priority): string
    {
        return match ($priority) {
            'urgent', 'high' => 'high',
            'normal' => 'normal',
            'low' => 'low',
            default => 'normal'
        };
    }

    /**
     * Test connection to Firebase
     */
    public function testConnection(): array
    {
        try {
            // Test if messaging service is accessible
            if (!$this->messaging) {
                throw new \Exception('Messaging service not initialized');
            }

            // Test if we can build a simple message
            try {
                $testData = [
                    'title' => 'Test',
                    'message' => 'Test message',
                    'type' => 'general',
                    'priority' => 'normal'
                ];
                
                $testMessage = $this->buildMessage($testData);
                
                \Log::info('Test message built successfully', [
                    'message_class' => get_class($testMessage)
                ]);
                
            } catch (\Exception $e) {
                \Log::error('Error building test message', [
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to build test message: ' . $e->getMessage());
            }

            // Try to get project info
            $projectId = $this->projectId;
            
            return [
                'success' => true,
                'message' => 'Firebase connection successful - service accessible and message building works',
                'project_id' => $projectId,
                'status_code' => 200
            ];

        } catch (\Exception $e) {
            \Log::error('Firebase test connection error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Test Error: ' . (string) $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Send broadcast notification to topic
     */
    public function sendBroadcastNotification(array $notificationData): array
    {
        try {
            \Log::info('Sending broadcast notification to topic only');
            
            // Clean the notification data before sending
            $cleanedNotificationData = $this->validateAndCleanNotificationData($notificationData);
            
            return $this->sendToTopic($cleanedNotificationData);
        } catch (\Exception $e) {
            \Log::error('Failed to send broadcast notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'notification_data' => $notificationData
            ]);

            return [
                'success' => false,
                'error' => (string) $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Subscribe user to topic
     */
    public function subscribeToTopic(array $tokens, string $topic): array
    {
        try {
            // Remove duplicate tokens before subscribing
            $uniqueTokens = array_unique($tokens);
            if (count($uniqueTokens) !== count($tokens)) {
                \Log::warning('Duplicate tokens detected during topic subscription', [
                    'topic' => $topic,
                    'original_count' => count($tokens),
                    'unique_count' => count($uniqueTokens)
                ]);
            }
            
            \Log::info('Subscribing to topic', [
                'topic' => $topic,
                'token_count' => count($uniqueTokens)
            ]);
            
            $result = $this->messaging->subscribeToTopic($topic, $uniqueTokens);

            \Log::info('Successfully subscribed to topic', [
                'topic' => $topic,
                'result' => $result
            ]);

            return [
                'success' => true,
                'data' => $result,
                'status_code' => 200
            ];

        } catch (\Exception $e) {
            \Log::error('Failed to subscribe to topic', [
                'topic' => $topic,
                'tokens' => $tokens,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Subscribe Error: ' . (string) $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Unsubscribe user from topic
     */
    public function unsubscribeFromTopic(array $tokens, string $topic): array
    {
        try {
            // Remove duplicate tokens before unsubscribing
            $uniqueTokens = array_unique($tokens);
            if (count($uniqueTokens) !== count($tokens)) {
                \Log::warning('Duplicate tokens detected during topic unsubscription', [
                    'topic' => $topic,
                    'original_count' => count($tokens),
                    'unique_count' => count($uniqueTokens)
                ]);
            }
            
            \Log::info('Unsubscribing from topic', [
                'topic' => $topic,
                'token_count' => count($uniqueTokens)
            ]);
            
            $result = $this->messaging->unsubscribeFromTopic($topic, $uniqueTokens);

            \Log::info('Successfully unsubscribed from topic', [
                'topic' => $topic,
                'result' => $result
            ]);

            return [
                'success' => true,
                'data' => $result,
                'status_code' => 200
            ];

        } catch (\Exception $e) {
            \Log::error('Failed to unsubscribe from topic', [
                'topic' => $topic,
                'tokens' => $tokens,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Unsubscribe Error: ' . (string) $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Debug method to check FCM service status
     */
    public function getDebugInfo(): array
    {
        return [
            'project_id' => $this->projectId,
            'messaging_service' => $this->messaging ? get_class($this->messaging) : 'null',
            'service_working' => $this->messaging !== null
        ];
    }

    /**
     * Test method to send a simple notification without complex data
     */
    public function testSimpleNotification(): array
    {
        try {
            $testData = [
                'title' => 'Test Notification',
                'message' => 'This is a test notification',
                'type' => 'general',
                'priority' => 'normal'
            ];
            
            \Log::info('Testing simple notification', $testData);
            
            // Try to build the message first
            $message = $this->buildMessage($testData);
            
            \Log::info('Message built successfully', [
                'message_class' => get_class($message)
            ]);
            
            return [
                'success' => true,
                'message' => 'Simple notification test passed',
                'data' => $testData
            ];
            
        } catch (\Exception $e) {
            \Log::error('Simple notification test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Test Failed: ' . (string) $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Test method to just build a message without sending
     */
    public function testBuildMessage(): array
    {
        try {
            $testData = [
                'title' => 'Test Build',
                'message' => 'Testing message building',
                'data' => [
                    'key1' => 'value1',
                    'key2' => 'value2'
                ]
            ];
            
            \Log::info('Testing message building', $testData);
            
            $message = $this->buildMessage($testData);
            
            \Log::info('Message built successfully', [
                'message_class' => get_class($message),
                'message_object' => $message
            ]);
            
            return [
                'success' => true,
                'message' => 'Message building test passed',
                'message_class' => get_class($message)
            ];
            
        } catch (\Exception $e) {
            \Log::error('Message building test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Build Test Failed: ' . (string) $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Test method to build a minimal message with no extra configs
     */
    public function testMinimalMessage(): array
    {
        try {
            \Log::info('Testing minimal message building');
            
            // Create a very simple message without complex configs
            $message = CloudMessage::new();
            
            // Create basic notification
            $notification = Notification::create('Test Title', 'Test Message');
            $message = $message->withNotification($notification);
            
            \Log::info('Minimal message built successfully', [
                'message_class' => get_class($message),
                'notification_class' => get_class($notification)
            ]);
            
            return [
                'success' => true,
                'message' => 'Minimal message test passed',
                'message_class' => get_class($message)
            ];
            
        } catch (\Exception $e) {
            \Log::error('Minimal message test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Minimal Test Failed: ' . (string) $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Super simple test - just create a basic notification
     */
    public function testBasicNotification(): array
    {
        try {
            \Log::info('Testing basic notification creation');
            
            // Test the exact same data structure that's failing
            $testData = [
                'title' => 'Test Title',
                'message' => 'Test Message',
                'type' => 'general',
                'priority' => 'normal'
            ];
            
            // Try to send to topic (this is what's failing)
            $result = $this->sendToTopic($testData);
            
            return $result;
            
        } catch (\Exception $e) {
            \Log::error('Basic notification test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Basic Test Failed: ' . (string) $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Test with the exact data structure from the form
     */
    public function testFormData(): array
    {
        try {
            \Log::info('Testing with form data structure');
            
            // Simulate the exact data structure from the form
            $testData = [
                'title' => 'Test Title',
                'title_kurdish' => 'Test Title Kurdish',
                'message' => 'Test Message',
                'message_kurdish' => 'Test Message Kurdish',
                'type' => 'general',
                'priority' => 'normal',
                'data' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'nested' => ['a' => 'b', 'c' => 'd']
                ]
            ];
            
            \Log::info('Test data prepared', $testData);
            
            // Try to send to topic
            $result = $this->sendToTopic($testData);
            
            return $result;
            
        } catch (\Exception $e) {
            \Log::error('Form data test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Form Data Test Failed: ' . (string) $e->getMessage(),
                'status_code' => 500
            ];
        }
    }
}
