<?php

namespace App\Filament\Widgets;

use App\Models\AppVersion;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AppVersionStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalVersions = AppVersion::count();
        $androidVersions = AppVersion::where('platform', 'android')->count();
        $iosVersions = AppVersion::where('platform', 'ios')->count();
        $activeVersions = AppVersion::where('is_active', true)->count();
        $forceUpdates = AppVersion::where('is_force_update', true)->count();
        
        $latestAndroid = AppVersion::where('platform', 'android')
            ->where('is_active', true)
            ->orderBy('build_number', 'desc')
            ->first();
            
        $latestIos = AppVersion::where('platform', 'ios')
            ->where('is_active', true)
            ->orderBy('build_number', 'desc')
            ->first();

        return [
            Stat::make('Total Versions', $totalVersions)
                ->description('All app versions')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('primary'),

            Stat::make('Android Versions', $androidVersions)
                ->description($latestAndroid ? "Latest: v{$latestAndroid->version} (Build {$latestAndroid->build_number})" : 'No versions')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('success'),

            Stat::make('iOS Versions', $iosVersions)
                ->description($latestIos ? "Latest: v{$latestIos->version} (Build {$latestIos->build_number})" : 'No versions')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('info'),

            Stat::make('Active Versions', $activeVersions)
                ->description('Available for update checks')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($activeVersions > 0 ? 'success' : 'danger'),

            Stat::make('Force Updates', $forceUpdates)
                ->description('Critical versions requiring update')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($forceUpdates > 0 ? 'warning' : 'gray'),
        ];
    }
}
