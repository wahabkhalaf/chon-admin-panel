<?php

namespace App\Filament\Resources\PointsPackageResource\Pages;

use App\Filament\Resources\PointsPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePointsPackage extends CreateRecord
{
    protected static string $resource = PointsPackageResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
