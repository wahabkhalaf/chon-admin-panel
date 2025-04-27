<?php

namespace App\Filament\Resources\CompetitionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompetitionLeaderboardRelationManager extends RelationManager
{
    protected static string $relationship = 'competitionLeaderboard';

    protected static ?string $title = 'Competition Leaderboard';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        // No form needed since this is read-only
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('player.nickname')
                    ->label('Player')
                    ->searchable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('rank')
                    ->label('Rank')
                    ->sortable()
                    ->colors([
                        'success' => fn($state) => $state === 1,
                        'warning' => fn($state) => $state === 2,
                        'primary' => fn($state) => $state === 3,
                        'secondary' => fn($state) => $state > 3,
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime(),
            ])
            ->defaultSort('rank', 'asc')
            ->filters([
                // No filters needed for this relation manager
            ])
            ->headerActions([
                // No header actions (create button removed)
            ])
            ->actions([
                // No row actions
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['player']);
            });
    }
}