<?php

namespace App\Filament\Resources\CompetitionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompetitionPlayerAnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'competitionPlayerAnswers';

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
                Tables\Columns\TextColumn::make('question.question_text')
                    ->label('Question')
                    ->limit(50),
                Tables\Columns\TextColumn::make('player_answer')
                    ->label('Player Answer')
                    ->limit(30),
                Tables\Columns\TextColumn::make('correct_answer')
                    ->label('Correct Answer')
                    ->limit(30),
                Tables\Columns\BadgeColumn::make('is_correct')
                    ->label('Correct?')
                    ->formatStateUsing(function ($state) {
                        return $state ? 'Yes' : 'No';
                    })
                    ->colors([
                        'success' => fn($state) => $state === true,
                        'danger' => fn($state) => $state === false,
                    ]),
                Tables\Columns\TextColumn::make('answered_at')
                    ->label('Answered At')
                    ->dateTime(),
            ])
            ->defaultSort('answered_at', 'asc')
            ->filters([
                Tables\Filters\Filter::make('correct_only')
                    ->label('Correct Answers')
                    ->query(fn(Builder $query) => $query->where('is_correct', true)),
                Tables\Filters\Filter::make('incorrect_only')
                    ->label('Incorrect Answers')
                    ->query(fn(Builder $query) => $query->where('is_correct', false)),
                Tables\Filters\SelectFilter::make('player')
                    ->relationship('player', 'nickname')
                    ->searchable()
                    ->label('Player'),
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
                return $query->with(['player', 'question']);
            });
    }
}