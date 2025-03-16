<?php

namespace App\Filament\Resources\PlayerWalletResource\Pages;

use App\Filament\Resources\PlayerWalletResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlayerWallets extends ListRecords
{
    protected static string $resource = PlayerWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action needed as wallets are created automatically
        ];
    }
}