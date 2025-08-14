<?php

namespace App\Filament\Resources\AdvertisingResource\Pages;

use App\Filament\Resources\AdvertisingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdvertisement extends CreateRecord
{
    protected static string $resource = AdvertisingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Advertisement created successfully!';
    }
}
