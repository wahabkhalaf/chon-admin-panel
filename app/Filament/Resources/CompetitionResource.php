<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompetitionResource\Pages;
use App\Filament\Resources\CompetitionResource\RelationManagers\CompetitionLeaderboardRelationManager;
use App\Filament\Resources\CompetitionResource\RelationManagers\CompetitionPlayerAnswersRelationManager;
use App\Filament\Resources\CompetitionResource\RelationManagers\PrizeTiersRelationManager;
use App\Filament\Resources\CompetitionResource\RelationManagers\QuestionsRelationManager;
use App\Filament\Resources\CompetitionResource\Widgets\CompetitionStats;
use App\Models\Competition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;


class CompetitionResource extends Resource
{
    protected static ?string $model = Competition::class;
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'Competition Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([


                Forms\Components\Section::make(function ($record) {
                    if ($record && !$record->isUpcoming()) {
                        $statusText = $record->isOpen() ? 'open for registration' :
                            ($record->isActive() ? 'active' : 'completed');

                        return "Competition Status: " . ucfirst($statusText) . " (Read Only)";
                    }
                    return 'Basic Information';
                })
                    ->description(function ($record) {
                        if ($record && !$record->isUpcoming()) {
                            $statusText = $record->isOpen() ? 'open for registration' :
                                ($record->isActive() ? 'active' : 'completed');

                            $statusColor = match ($statusText) {
                                'open' => 'primary',
                                'active' => 'success',
                                'completed' => 'danger',
                                default => 'gray'
                            };

                            return "This competition is no longer in 'upcoming' status and cannot be edited. You can only view its details.";
                        }
                        return null;
                    })
                    ->icon(function ($record) {
                        if (!$record || $record->isUpcoming()) {
                            return 'heroicon-o-information-circle';
                        }

                        $status = $record->getStatus();
                        return match ($status) {
                            'open' => 'heroicon-o-clipboard-document-list',
                            'active' => 'heroicon-o-play',
                            'completed' => 'heroicon-o-check-badge',
                            default => 'heroicon-o-information-circle'
                        };
                    })
                    ->iconColor(function ($record) {
                        if (!$record || $record->isUpcoming()) {
                            return 'gray';
                        }

                        $status = $record->getStatus();
                        return match ($status) {
                            'open' => 'info',
                            'active' => 'success',
                            'completed' => 'primary',
                            default => 'gray'
                        };
                    })
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->disabled(fn($record) => $record && !$record->canEditField('name')),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->disabled(fn($record) => $record && !$record->canEditField('description')),
                        Forms\Components\Select::make('game_type')
                            ->required()
                            ->options([
                                'action' => 'Action',
                                'strategy' => 'Strategy',
                                'puzzle' => 'Puzzle',
                                'racing' => 'Racing',
                                'sports' => 'Sports',
                                'rpg' => 'RPG',
                                'other' => 'Other',
                            ])
                            ->disabled(fn($record) => $record && !$record->canEditField('game_type')),
                    ]),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('entry_fee')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('IQD')
                            ->disabled(fn($record) => $record && !$record->canEditField('entry_fee')),
                    ])->columns(1),

                Forms\Components\Section::make('Time & Capacity')
                    ->schema([
                        Forms\Components\DateTimePicker::make('open_time')
                            ->required()
                            ->minDate(now())
                            ->label('Registration Opens')
                            ->helperText('When users can start registering for this competition')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                // If start_time is before open_time, reset it
                                $set('start_time', null);
                                $set('end_time', null);
                            })
                            ->disabled(fn($record) => $record && !$record->canEditField('open_time')),

                        Forms\Components\DateTimePicker::make('start_time')
                            ->required()
                            ->label('Competition Starts')
                            ->helperText('Must be after registration opens')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                // If end_time is before start_time, reset it
                                $set('end_time', null);
                            })
                            ->rules([
                                fn(Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $openTime = $get('open_time');
                                    if ($openTime && $value && $value <= $openTime) {
                                        $fail('Start time must be after registration open time');
                                    }
                                },
                            ])
                            ->disabled(fn($record) => $record && !$record->canEditField('start_time')),

                        Forms\Components\DateTimePicker::make('end_time')
                            ->required()
                            ->label('Competition Ends')
                            ->helperText('Must be after competition starts')
                            ->rules([
                                fn(Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $startTime = $get('start_time');
                                    if ($startTime && $value && $value <= $startTime) {
                                        $fail('End time must be after start time');
                                    }
                                },
                            ])
                            ->disabled(fn($record) => $record && !$record->canEditField('end_time')),

                        Forms\Components\TextInput::make('max_users')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->label('Maximum Participants')
                            ->disabled(fn($record) => $record && !$record->canEditField('max_users')),
                    ])->columns(2),
            ])->statePath('data');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('open_time', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('game_type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->getStateUsing(fn($record) => $record->getStatus())
                    ->colors([
                        'warning' => 'upcoming',
                        'info' => 'open',
                        'success' => 'active',
                        'primary' => 'completed',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'upcoming' => 'Upcoming',
                            'open' => 'Registration Open',
                            'active' => 'Active',
                            'completed' => 'Completed',
                            default => ucfirst($state)
                        };
                    }),
                Tables\Columns\TextColumn::make('entry_fee')
                    ->money('IQD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('open_time')
                    ->dateTime()
                    ->sortable()
                    ->label('Registration Opens'),
                Tables\Columns\TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable()
                    ->label('Starts'),
                Tables\Columns\TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable()
                    ->label('Ends'),
                Tables\Columns\TextColumn::make('max_users')
                    ->numeric()
                    ->sortable()
                    ->label('Max Participants'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'upcoming' => 'Upcoming',
                        'open' => 'Registration Open',
                        'active' => 'Active',
                        'completed' => 'Completed',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        $now = now();

                        return match ($data['value']) {
                            'upcoming' => $query->where('open_time', '>', $now),
                            'open' => $query->where('open_time', '<=', $now)
                                ->where('start_time', '>', $now),
                            'active' => $query->where('start_time', '<=', $now)
                                ->where('end_time', '>=', $now),
                            'completed' => $query->where('end_time', '<', $now),
                            default => $query,
                        };
                    }),
                Tables\Filters\SelectFilter::make('game_type')
                    ->options([
                        'action' => 'Action',
                        'strategy' => 'Strategy',
                        'puzzle' => 'Puzzle',
                        'racing' => 'Racing',
                        'sports' => 'Sports',
                        'rpg' => 'RPG',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            QuestionsRelationManager::class,
            CompetitionPlayerAnswersRelationManager::class,
            CompetitionLeaderboardRelationManager::class,
            PrizeTiersRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            CompetitionStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompetitions::route('/'),
            'create' => Pages\CreateCompetition::route('/create'),
            'edit' => Pages\EditCompetition::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::getActiveCompetitionsCount();
    }
}
