<?php

namespace App\Filament\Resources\PlayerPointsBalanceResource\Pages;

use App\Filament\Resources\PlayerPointsBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlayerPointsBalance extends EditRecord
{
    protected static string $resource = PlayerPointsBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
