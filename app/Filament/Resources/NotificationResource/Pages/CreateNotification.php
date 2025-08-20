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
                    'title' => (string) ($data['title'] ?? ''),
                    'title_kurdish' => $data['title_kurdish'] ? (string) $data['title_kurdish'] : null,
                    'message' => (string) ($data['message'] ?? ''),
                    'message_kurdish' => $data['message_kurdish'] ? (string) $data['message_kurdish'] : null,
                    'type' => (string) ($data['type'] ?? 'general'),
                    'priority' => (string) ($data['priority'] ?? 'normal'),
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
            if (is_string($value) || is_numeric($value)) {
                $cleanData[$key] = (string) $value;
            } elseif (is_array($value)) {
                // Convert arrays to JSON strings
                $cleanData[$key] = json_encode($value);
            } elseif (is_object($value)) {
                // Convert objects to JSON strings
                $cleanData[$key] = json_encode($value);
            } else {
                // Convert anything else to string
                $cleanData[$key] = (string) $value;
            }
        }

        return $cleanData;
    }
}
