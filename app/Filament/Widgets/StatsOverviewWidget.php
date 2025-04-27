<?php

namespace App\Filament\Widgets;

use App\Models\Competition;
use App\Models\CompetitionLeaderboard;
use App\Models\Player;
use App\Models\Question;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $topPlayer = CompetitionLeaderboard::with('player')
            ->orderBy('score', 'desc')
            ->first();

        $topPlayerDisplay = $topPlayer
            ? "{$topPlayer->player->nickname} ({$topPlayer->score})"
            : 'None';

        return [
            Stat::make('Total Competitions', Competition::count())
                ->description('Total number of competitions')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('primary'),

            Stat::make('Total Players', Player::count())
                ->description('Total number of players')
                ->descriptionIcon('heroicon-m-user')
                ->color('success'),

            Stat::make('Total Entry Fees', Transaction::sum('amount'))
                ->description('Total entry fees collected')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Active Competitions', Competition::getActiveCompetitionsCount())
                ->description('Currently active competitions')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('danger'),

            Stat::make('Total Questions', Question::count())
                ->description('Total number of questions')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('gray'),

            Stat::make('Top Player', $topPlayerDisplay)
                ->description('Player with highest score')
                ->descriptionIcon('heroicon-m-fire')
                ->color('danger'),
        ];
    }
}