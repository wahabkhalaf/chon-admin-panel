<?php

namespace App\Filament\Resources\PlayerPointsBalanceResource\Pages;

use App\Filament\Resources\PlayerPointsBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlayerPointsBalances extends ListRecords
{
    protected static string $resource = PlayerPointsBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
