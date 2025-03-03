<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompetitionResource\Pages;
use App\Models\Competition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CompetitionResource extends Resource
{
    protected static ?string $model = Competition::class;
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'Game Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('entry_fee')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state < 0) {
                                    $set('entry_fee', 0);
                                }
                            }),
                        Forms\Components\TextInput::make('prize_pool')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state < 0) {
                                    $set('prize_pool', 0);
                                }
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Time & Capacity')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_time')
                            ->required()
                            ->live()
                            ->minDate(now())
                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                if ($state && $get('end_time') && Carbon::parse($state)->gte($get('end_time'))) {
                                    $set('end_time', Carbon::parse($state)->addHours(1));
                                }
                            }),
                        Forms\Components\DateTimePicker::make('end_time')
                            ->required()
                            ->live()
                            ->minDate(now())
                            ->after('start_time'),
                        Forms\Components\TextInput::make('max_users')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->integer()
                            ->live(onBlur: true),
                    ])->columns(3),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'upcoming' => 'Upcoming',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'closed' => 'Closed',
                            ])
                            ->default('upcoming')
                            ->live(),
                    ]),
            ])->statePath('data');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entry_fee')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prize_pool')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_users')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'upcoming',
                        'success' => 'active',
                        'primary' => 'completed',
                        'danger' => 'closed',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'upcoming' => 'Upcoming',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\Filter::make('active_competitions')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('start_time', '<=', now())
                        ->where('end_time', '>=', now())),
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
            //
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
        return static::getModel()::where('status', 'active')->count();
    }
}
