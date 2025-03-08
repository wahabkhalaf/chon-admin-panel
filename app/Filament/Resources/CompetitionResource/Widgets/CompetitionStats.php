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

            Stat::make('Open for Registration', Competition::getOpenCompetitionsCount())
                ->description('Competitions accepting registrations')
                ->color('info')
                ->chart([3, 5, 7, 8, 6, 4, 5]),

            Stat::make('Upcoming Competitions', Competition::getUpcomingCompetitionsCount())
                ->description('Scheduled competitions')
                ->color('warning')
                ->chart([3, 2, 4, 3, 4, 5, 4]),

            Stat::make('Average Entry Fee', number_format(Competition::getAverageEntryFee(), 2) . ' IQD')
                ->description('Average entry fee for active competitions')
                ->color('info')
                ->chart([3, 4, 3, 4, 3, 4, 3]),

            Stat::make('Completed Competitions', Competition::getCompletedCompetitionsCount())
                ->description('Total finished competitions')
                ->color('gray')
                ->chart([2, 4, 6, 8, 10, 12, 14]),

            Stat::make('Most Popular Game Type', Competition::getMostPopularGameType())
                ->description('Most common game type')
                ->color('primary')
                ->chart([3, 5, 7, 8, 6, 4, 5]),
        ];
    }
}
