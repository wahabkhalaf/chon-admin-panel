<?php

namespace App\Filament\Resources\PointsPackageResource\Pages;

use App\Filament\Resources\PointsPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPointsPackages extends ListRecords
{
    protected static string $resource = PointsPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
