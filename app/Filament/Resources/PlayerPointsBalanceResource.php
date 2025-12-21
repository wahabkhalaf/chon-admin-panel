<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlayerPointsBalanceResource\Pages;
use App\Filament\Resources\PlayerPointsBalanceResource\RelationManagers;
use App\Models\Player;
use App\Models\PlayerPointsBalance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlayerPointsBalanceResource extends Resource
{
    protected static ?string $model = PlayerPointsBalance::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Player Balances';

    protected static ?string $navigationGroup = 'Points Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Player Balance';

    protected static ?string $pluralModelLabel = 'Player Balances';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Player Information')
                    ->schema([
                        Forms\Components\Select::make('player_id')
                            ->label('Player')
                            ->relationship('player', 'nickname')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($record) => $record !== null),
                    ]),
                Forms\Components\Section::make('Balance Details')
                    ->schema([
                        Forms\Components\TextInput::make('current_balance')
                            ->label('Current Balance')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('points'),
                        Forms\Components\TextInput::make('total_earned')
                            ->label('Total Earned')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('points'),
                        Forms\Components\TextInput::make('total_spent')
                            ->label('Total Spent')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('points'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('current_balance', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['player:id,nickname,whatsapp_number']))
            ->columns([
                Tables\Columns\TextColumn::make('player_id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('player.nickname')
                    ->label('Player')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('player.whatsapp_number')
                    ->label('WhatsApp')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Current Balance')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('total_earned')
                    ->label('Total Earned')
                    ->numeric()
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->numeric()
                    ->sortable()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_balance')
                    ->label('Has Balance')
                    ->query(fn (Builder $query): Builder => $query->where('current_balance', '>', 0)),
                Tables\Filters\Filter::make('zero_balance')
                    ->label('Zero Balance')
                    ->query(fn (Builder $query): Builder => $query->where('current_balance', '=', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for balances
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PointsTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlayerPointsBalances::route('/'),
            'create' => Pages\CreatePlayerPointsBalance::route('/create'),
            'view' => Pages\ViewPlayerPointsBalance::route('/{record}'),
            'edit' => Pages\EditPlayerPointsBalance::route('/{record}/edit'),
        ];
    }
}
