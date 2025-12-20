<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use App\Jobs\CreatePlayerNotificationRecords;
use App\Models\Notification;
use App\Services\FcmNotificationService;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CreateNotification extends CreateRecord
{
    protected static string $resource = NotificationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $sendImmediately = $data['send_immediately'] ?? true;
        unset($data['send_immediately']);

        if ($sendImmediately) {
            try {
                // TEMPORARILY DISABLED - FCM sending commented out to avoid sending to production
                // TODO: Re-enable when ready for production
                /*
                $fcmService = app(FcmNotificationService::class);
                // Ensure all data is properly formatted for FCM
                $notificationData = [
                    'title' => $this->ensureString($data['title'] ?? '', 'title'),
                    'title_kurdish' => $data['title_kurdish'] ? $this->ensureString($data['title_kurdish'], 'title_kurdish') : null,
                    'message' => $this->ensureString($data['message'] ?? '', 'message'),
                    'message_kurdish' => $data['message_kurdish'] ? $this->ensureString($data['message_kurdish'], 'message_kurdish') : null,
                    'type' => $this->ensureString($data['type'] ?? 'general', 'type'),
                    'priority' => $this->ensureString($data['priority'] ?? 'normal', 'priority'),
                    'data' => $this->cleanDataForFcm($data['data'] ?? []),
                ];

                // Log the cleaned data for debugging
                \Log::info('Notification data prepared for FCM', [
                    'original_data' => $data,
                    'cleaned_notification_data' => $notificationData,
                    'data_types' => [
                        'title' => gettype($notificationData['title']),
                        'message' => gettype($notificationData['message']),
                        'data' => gettype($notificationData['data'])
                    ]
                ]);
                */
                
                $userIds = [];
                if (!empty($data['user_ids'])) {
                    $userIds = collect(explode(',', (string) $data['user_ids']))
                        ->map(fn($id) => trim($id))
                        ->filter(fn($id) => is_numeric($id)) // Only keep numeric IDs
                        ->map(fn($id) => (int) $id) // Convert to integers
                        ->values()
                        ->all();
                }

                // TEMPORARILY DISABLED - FCM sending
                /*
                // If we have specific user IDs, send to those users only
                // If no user IDs, send as broadcast to all users
                if (!empty($userIds)) {
                    \Log::info('Sending notification to specific users', ['user_ids' => $userIds]);
                    $result = $fcmService->sendNotificationToUsers($notificationData, $userIds);
                    $data['target_user_ids'] = $userIds; // Store for later use
                } else {
                    \Log::info('Sending broadcast notification to all users');
                    $result = $fcmService->sendBroadcastNotification($notificationData);
                    $data['target_user_ids'] = null; // null means all users
                }
                $data['status'] = $result['success'] ? 'sent' : 'failed';
                
                // Store the result directly - the model will handle JSON encoding
                $data['api_response'] = $result;
                
                $data['sent_at'] = $result['success'] ? now() : null;

                if ($result['success']) {
                    FilamentNotification::make()
                        ->title('Notification sent successfully!')
                        ->success()
                        ->send();
                } else {
                    $errorMessage = $result['error'] ?? 'Unknown error';
                    
                    // Ensure error message is always a string
                    if (is_array($errorMessage)) {
                        $errorMessage = json_encode($errorMessage);
                    } elseif (is_object($errorMessage)) {
                        $errorMessage = json_encode($errorMessage);
                    } elseif (!is_string($errorMessage)) {
                        $errorMessage = (string) $errorMessage;
                    }
                    
                    // Log the error for debugging
                    \Log::error('Notification sending failed', [
                        'result' => $result,
                        'error_message' => $errorMessage
                    ]);
                    
                    FilamentNotification::make()
                        ->title('Failed to send notification')
                        ->body($errorMessage)
                        ->danger()
                        ->send();
                }
                */
                
                // Simulate successful save (without actually sending FCM)
                $data['target_user_ids'] = !empty($userIds) ? $userIds : null;
                $data['status'] = 'sent';
                $data['api_response'] = [
                    'success' => true,
                    'message' => 'Notification saved to database (FCM disabled)',
                    'test_mode' => true
                ];
                $data['sent_at'] = now();
                
                FilamentNotification::make()
                    ->title('Notification saved to database')
                    ->body('FCM sending is temporarily disabled')
                    ->success()
                    ->send();
                    
            } catch (\Exception $e) {
                $data['status'] = 'failed';
                $data['api_response'] = ['error' => $e->getMessage()];
                \Log::error('Error saving notification', [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
                FilamentNotification::make()
                    ->title('Error saving notification')
                    ->body((string) $e->getMessage())
                    ->danger()
                    ->send();
            }
        } else {
            $data['status'] = 'pending';
        }
        //Store target user IDs before removing from data
        $targetUserIds = $data['target_user_ids'] ?? null;
        
        // Remove fields that are not database columns
        unset($data['user_ids'], $data['target_user_ids']);

        try {
            \Log::info('Creating notification record', [
                'data_keys' => array_keys($data),
                'data' => $data
            ]);
            
            $notification = Notification::create($data);
            
            \Log::info('Notification created successfully', [
                'id' => $notification->id
            ]);
            
            // Create PlayerNotification records if notification was sent
            if ($data['status'] === 'sent' && $sendImmediately) {
                // Dispatch job to create player notifications in background
                CreatePlayerNotificationRecords::dispatch($notification->id, $targetUserIds);
                
                FilamentNotification::make()
                    ->title('Notification queued')
                    ->body('Player notifications are being created in the background')
                    ->info()
                    ->send();
            }
            
            \Log::info('Notification created successfully', [
                'id' => $notification->id
            ]);
            
            return $notification;
        } catch (\Exception $e) {
            \Log::error('Failed to create notification record', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Clean data for FCM - ensure all values are strings
     */
    private function cleanDataForFcm($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $cleanData = [];
        foreach ($data as $key => $value) {
            try {
                if (is_string($value) || is_numeric($value)) {
                    $cleanData[$key] = (string) $value;
                } elseif (is_array($value)) {
                    // Convert arrays to JSON strings
                    $jsonString = json_encode($value);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $cleanData[$key] = $jsonString;
                    } else {
                        \Log::warning("Failed to convert array to JSON for key: {$key}", [
                            'value' => $value,
                            'json_error' => json_last_error_msg()
                        ]);
                        // Fallback: convert array elements to strings
                        $cleanData[$key] = '[' . implode(', ', array_map('strval', $value)) . ']';
                    }
                } elseif (is_object($value)) {
                    // Convert objects to JSON strings
                    if (method_exists($value, '__toString')) {
                        $cleanData[$key] = (string) $value;
                    } else {
                        $jsonString = json_encode($value);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $cleanData[$key] = $jsonString;
                        } else {
                            \Log::warning("Failed to convert object to JSON for key: {$key}", [
                                'value' => $value,
                                'json_error' => json_last_error_msg()
                            ]);
                            $cleanData[$key] = get_class($value);
                        }
                    }
                } elseif (is_bool($value)) {
                    $cleanData[$key] = $value ? 'true' : 'false';
                } elseif (is_null($value)) {
                    $cleanData[$key] = '';
                } else {
                    // Convert anything else to string
                    $cleanData[$key] = (string) $value;
                }
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
     * Ensures a value is a string, converting arrays and objects to JSON if needed
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
     * Create PlayerNotification records for each player
     */
    private function createPlayerNotificationRecords(\App\Models\Notification $notification, ?array $targetUserIds): void
    {
        try {
            \Log::info('Creating PlayerNotification records', [
                'notification_id' => $notification->id,
                'target_user_ids' => $targetUserIds
            ]);

            // Get players based on target
            if ($targetUserIds !== null && !empty($targetUserIds)) {
                // Specific users
                $players = \App\Models\Player::whereIn('id', $targetUserIds)->get();
            } else {
                // All players
                $players = \App\Models\Player::all();
            }

            $insertData = [];
            $now = now();

            foreach ($players as $player) {
                $insertData[] = [
                    'player_id' => $player->id,
                    'notification_id' => $notification->id,
                    'received_at' => $now,
                    'read_at' => null,
                    'delivery_data' => json_encode([
                        'sent_via' => 'fcm',
                        'sent_at' => $now->toISOString(),
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($insertData)) {
                // Bulk insert for performance
                \DB::table('player_notifications')->insert($insertData);
                
                \Log::info('PlayerNotification records created', [
                    'count' => count($insertData),
                    'notification_id' => $notification->id
                ]);

                FilamentNotification::make()
                    ->title('Notification saved')
                    ->body(count($insertData) . ' players were notified')
                    ->success()
                    ->send();
            }

        } catch (\Exception $e) {
            \Log::error('Failed to create PlayerNotification records', [
                'error' => $e->getMessage(),
                'notification_id' => $notification->id
            ]);
        }
    }
}
