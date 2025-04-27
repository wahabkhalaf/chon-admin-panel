<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Competition;
use App\Models\PaymentMethod;
use App\Models\Player;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Competition Entry Fees';

    protected static ?string $navigationGroup = 'Competitions';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\Select::make('player_id')
                            ->label('Player')
                            ->options(Player::all()->pluck('nickname', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('competition_id')
                            ->label('Competition')
                            ->options(Competition::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$'),
                        Forms\Components\Select::make('status')
                            ->options([
                                Transaction::STATUS_PENDING => 'Pending',
                                Transaction::STATUS_COMPLETED => 'Completed',
                                Transaction::STATUS_FAILED => 'Failed',
                            ])
                            ->default(Transaction::STATUS_PENDING)
                            ->required(),
                    ]),
                Forms\Components\Section::make('Payment Method')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options(PaymentMethod::all()->pluck('name', 'code'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\TextInput::make('payment_provider')
                            ->label('Payment Provider')
                            ->maxLength(50)
                            ->nullable(),
                        Forms\Components\KeyValue::make('payment_details')
                            ->label('Payment Details')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->nullable(),
                    ]),
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference_id')
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\Textarea::make('notes')
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Created At')
                            ->default(now())
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('updated_at')
                            ->label('Updated At')
                            ->nullable()
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('player.nickname')
                    ->label('Player')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('competition.name')
                    ->label('Competition')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Transaction::STATUS_PENDING => 'warning',
                        Transaction::STATUS_COMPLETED => 'success',
                        Transaction::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reference_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Transaction::STATUS_PENDING => 'Pending',
                        Transaction::STATUS_COMPLETED => 'Completed',
                        Transaction::STATUS_FAILED => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('player_id')
                    ->label('Player')
                    ->relationship('player', 'nickname')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('competition_id')
                    ->label('Competition')
                    ->relationship('competition', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options(fn() => PaymentMethod::pluck('name', 'code')->toArray())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_completed')
                    ->label('Mark as Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Transaction $record) => $record->status === Transaction::STATUS_PENDING)
                    ->action(function (Transaction $record) {
                        $record->markAsCompleted('Manually marked as completed by admin');
                    }),
                Tables\Actions\Action::make('mark_failed')
                    ->label('Mark as Failed')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Transaction $record) => $record->status === Transaction::STATUS_PENDING)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Failure Reason')
                            ->required(),
                    ])
                    ->action(function (Transaction $record, array $data) {
                        $record->markAsFailed($data['reason']);
                    }),
                Tables\Actions\ViewAction::make(),
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
            TransactionResource\RelationManagers\LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}