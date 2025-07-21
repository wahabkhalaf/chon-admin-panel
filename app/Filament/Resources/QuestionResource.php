<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Filament\Resources\QuestionResource\RelationManagers;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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
                            ->columnSpan(2)
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
                                        if (!isset($livewire->data['options'])) {
                                            return;
                                        }

                                        if ($state) {
                                            // When marking an option as correct, set all others to false
                                            foreach ($livewire->data['options'] as $index => $option) {
                                                if (($option['is_correct'] ?? false) === true && $index !== array_key_last($livewire->data['options'])) {
                                                    $set("options.{$index}.is_correct", false);
                                                }
                                            }
                                        } else {
                                            // Check if any other option is marked as correct
                                            $hasCorrectOption = false;
                                            foreach ($livewire->data['options'] as $index => $option) {
                                                if (($option['is_correct'] ?? false) === true) {
                                                    $hasCorrectOption = true;
                                                    break;
                                                }
                                            }

                                            // If no correct option exists, force the last option to remain correct
                                            if (!$hasCorrectOption) {
                                                $lastIndex = array_key_last($livewire->data['options']);
                                                $set("options.{$lastIndex}.is_correct", true);
                                                Notification::make()
                                                    ->warning()
                                                    ->title('At least one option must be marked as correct')
                                                    ->send();
                                            }
                                        }

                                        // Always update correct_answer based on the current state
                                        foreach ($livewire->data['options'] as $option) {
                                            if (($option['is_correct'] ?? false) === true) {
                                                $set('correct_answer', $option['option'] ?? '');
                                                break;
                                            }
                                        }
                                    }),
                            ])
                            ->columns(2)
                            ->minItems(2)
                            ->maxItems(5)
                            ->visible(fn(callable $get) => $get('question_type') === 'multi_choice')
                            ->label('Answer Options')
                            ->rules([
                                'array',
                            ])
                            ->default([])
                            ->live()
                            ->afterStateUpdated(function (callable $set, $livewire) {
                                // Ensure correct_answer is set when options change
                                if (isset($livewire->data['options']) && is_array($livewire->data['options'])) {
                                    foreach ($livewire->data['options'] as $option) {
                                        if (($option['is_correct'] ?? false) === true) {
                                            $set('correct_answer', $option['option'] ?? '');
                                            break;
                                        }
                                    }
                                }
                            })
                            ->beforeStateDehydrated(function (callable $set, $livewire) {
                                // Ensure correct_answer is set before form submission
                                if (isset($livewire->data['options']) && is_array($livewire->data['options'])) {
                                    foreach ($livewire->data['options'] as $option) {
                                        if (($option['is_correct'] ?? false) === true) {
                                            $set('correct_answer', $option['option'] ?? '');
                                            break;
                                        }
                                    }
                                }
                            }),

                        // Puzzle answer
                        Forms\Components\TextInput::make('correct_answer')
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
                            ->required(function (callable $get) {
                                $questionType = $get('question_type');
                                return in_array($questionType, [
                                    'multi_choice',
                                    'puzzle',
                                    'pattern_recognition',
                                    'true_false',
                                    'math'
                                ]);
                            })
                            ->dehydrateStateUsing(function (callable $get) {
                                $questionType = $get('question_type');
                                if ($questionType === 'multi_choice') {
                                    $options = $get('options') ?? [];
                                    foreach ($options as $option) {
                                        if (($option['is_correct'] ?? false) === true) {
                                            return $option['option'] ?? '';
                                        }
                                    }
                                    return ''; // Return empty string instead of null for better validation
                                }
                                return $get('correct_answer');
                            })
                            ->live(),

                        Forms\Components\TextInput::make('seconds')
                            ->label('Seconds')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('Time allowed for this question (in seconds).'),
                    ]),

                Forms\Components\Section::make('Kurdish Translation')
                    ->schema([
                        Forms\Components\Textarea::make('question_text_kurdish')
                            ->maxLength(1000)
                            ->columnSpan(2)
                            ->label('Question (Kurdish)'),

                        // Multi-choice options in Kurdish
                        Forms\Components\Repeater::make('options_kurdish')
                            ->schema([
                                Forms\Components\TextInput::make('option')
                                    ->required(),
                                Forms\Components\Toggle::make('is_correct')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                        if (!isset($livewire->data['options_kurdish'])) {
                                            return;
                                        }

                                        if ($state) {
                                            // When marking an option as correct, set all others to false
                                            foreach ($livewire->data['options_kurdish'] as $index => $option) {
                                                if (($option['is_correct'] ?? false) === true && $index !== array_key_last($livewire->data['options_kurdish'])) {
                                                    $set("options_kurdish.{$index}.is_correct", false);
                                                }
                                            }
                                        } else {
                                            // Check if any other option is marked as correct
                                            $hasCorrectOption = false;
                                            foreach ($livewire->data['options_kurdish'] as $index => $option) {
                                                if (($option['is_correct'] ?? false) === true) {
                                                    $hasCorrectOption = true;
                                                    break;
                                                }
                                            }

                                            // If no correct option exists, force the last option to remain correct
                                            if (!$hasCorrectOption) {
                                                $lastIndex = array_key_last($livewire->data['options_kurdish']);
                                                $set("options_kurdish.{$lastIndex}.is_correct", true);
                                                Notification::make()
                                                    ->warning()
                                                    ->title('At least one Kurdish option must be marked as correct')
                                                    ->send();
                                            }
                                        }

                                        // Always update correct_answer_kurdish based on the current state
                                        foreach ($livewire->data['options_kurdish'] as $option) {
                                            if (($option['is_correct'] ?? false) === true) {
                                                $set('correct_answer_kurdish', $option['option'] ?? '');
                                                break;
                                            }
                                        }
                                    }),
                            ])
                            ->columns(2)
                            ->minItems(2)
                            ->maxItems(5)
                            ->visible(fn(callable $get) => $get('question_type') === 'multi_choice')
                            ->label('Answer Options (Kurdish)')
                            ->rules([
                                'array',
                            ])
                            ->default([])
                            ->live()
                            ->afterStateUpdated(function (callable $set, $livewire) {
                                // Ensure correct_answer_kurdish is set when options change
                                if (isset($livewire->data['options_kurdish']) && is_array($livewire->data['options_kurdish'])) {
                                    foreach ($livewire->data['options_kurdish'] as $option) {
                                        if (($option['is_correct'] ?? false) === true) {
                                            $set('correct_answer_kurdish', $option['option'] ?? '');
                                            break;
                                        }
                                    }
                                }
                            })
                            ->beforeStateDehydrated(function (callable $set, $livewire) {
                                // Ensure correct_answer_kurdish is set before form submission
                                if (isset($livewire->data['options_kurdish']) && is_array($livewire->data['options_kurdish'])) {
                                    foreach ($livewire->data['options_kurdish'] as $option) {
                                        if (($option['is_correct'] ?? false) === true) {
                                            $set('correct_answer_kurdish', $option['option'] ?? '');
                                            break;
                                        }
                                    }
                                }
                            }),

                        // Other answer types in Kurdish
                        Forms\Components\TextInput::make('correct_answer_kurdish')
                            ->visible(fn(callable $get) => in_array($get('question_type'), ['puzzle', 'pattern_recognition', 'math']))
                            ->label('Correct Answer (Kurdish)'),

                        // True/False answer in Kurdish
                        Forms\Components\Select::make('true_false_answer_kurdish')
                            ->options([
                                'true' => 'True',
                                'false' => 'False',
                            ])
                            ->visible(fn(callable $get) => $get('question_type') === 'true_false')
                            ->reactive()
                            ->afterStateUpdated(fn($state, callable $set) => $set('correct_answer_kurdish', $state))
                            ->label('Correct Answer (Kurdish)'),

                        // Hidden field to store the correct answer in Kurdish
                        Forms\Components\Hidden::make('correct_answer_kurdish')
                            ->dehydrateStateUsing(function (callable $get) {
                                $questionType = $get('question_type');

                                // For multi-choice, get the correct answer from the Kurdish options
                                if ($questionType === 'multi_choice') {
                                    $optionsKurdish = $get('options_kurdish') ?? [];
                                    foreach ($optionsKurdish as $option) {
                                        if (($option['is_correct'] ?? false) === true) {
                                            return $option['option'] ?? '';
                                        }
                                    }
                                    return ''; // Return empty string if no correct option found
                                }

                                return $get('correct_answer_kurdish');
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(),
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
                    ->sortable()
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
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->colors([
                        'success' => fn(string $state): bool => $state === 'easy',
                        'warning' => fn(string $state): bool => $state === 'medium',
                        'danger' => fn(string $state): bool => $state === 'hard',
                    ])
                    ->label('Difficulty'),
                // Tables\Columns\IconColumn::make('editable')
                //     ->label('Status')
                //     ->sortable()
                //     ->getStateUsing(fn(Model $record): bool => $record->canEdit())
                //     ->boolean()
                //     ->trueIcon('heroicon-o-pencil')
                //     ->falseIcon('heroicon-o-lock-closed')
                //     ->trueColor('success')
                //     ->falseColor('danger')
                //     ->tooltip(fn(Model $record): string => $record->canEdit()
                //         ? 'Editable'
                //         : 'Locked - Used in active competition'),
                Tables\Columns\TextColumn::make('competitions_count')
                    ->label('Used In')
                    ->getStateUsing(function ($record) {
                        return $record->competitions()->count();
                    })
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('question_type')
                    ->options(Question::TYPES),

                Tables\Filters\SelectFilter::make('level')
                    ->options(Question::LEVELS),

                Tables\Filters\Filter::make('new_questions')
                    ->label('New Questions')
                    ->query(fn(Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7))),

                Tables\Filters\Filter::make('unused_questions')
                    ->label('Unused Questions')
                    ->query(function (Builder $query): Builder {
                        return $query->whereDoesntHave('competitions');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn(Model $record): bool => $record->canEdit())
                    ->beforeFormFilled(function (Tables\Actions\EditAction $action, Model $record): void {
                        // Double-check if the question can be edited (redundant with visible condition but kept for safety)
                        if (!$record->canEdit()) {
                            Notification::make()
                                ->title('Cannot edit question')
                                ->body('This question is attached to competitions that are open for registration or already active. Questions in active competitions cannot be modified.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(Model $record): bool => $record->canEdit())
                    ->before(function (Tables\Actions\DeleteAction $action, Model $record): void {
                        // Double-check if the question can be deleted (redundant with visible condition but kept for safety)
                        if (!$record->canEdit()) {
                            Notification::make()
                                ->title('Cannot delete question')
                                ->body('This question is attached to competitions that are open for registration or already active. Questions in active competitions cannot be deleted.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
                Tables\Actions\ViewAction::make()
                    ->modalContent(fn(Question $record) => view('filament.resources.question-resource.question-detail', [
                        'question' => $record
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, Collection $records): void {
                            // Check if any of the questions cannot be deleted
                            $nonEditableQuestions = $records->filter(fn($record) => !$record->canEdit())->count();

                            if ($nonEditableQuestions > 0) {
                                Notification::make()
                                    ->title('Cannot delete some questions')
                                    ->body("$nonEditableQuestions question(s) are attached to active competitions and cannot be deleted.")
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Get the form schema for this resource.
     * This method is primarily used for testing.
     */
    public static function getFormSchema(): array
    {
        return Forms\Components\Section::make('Question Details')
            ->getChildComponents();
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
