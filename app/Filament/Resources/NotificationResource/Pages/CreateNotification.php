<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
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
                $userIds = [];
                if (!empty($data['user_ids'])) {
                    $userIds = collect(explode(',', (string) $data['user_ids']))
                        ->map(fn($id) => trim($id))
                        ->filter()
                        ->values()
                        ->all();
                }

                // If we have specific user IDs, send to those users only
                // If no user IDs, send as broadcast to all users
                if (!empty($userIds)) {
                    \Log::info('Sending notification to specific users', ['user_ids' => $userIds]);
                    $result = $fcmService->sendNotification($notificationData, $userIds);
                } else {
                    \Log::info('Sending broadcast notification to all users');
                    $result = $fcmService->sendBroadcastNotification($notificationData);
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
            } catch (\Exception $e) {
                $data['status'] = 'failed';
                $data['api_response'] = ['error' => $e->getMessage()];
                \Log::error('Error sending notification', [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
                FilamentNotification::make()
                    ->title('Error sending notification')
                    ->body((string) $e->getMessage())
                    ->danger()
                    ->send();
            }
        } else {
            $data['status'] = 'pending';
        }

        return Notification::create($data);
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
}
