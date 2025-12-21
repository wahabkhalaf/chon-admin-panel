<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PointsTransactionResource\Pages;
use App\Models\Player;
use App\Models\PointsTransaction;
use App\Models\PlayerPointsBalance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PointsTransactionResource extends Resource
{
    protected static ?string $model = PointsTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Points Transactions';

    protected static ?string $navigationGroup = 'Points Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Points Transaction';

    protected static ?string $pluralModelLabel = 'Points Transactions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\Select::make('player_id')
                            ->label('Player')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Player::where('nickname', 'like', "%{$search}%")
                                ->orWhere('whatsapp_number', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('nickname', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Player::find($value)?->nickname)
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->options([
                                PointsTransaction::TYPE_PURCHASE => 'Purchase',
                                PointsTransaction::TYPE_SPEND => 'Spend',
                                PointsTransaction::TYPE_ADMIN_CREDIT => 'Admin Credit',
                                PointsTransaction::TYPE_REFUND => 'Refund',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->suffix('points'),
                    ])->columns(3),
                Forms\Components\Section::make('Reference')
                    ->schema([
                        Forms\Components\Select::make('reference_type')
                            ->options([
                                PointsTransaction::REF_COMPETITION => 'Competition',
                                PointsTransaction::REF_PACKAGE_PURCHASE => 'Package Purchase',
                                PointsTransaction::REF_ADMIN_ACTION => 'Admin Action',
                            ])
                            ->nullable(),
                        Forms\Components\TextInput::make('reference_id')
                            ->nullable()
                            ->maxLength(255),
                    ])->columns(2),
                Forms\Components\Section::make('Additional Info')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['player:id,nickname,whatsapp_number']))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('player.nickname')
                    ->label('Player')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('player.whatsapp_number')
                    ->label('WhatsApp')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PointsTransaction::TYPE_PURCHASE => 'success',
                        PointsTransaction::TYPE_SPEND => 'danger',
                        PointsTransaction::TYPE_ADMIN_CREDIT => 'info',
                        PointsTransaction::TYPE_REFUND => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        PointsTransaction::TYPE_PURCHASE => 'Purchase',
                        PointsTransaction::TYPE_SPEND => 'Spend',
                        PointsTransaction::TYPE_ADMIN_CREDIT => 'Admin Credit',
                        PointsTransaction::TYPE_REFUND => 'Refund',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable()
                    ->color(fn (PointsTransaction $record): string => 
                        $record->isCredit() ? 'success' : 'danger'
                    )
                    ->formatStateUsing(fn (PointsTransaction $record): string => 
                        ($record->isCredit() ? '+' : '-') . number_format($record->amount)
                    ),
                Tables\Columns\TextColumn::make('balance_before')
                    ->label('Before')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('After')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Ref Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        PointsTransaction::REF_COMPETITION => 'Competition',
                        PointsTransaction::REF_PACKAGE_PURCHASE => 'Package',
                        PointsTransaction::REF_ADMIN_ACTION => 'Admin',
                        default => $state ?? '-',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Ref ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        PointsTransaction::TYPE_PURCHASE => 'Purchase',
                        PointsTransaction::TYPE_SPEND => 'Spend',
                        PointsTransaction::TYPE_ADMIN_CREDIT => 'Admin Credit',
                        PointsTransaction::TYPE_REFUND => 'Refund',
                    ]),
                Tables\Filters\SelectFilter::make('reference_type')
                    ->options([
                        PointsTransaction::REF_COMPETITION => 'Competition',
                        PointsTransaction::REF_PACKAGE_PURCHASE => 'Package Purchase',
                        PointsTransaction::REF_ADMIN_ACTION => 'Admin Action',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for transactions (read-only history)
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
            'index' => Pages\ListPointsTransactions::route('/'),
            'create' => Pages\CreatePointsTransaction::route('/create'),
            'view' => Pages\ViewPointsTransaction::route('/{record}'),
        ];
    }
}
