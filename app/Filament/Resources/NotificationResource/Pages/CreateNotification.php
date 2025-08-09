<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\ExpressApiClient;
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
                $apiClient = app(ExpressApiClient::class);
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

                $result = $apiClient->sendNotification($notificationData, $userIds);
                $data['status'] = $result['success'] ? 'sent' : 'failed';
                $data['api_response'] = $result;
                $data['sent_at'] = $result['success'] ? now() : null;

                if ($result['success']) {
                    FilamentNotification::make()
                        ->title('Notification sent successfully!')
                        ->success()
                        ->send();
                } else {
                    FilamentNotification::make()
                        ->title('Failed to send notification')
                        ->body($result['error'] ?? 'Unknown error')
                        ->danger()
                        ->send();
                }
            } catch (\Exception $e) {
                $data['status'] = 'failed';
                $data['api_response'] = ['error' => $e->getMessage()];
                Log::error('Error sending notification', [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
                FilamentNotification::make()
                    ->title('Error sending notification')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        } else {
            $data['status'] = 'pending';
        }

        return Notification::create($data);
    }
}
