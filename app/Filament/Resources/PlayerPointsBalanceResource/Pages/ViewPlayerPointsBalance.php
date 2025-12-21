<?php

namespace App\Filament\Resources\PlayerPointsBalanceResource\Pages;

use App\Filament\Resources\PlayerPointsBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPlayerPointsBalance extends ViewRecord
{
    protected static string $resource = PlayerPointsBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
