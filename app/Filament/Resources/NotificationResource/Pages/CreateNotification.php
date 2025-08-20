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
                $notificationData = [
                    'title' => $data['title'],
                    'title_kurdish' => $data['title_kurdish'] ?? null,
                    'message' => $data['message'],
                    'message_kurdish' => $data['message_kurdish'] ?? null,
                    'type' => $data['type'],
                    'priority' => $data['priority'],
                    'data' => $data['data'] ?? [],
                ];
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
                    // Ensure error message is a string
                    if (is_array($errorMessage)) {
                        $errorMessage = json_encode($errorMessage);
                    }
                    
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
}
