<?php

namespace App\Filament\Resources\PlayerPaymentMethodResource\Pages;

use App\Filament\Resources\PlayerPaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePlayerPaymentMethod extends CreateRecord
{
    protected static string $resource = PlayerPaymentMethodResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}