<?php

namespace App\Filament\Resources\PointsTransactionResource\Pages;

use App\Filament\Resources\PointsTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPointsTransactions extends ListRecords
{
    protected static string $resource = PointsTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Credit/Refund'),
        ];
    }
}
