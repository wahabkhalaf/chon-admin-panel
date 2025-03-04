<?php

namespace App\Filament\Resources\CompetitionResource\Widgets;

use App\Models\Competition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CompetitionStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active Competitions', Competition::getActiveCompetitionsCount())
                ->description('Currently running competitions')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Upcoming Competitions', Competition::getUpcomingCompetitionsCount())
                ->description('Scheduled competitions')
                ->color('warning')
                ->chart([3, 2, 4, 3, 4, 5, 4]),

            Stat::make('Total Active Prize Pool', number_format(Competition::getTotalPrizePool(), 2) . ' IQD')
                ->description('Sum of all active competition prizes')
                ->color('success')
                ->chart([4, 5, 3, 4, 5, 6, 5]),

            Stat::make('Average Entry Fee', number_format(Competition::getAverageEntryFee(), 2) . ' IQD')
                ->description('Average entry fee for active competitions')
                ->color('info')
                ->chart([3, 4, 3, 4, 3, 4, 3]),

            Stat::make('Completed Competitions', Competition::getCompletedCompetitionsCount())
                ->description('Total finished competitions')
                ->color('gray')
                ->chart([2, 4, 6, 8, 10, 12, 14]),
        ];
    }
}
