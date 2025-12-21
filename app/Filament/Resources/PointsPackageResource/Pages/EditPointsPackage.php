<?php

namespace App\Filament\Resources\PointsPackageResource\Pages;

use App\Filament\Resources\PointsPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPointsPackage extends EditRecord
{
    protected static string $resource = PointsPackageResource::class;

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
