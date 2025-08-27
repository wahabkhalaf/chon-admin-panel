<?php

namespace App\Filament\Resources\CompetitionResource\Pages;

use App\Filament\Resources\CompetitionResource;
use App\Models\Player;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\ActionSize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditCompetition extends EditRecord
{
    protected static string $resource = CompetitionResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        $stats = $this->getCompetitionStats();

        return $infolist
            ->schema([
                Section::make('Competition Statistics')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('total_players')
                            ->label('Total Players')
                            ->state($stats['total_players'])
                            ->color('primary')
                            ->size('lg')
                            ->icon('heroicon-o-users'),

                        TextEntry::make('total_correct_answers')
                            ->label('Total Correct Answers')
                            ->state($stats['total_correct_answers'])
                            ->color('success')
                            ->size('lg')
                            ->icon('heroicon-o-check-circle'),

                        TextEntry::make('average_score')
                            ->label('Average Score')
                            ->state($stats['average_score'])
                            ->color('info')
                            ->size('lg')
                            ->icon('heroicon-o-chart-bar'),

                        Grid::make()
                            ->schema([
                                TextEntry::make('highest_score')
                                    ->label('Highest Score')
                                    ->state($stats['highest_score'])
                                    ->color('success')
                                    ->icon('heroicon-o-arrow-up'),

                                TextEntry::make('lowest_score')
                                    ->label('Lowest Score')
                                    ->state($stats['lowest_score'])
                                    ->color('danger')
                                    ->icon('heroicon-o-arrow-down'),
                            ]),
                    ]),

                Section::make('Top Player')
                    ->visible(fn() => !empty($stats['top_player_id']))
                    ->columns(4)
                    ->schema([
                        TextEntry::make('top_player_name')
                            ->label('Player Name')
                            ->state($stats['top_player_name'])
                            ->color('primary')
                            ->icon('heroicon-o-trophy'),

                        TextEntry::make('total_competitions_joined')
                            ->label('Total Competitions')
                            ->state($stats['top_player_stats']['total_competitions_joined'] ?? 0)
                            ->color('primary')
                            ->icon('heroicon-o-flag'),

                        TextEntry::make('total_score')
                            ->label('Total Score')
                            ->state($stats['top_player_stats']['total_score'] ?? 0)
                            ->color('success')
                            ->icon('heroicon-o-star'),

                        TextEntry::make('total_wins')
                            ->label('Total Wins')
                            ->state($stats['top_player_stats']['total_wins'] ?? 0)
                            ->color('warning')
                            ->icon('heroicon-o-fire'),
                    ]),
            ]);
    }

    // Calculate competition statistics
    private function getCompetitionStats(): array
    {
        $competition = $this->record;

        // Calculate total players who joined this competition
        $totalPlayers = DB::table('transactions')
            ->where('competition_id', $competition->id)
            ->where('status', '=', 'completed')
            ->select('player_id')
            ->distinct()
            ->count();

        // Calculate total correct answers
        $totalCorrectAnswers = DB::table('competition_player_answers')
            ->where('competition_id', $competition->id)
            ->where('is_correct', true)
            ->count();

        // Calculate average score
        $averageScore = DB::table('competition_leaderboards')
            ->where('competition_id', $competition->id)
            ->avg('score') ?? 0;

        // Get highest score
        $highestScore = DB::table('competition_leaderboards')
            ->where('competition_id', $competition->id)
            ->max('score') ?? 0;

        // Get lowest score
        $lowestScore = DB::table('competition_leaderboards')
            ->where('competition_id', $competition->id)
            ->min('score') ?? 0;

        // Get top player
        $topPlayer = DB::table('competition_leaderboards')
            ->where('competition_id', $competition->id)
            ->orderBy('score', 'desc')
            ->select('player_id')
            ->first();

        $topPlayerId = $topPlayer ? $topPlayer->player_id : null;

        // Get top player details if available
        $topPlayerName = null;
        $topPlayerStats = [];

        if ($topPlayerId) {
            $playerInfo = Player::find($topPlayerId);
            $topPlayerName = $playerInfo ? $playerInfo->nickname : 'Unknown';

            // Calculate player stats
            // Total competitions joined by player
            $totalCompetitionsJoined = DB::table('transactions')
                ->where('player_id', $topPlayerId)
                ->where('status', '=', 'completed')
                ->select('competition_id')
                ->distinct()
                ->count();

            // Total score across all competitions
            $totalScore = DB::table('competition_leaderboards')
                ->where('player_id', $topPlayerId)
                ->sum('score') ?? 0;

            // Total wins (rank 1)
            $totalWins = DB::table('competition_leaderboards')
                ->where('player_id', $topPlayerId)
                ->where('rank', 1)
                ->count();

            $topPlayerStats = [
                'total_competitions_joined' => $totalCompetitionsJoined,
                'total_score' => $totalScore,
                'total_wins' => $totalWins,
            ];
        }

        return [
            'total_players' => $totalPlayers,
            'total_correct_answers' => $totalCorrectAnswers,
            'average_score' => round($averageScore, 2),
            'highest_score' => $highestScore,
            'lowest_score' => $lowestScore,
            'top_player_id' => $topPlayerId,
            'top_player_name' => $topPlayerName,
            'top_player_stats' => $topPlayerStats,
        ];
    }

    public function getContentTabLabel(): string|null
    {
        return 'Competition Details';
    }

    protected function getHeaderActions(): array
    {
        $status = $this->record->getStatus();
        $statusLabel = match ($status) {
            'upcoming' => 'Upcoming',
            'open' => 'Registration Open',
            'active' => 'Active',
            'completed' => 'Completed',
            default => ucfirst($status)
        };

        $statusColor = match ($status) {
            'upcoming' => 'warning',
            'open' => 'info',
            'active' => 'success',
            'completed' => 'secondary',
            default => 'gray'
        };

        return [

            Actions\Action::make('status')
                ->label($statusLabel)
                ->color($statusColor)
                ->icon(match ($status) {
                    'upcoming' => 'heroicon-o-clock',
                    'open' => 'heroicon-o-clipboard-document',
                    'active' => 'heroicon-o-play',
                    'completed' => 'heroicon-o-check-circle',
                    default => 'heroicon-o-information-circle'
                })
                ->size(ActionSize::Small)
                ->disabled()
                ->extraAttributes([
                    'class' => 'cursor-default',
                ]),

            Actions\DeleteAction::make()
                ->before(function ($action) {
                    if (!$this->record->canDelete()) {
                        $action->cancel();
                        Notification::make()
                            ->danger()
                            ->title('Cannot Delete Competition')
                            ->body('Active, open for registration, or completed competitions cannot be deleted.')
                            ->send();
                    }
                })
                ->visible(fn($action) => $this->record->canDelete()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->refresh();

        // Show a banner notification if the competition is not upcoming
        if (!$this->record->isUpcoming()) {
            $statusText = $this->record->isOpen() ? 'open for registration' :
                ($this->record->isActive() ? 'active' : 'completed');

            Notification::make()
                ->warning()
                ->title('Read-Only Mode')
                ->body("This competition is $statusText and cannot be edited. You can only view its details.")
                ->persistent()
                ->send();
        }

        return $data;
    }

    protected function beforeValidate(): void
    {
        // If competition is not upcoming, no fields can be edited
        if (!$this->record->isUpcoming()) {
            $allFields = ['name', 'description', 'game_type', 'entry_fee', 'open_time', 'start_time', 'end_time', 'max_users'];

            foreach ($allFields as $field) {
                if (isset($this->data[$field]) && $this->data[$field] != $this->record->$field) {
                    $this->data[$field] = $this->record->$field;

                    $statusText = $this->record->isOpen() ? 'open for registration' :
                        ($this->record->isActive() ? 'active' : 'completed');

                    Notification::make()
                        ->warning()
                        ->title('Competition Not Editable')
                        ->body("Cannot modify any fields for competitions that are $statusText. Only upcoming competitions can be edited.")
                        ->send();
                }
            }

            // After showing the notification once, return to prevent further validation
            // This ensures the user only sees one notification
            if (array_diff_assoc($this->data, $this->record->toArray())) {
                $this->halt();
                return;
            }
        }

        // Only validate time relationships for upcoming competitions
        if ($this->record->isUpcoming()) {
            try {
                // Validate start time is after open time
                if (isset($this->data['start_time']) && isset($this->data['open_time'])) {
                    if ($this->data['start_time'] <= $this->data['open_time']) {
                        throw new \InvalidArgumentException('Start time must be after registration open time');
                    }
                }

                // Validate end time is after start time
                if (isset($this->data['end_time']) && isset($this->data['start_time'])) {
                    if ($this->data['end_time'] <= $this->data['start_time']) {
                        throw new \InvalidArgumentException('End time must be after start time');
                    }
                }

                // Ensure non-negative values
                if (isset($this->data['entry_fee']) && $this->data['entry_fee'] < 0) {
                    $this->data['entry_fee'] = 0;
                }

                // Ensure max_users is at least 1
                if (isset($this->data['max_users']) && $this->data['max_users'] < 1) {
                    $this->data['max_users'] = 1;
                }
            } catch (\InvalidArgumentException $e) {
                // Add form error based on the message
                if (strpos($e->getMessage(), 'Start time') !== false) {
                    $this->addError('start_time', $e->getMessage());
                } elseif (strpos($e->getMessage(), 'End time') !== false) {
                    $this->addError('end_time', $e->getMessage());
                }

                // Show notification
                Notification::make()
                    ->danger()
                    ->title('Invalid Time Range')
                    ->body($e->getMessage())
                    ->send();
            }
        }
    }

    // Disable the Save button when the competition is not upcoming
    protected function getSaveFormAction(): Actions\Action
    {
        $isUpcoming = $this->record->isUpcoming();

        $action = parent::getSaveFormAction()
            ->disabled(!$isUpcoming)
            ->label(function () use ($isUpcoming) {
                if (!$isUpcoming) {
                    return 'Cannot Save (Read-Only)';
                }
                return 'Save changes';
            });

        // Add color to the button based on status
        if (!$isUpcoming) {
            $statusText = $this->record->getStatus();

            $color = match ($statusText) {
                'open' => 'info',
                'active' => 'success',
                'completed' => 'secondary',
                default => 'warning'
            };

            $action->color($color);
        }

        return $action;
    }

    // Add a status badge to the header
    protected function getHeaderWidgets(): array
    {
        return [
        ];
    }
    // make reations a tab
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
