<?php

namespace App\Filament\Resources\PointsTransactionResource\Pages;

use App\Filament\Resources\PointsTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPointsTransaction extends ViewRecord
{
    protected static string $resource = PointsTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Transactions are read-only, no edit action
        ];
    }
}
