<?php

namespace App\Filament\Resources\CompetitionResource\RelationManagers;

use App\Models\CompetitionQuestion;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('question_id')
                    ->label('Question')
                    ->options(Question::all()->pluck('question_text', 'id'))
                    ->searchable()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('question_text')
                    ->label('Question')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('question_type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => Question::TYPES[$state] ?? $state)
                    ->colors([
                        'primary' => fn(string $state): bool => $state === 'multi_choice',
                        'success' => fn(string $state): bool => $state === 'true_false',
                        'warning' => fn(string $state): bool => $state === 'puzzle',
                        'danger' => fn(string $state): bool => $state === 'math',
                        'gray' => fn(string $state): bool => $state === 'pattern_recognition',
                    ]),

                Tables\Columns\TextColumn::make('level')
                    ->badge()
                    ->label('Difficulty')
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->colors([
                        'success' => fn(string $state): bool => $state === 'easy',
                        'warning' => fn(string $state): bool => $state === 'medium',
                        'danger' => fn(string $state): bool => $state === 'hard',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('question_type')
                    ->options(Question::TYPES)
                    ->label('Question Type'),

                Tables\Filters\SelectFilter::make('level')
                    ->options(Question::LEVELS)
                    ->label('Difficulty'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}