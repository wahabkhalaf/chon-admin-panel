<?php

namespace App\Filament\Resources\AppVersionResource\Pages;

use App\Filament\Resources\AppVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use App\Filament\Widgets\AppVersionStats;

class ListAppVersions extends ListRecords
{
    protected static string $resource = AppVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New Version')
                ->icon('heroicon-o-plus')
                ->color('success'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AppVersionStats::class,
        ];
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-device-phone-mobile';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No app versions found';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Create your first app version to start managing updates.';
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create First Version')
                ->icon('heroicon-o-plus'),
        ];
    }
}
