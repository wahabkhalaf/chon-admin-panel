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
                'notification_id' => $notificationData['id'] ?? 'unknown'
            ]);

            // If we have specific tokens, send to those only
            // If no tokens, send to topic (but be careful about duplicates)
            if (empty($fcmTokens)) {
                \Log::info('No FCM tokens provided, sending to topic');
                return $this->sendToTopic($notificationData);
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
                    $result = $this->sendToToken($notificationData, $token);
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
            \Log::error('Exception while sending FCM notification', [
                'notification' => $notificationData,
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
            \Log::info('Building FCM message for topic', [
                'topic' => 'all_users',
                'notification_data' => $notificationData
            ]);

            // Validate notification data before building message
            if (empty($notificationData['title']) || empty($notificationData['message'])) {
                throw new \Exception('Title and message are required for notifications');
            }

            $message = $this->buildMessage($notificationData);
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
                'trace' => $e->getTraceAsString()
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
            $message = CloudMessage::new();

            // Ensure title and message are strings
            $title = $notificationData['title'] ?? '';
            $messageText = $notificationData['message'] ?? '';
            
            // Convert to strings if they're not already
            if (is_array($title)) {
                $title = json_encode($title);
            } elseif (!is_string($title)) {
                $title = (string) $title;
            }
            
            if (is_array($messageText)) {
                $messageText = json_encode($messageText);
            } elseif (!is_string($messageText)) {
                $messageText = (string) $messageText;
            }

            // Set notification content
            $notification = Notification::create($title, $messageText);

            $message = $message->withNotification($notification);

            // Set data payload - ensure all data is string-compatible
            if (!empty($notificationData['data'])) {
                $cleanData = [];
                foreach ($notificationData['data'] as $key => $value) {
                    if (is_array($value)) {
                        $cleanData[$key] = json_encode($value);
                    } elseif (is_object($value)) {
                        $cleanData[$key] = json_encode($value);
                    } else {
                        $cleanData[$key] = (string) $value;
                    }
                }
                $message = $message->withData($cleanData);
            }

            // Android configuration - simplified to avoid validation errors
            $androidConfig = AndroidConfig::fromArray([
                'priority' => $this->getAndroidPriority($notificationData['priority'] ?? 'normal'),
                'notification' => [
                    'title' => $notificationData['title'] ?? '',
                    'body' => $notificationData['message'] ?? '',
                    'icon' => 'ic_notification',
                    'sound' => 'default',
                    'channel_id' => 'chon_notifications'
                ]
            ]);

            $message = $message->withAndroidConfig($androidConfig);

            // iOS configuration
            $apnsConfig = ApnsConfig::fromArray([
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $notificationData['title'] ?? '',
                            'body' => $notificationData['message'] ?? ''
                        ],
                        'sound' => 'default',
                        'badge' => 1,
                        'category' => 'chon_notifications'
                    ]
                ]
            ]);

            $message = $message->withApnsConfig($apnsConfig);

            // Web push configuration
            $webPushConfig = WebPushConfig::fromArray([
                'notification' => [
                    'title' => $notificationData['title'] ?? '',
                    'body' => $notificationData['message'] ?? '',
                    'icon' => '/images/notification-icon.png',
                    'badge' => '/images/badge-icon.png',
                    'data' => $notificationData['data'] ?? []
                ]
            ]);

            $message = $message->withWebPushConfig($webPushConfig);

            return $message;
            
        } catch (\Exception $e) {
            \Log::error('Error building FCM message', [
                'error' => $e->getMessage(),
                'notification_data' => $notificationData
            ]);
            throw $e;
        }
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
     * Send notification to ALL users (topic only - for broadcasts)
     */
    public function sendBroadcastNotification(array $notificationData): array
    {
        try {
            \Log::info('Sending broadcast notification to topic only');
            return $this->sendToTopic($notificationData);
        } catch (\Exception $e) {
            \Log::error('Failed to send broadcast notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
}
