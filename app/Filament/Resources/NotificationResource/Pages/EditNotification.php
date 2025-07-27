<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditNotification extends EditRecord
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // If notification is already sent, show a warning
        if (isset($data['status']) && $data['status'] === 'sent') {
            Notification::make()
                ->title('Notification Already Sent')
                ->body('This notification has already been sent and cannot be modified.')
                ->warning()
                ->persistent()
                ->send();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Prevent saving if notification is already sent
        if (isset($data['status']) && $data['status'] === 'sent') {
            Notification::make()
                ->title('Cannot Modify Sent Notification')
                ->body('This notification has already been sent and cannot be modified.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }
}
