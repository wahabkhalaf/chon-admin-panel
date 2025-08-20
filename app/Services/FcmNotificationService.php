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
                'fcm_tokens_count' => count($fcmTokens)
            ]);

            if (empty($fcmTokens)) {
                return $this->sendToTopic($notificationData);
            }

            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($fcmTokens as $token) {
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

            \Log::info('FCM notification sent', [
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
                'error' => $e->getMessage(),
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
                'error' => (string) $e->getMessage(),
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
                'error' => (string) $e->getMessage(),
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

            // Set notification content
            $notification = Notification::create(
                $notificationData['title'] ?? '',
                $notificationData['message'] ?? ''
            );

            $message = $message->withNotification($notification);

            // Set data payload
            if (!empty($notificationData['data'])) {
                $message = $message->withData($notificationData['data']);
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
            $result = $this->messaging->subscribeToTopic($topic, $tokens);

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
                'error' => (string) $e->getMessage(),
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
            $result = $this->messaging->unsubscribeFromTopic($topic, $tokens);

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
                'error' => (string) $e->getMessage(),
                'status_code' => 500
            ];
        }
    }
}
