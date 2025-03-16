<?php

namespace App\Filament\Resources\PlayerPaymentMethodResource\Pages;

use App\Filament\Resources\PlayerPaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlayerPaymentMethod extends EditRecord
{
    protected static string $resource = PlayerPaymentMethodResource::class;

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
}