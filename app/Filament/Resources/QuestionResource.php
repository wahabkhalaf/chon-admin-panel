<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Filament\Resources\QuestionResource\RelationManagers;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Competition Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Question Details')
                    ->schema([
                        Forms\Components\Textarea::make('question_text')
                            ->required()
                            ->maxLength(1000)
                            ->label('Question'),

                        Forms\Components\Select::make('question_type')
                            ->options(Question::TYPES)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Set default empty array for options when not multi-choice
                                if ($state !== 'multi_choice') {
                                    $set('options', []);
                                }
                            })
                            ->label('Question Type'),

                        Forms\Components\Select::make('level')
                            ->options(Question::LEVELS)
                            ->default('medium')
                            ->required()
                            ->label('Difficulty Level'),

                        // Multi-choice options
                        Forms\Components\Repeater::make('options')
                            ->schema([
                                Forms\Components\TextInput::make('option')
                                    ->required(),
                                Forms\Components\Toggle::make('is_correct')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                        if ($state) {
                                            $livewire->data['correct_answer'] = $livewire->data['options'][$livewire->repeaters['data.options']['repeaterIndex']]['option'];
                                        }
                                    }),
                            ])
                            ->columns(2)
                            ->visible(fn(callable $get) => $get('question_type') === 'multi_choice')
                            ->label('Answer Options'),

                        // Puzzle answer
                        Forms\Components\TextInput::make('puzzle_answer')
                            ->required(fn(callable $get) => $get('question_type') === 'puzzle')
                            ->visible(fn(callable $get) => $get('question_type') === 'puzzle')
                            ->reactive()
                            ->afterStateUpdated(fn($state, callable $set) => $set('correct_answer', $state))
                            ->label('Correct Answer'),

                        // Pattern recognition answer
                        Forms\Components\TextInput::make('pattern_answer')
                            ->required(fn(callable $get) => $get('question_type') === 'pattern_recognition')
                            ->visible(fn(callable $get) => $get('question_type') === 'pattern_recognition')
                            ->reactive()
                            ->afterStateUpdated(fn($state, callable $set) => $set('correct_answer', $state))
                            ->label('Correct Pattern Answer'),

                        // True/False answer
                        Forms\Components\Select::make('true_false_answer')
                            ->options([
                                'true' => 'True',
                                'false' => 'False',
                            ])
                            ->required(fn(callable $get) => $get('question_type') === 'true_false')
                            ->visible(fn(callable $get) => $get('question_type') === 'true_false')
                            ->reactive()
                            ->afterStateUpdated(fn($state, callable $set) => $set('correct_answer', $state))
                            ->label('Correct Answer'),

                        // Math answer
                        Forms\Components\TextInput::make('math_answer')
                            ->required(fn(callable $get) => $get('question_type') === 'math')
                            ->visible(fn(callable $get) => $get('question_type') === 'math')
                            ->reactive()
                            ->numeric()
                            ->afterStateUpdated(fn($state, callable $set) => $set('correct_answer', (string) $state))
                            ->label('Correct Math Answer'),

                        // Hidden field to store the correct answer
                        Forms\Components\Hidden::make('correct_answer')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question_text')
                    ->searchable()
                    ->limit(50)
                    ->label('Question'),
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
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->colors([
                        'success' => fn(string $state): bool => $state === 'easy',
                        'warning' => fn(string $state): bool => $state === 'medium',
                        'danger' => fn(string $state): bool => $state === 'hard',
                    ])
                    ->label('Difficulty'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('question_type')
                    ->options(Question::TYPES),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
