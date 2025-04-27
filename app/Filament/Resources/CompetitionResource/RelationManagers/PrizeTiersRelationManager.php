<?php

namespace App\Filament\Resources\CompetitionResource\RelationManagers;

use App\Models\PrizeTier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PrizeTiersRelationManager extends RelationManager
{
    protected static string $relationship = 'prizeTiers';

    protected static ?string $title = 'Prize Tiers';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rank Range')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('rank_from')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->label('From Rank'),
                                Forms\Components\TextInput::make('rank_to')
                                    ->required()
                                    ->numeric()
                                    ->minValue(function (Forms\Get $get) {
                                        return (int) $get('rank_from');
                                    })
                                    ->label('To Rank'),
                            ]),
                    ]),
                Forms\Components\Section::make('Prize Details')
                    ->schema([
                        Forms\Components\Select::make('prize_type')
                            ->required()
                            ->options(PrizeTier::PRIZE_TYPES)
                            ->default('cash')
                            ->label('Prize Type'),
                        Forms\Components\TextInput::make('prize_value')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->label(function (Forms\Get $get) {
                                $prizeType = $get('prize_type');
                                return match ($prizeType) {
                                    'cash' => 'Prize Amount (IQD)',
                                    'points' => 'Points Amount',
                                    default => 'Prize Value',
                                };
                            }),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rank_from')
                    ->formatStateUsing(function ($record) {
                        return $record->getRankRangeDescription();
                    })
                    ->label('Rank Range'),
                Tables\Columns\TextColumn::make('prize_type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => PrizeTier::PRIZE_TYPES[$state] ?? ucfirst($state))
                    ->colors([
                        'success' => fn(string $state): bool => $state === 'cash',
                        'primary' => fn(string $state): bool => $state === 'points',
                        'warning' => fn(string $state): bool => $state === 'item',
                    ])
                    ->label('Type'),
                Tables\Columns\TextColumn::make('prize_value')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->getPrizeDescription();
                    })
                    ->label('Prize'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('rank_from')
            ->filters([
                // 
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Ensure rank_to is at least equal to rank_from
                        if ($data['rank_to'] < $data['rank_from']) {
                            $data['rank_to'] = $data['rank_from'];
                        }
                        return $data;
                    }),
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
}