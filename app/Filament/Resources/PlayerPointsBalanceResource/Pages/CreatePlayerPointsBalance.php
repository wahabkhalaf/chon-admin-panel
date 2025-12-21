<?php

namespace App\Filament\Resources\PlayerPointsBalanceResource\Pages;

use App\Filament\Resources\PlayerPointsBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePlayerPointsBalance extends CreateRecord
{
    protected static string $resource = PlayerPointsBalanceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
