<?php

namespace App\Filament\Resources\AdvertisingResource\Pages;

use App\Filament\Resources\AdvertisingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdvertisement extends EditRecord
{
    protected static string $resource = AdvertisingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Advertisement updated successfully!';
    }
}
