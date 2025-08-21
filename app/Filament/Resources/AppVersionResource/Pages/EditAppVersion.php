<?php

namespace App\Filament\Resources\AppVersionResource\Pages;

use App\Filament\Resources\AppVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAppVersion extends EditRecord
{
    protected static string $resource = AppVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Check if this is the only active version for this platform
                    $record = $this->getRecord();
                    $activeVersionsCount = \App\Models\AppVersion::where('platform', $record->platform)
                        ->where('is_active', true)
                        ->count();
                    
                    if ($activeVersionsCount <= 1 && $record->is_active) {
                        Notification::make()
                            ->title('Warning')
                            ->body("This is the only active version for {$record->platform}. Consider creating a new version before deleting this one.")
                            ->warning()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Show success notification
        Notification::make()
            ->title('App Version Updated Successfully!')
            ->body("Version {$record->version} (Build {$record->build_number}) for {$record->platform} has been updated.")
            ->success()
            ->send();

        // Log the update
        \Log::info('App version updated via admin panel', [
            'id' => $record->id,
            'platform' => $record->platform,
            'version' => $record->version,
            'build_number' => $record->build_number,
            'is_force_update' => $record->is_force_update,
            'is_active' => $record->is_active,
            'updated_by' => auth()->id() ?? 'admin'
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
