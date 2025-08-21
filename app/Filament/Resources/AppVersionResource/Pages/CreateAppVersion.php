<?php

namespace App\Filament\Resources\AppVersionResource\Pages;

use App\Filament\Resources\AppVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAppVersion extends CreateRecord
{
    protected static string $resource = AppVersionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        // Show success notification with version details
        Notification::make()
            ->title('App Version Created Successfully!')
            ->body("Version {$record->version} (Build {$record->build_number}) for {$record->platform} has been created.")
            ->success()
            ->send();

        // Log the creation
        \Log::info('New app version created via admin panel', [
            'platform' => $record->platform,
            'version' => $record->version,
            'build_number' => $record->build_number,
            'is_force_update' => $record->is_force_update,
            'created_by' => auth()->id() ?? 'admin'
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values if not provided
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_force_update'] = $data['is_force_update'] ?? false;
        
        // Set release date to now if not provided
        if (empty($data['released_at'])) {
            $data['released_at'] = now();
        }

        return $data;
    }
}
