<?php

namespace App\Filament\Resources\PlayerPaymentMethodResource\Pages;

use App\Filament\Resources\PlayerPaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlayerPaymentMethods extends ListRecords
{
    protected static string $resource = PlayerPaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}