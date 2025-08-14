<?php

namespace App\Filament\Resources\AdvertisingResource\Pages;

use App\Filament\Resources\AdvertisingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdvertisements extends ListRecords
{
    protected static string $resource = AdvertisingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Advertisement'),
        ];
    }
}
