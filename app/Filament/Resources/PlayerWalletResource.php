<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlayerWalletResource\Pages;
use App\Models\PaymentMethod;
use App\Models\Player;
use App\Models\PlayerWallet;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlayerWalletResource extends Resource
{
    protected static ?string $model = PlayerWallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Player Wallets';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('player_id')
                    ->label('Player')
                    ->options(Player::all()->pluck('nickname', 'id'))
                    ->searchable()
                    ->required()
                    ->disabled(fn($record) => $record !== null),
                Forms\Components\TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('last_updated')
                    ->label('Last Updated')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('player.nickname')
                    ->label('Player')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('player.whatsapp_number')
                    ->label('WhatsApp Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_balance')
                    ->label('Has Balance')
                    ->query(fn(Builder $query) => $query->where('balance', '>', 0)),
                Tables\Filters\Filter::make('no_balance')
                    ->label('No Balance')
                    ->query(fn(Builder $query) => $query->where('balance', '=', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('add_funds')
                    ->label('Add Funds')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('$'),
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options(function () {
                                return PaymentMethod::where('is_active', true)
                                    ->where('supports_deposit', true)
                                    ->pluck('name', 'code');
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('payment_provider')
                            ->label('Payment Provider')
                            ->maxLength(50)
                            ->default(function (callable $get) {
                                $code = $get('payment_method');
                                if (!$code)
                                    return null;
                                $method = PaymentMethod::where('code', $code)->first();
                                return $method ? $method->provider : null;
                            }),
                        Forms\Components\KeyValue::make('payment_details')
                            ->label('Payment Details')
                            ->keyLabel('Field')
                            ->valueLabel('Value'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->nullable(),
                    ])
                    ->action(function (PlayerWallet $record, array $data) {
                        // Create a deposit transaction
                        $transaction = $record->player->createTransaction(
                            $data['amount'],
                            Transaction::TYPE_DEPOSIT,
                            null,
                            null,
                            $data['notes'] ?? 'Added via admin panel',
                            $data['payment_method'] ?? null,
                            $data['payment_provider'] ?? null,
                            $data['payment_details'] ?? null
                        );

                        // Mark it as completed
                        $transaction->markAsCompleted('Manually added by admin');

                        // Refresh the record
                        $record->refresh();
                    }),
                Tables\Actions\Action::make('withdraw_funds')
                    ->label('Withdraw Funds')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('$')
                            ->rules([
                                fn(PlayerWallet $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
                                    if ($value > $record->balance) {
                                        $fail("The amount cannot exceed the current balance of $" . number_format($record->balance, 2));
                                    }
                                },
                            ]),
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options(function () {
                                return PaymentMethod::where('is_active', true)
                                    ->where('supports_withdrawal', true)
                                    ->pluck('name', 'code');
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('payment_provider')
                            ->label('Payment Provider')
                            ->maxLength(50)
                            ->default(function (callable $get) {
                                $code = $get('payment_method');
                                if (!$code)
                                    return null;
                                $method = PaymentMethod::where('code', $code)->first();
                                return $method ? $method->provider : null;
                            }),
                        Forms\Components\KeyValue::make('payment_details')
                            ->label('Payment Details')
                            ->keyLabel('Field')
                            ->valueLabel('Value'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->nullable(),
                    ])
                    ->action(function (PlayerWallet $record, array $data) {
                        // Create a withdrawal transaction
                        $transaction = $record->player->createTransaction(
                            $data['amount'],
                            Transaction::TYPE_WITHDRAWAL,
                            null,
                            null,
                            $data['notes'] ?? 'Withdrawn via admin panel',
                            $data['payment_method'] ?? null,
                            $data['payment_provider'] ?? null,
                            $data['payment_details'] ?? null
                        );

                        // Mark it as completed
                        $transaction->markAsCompleted('Manually withdrawn by admin');

                        // Refresh the record
                        $record->refresh();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions needed
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
            'index' => Pages\ListPlayerWallets::route('/'),
            'view' => Pages\ViewPlayerWallet::route('/{record}'),
        ];
    }
}