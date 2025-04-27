<?php

namespace App\Filament\Resources\CompetitionResource\RelationManagers;

use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('player_id')
                    ->relationship('player', 'nickname')
                    ->searchable()
                    ->required()
                    ->label('Player'),

                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('IQD'),

                Forms\Components\Select::make('transaction_type')
                    ->options([
                        Transaction::TYPE_ENTRY_FEE => 'Entry Fee',
                        Transaction::TYPE_PRIZE => 'Prize',
                        Transaction::TYPE_REFUND => 'Refund',
                        Transaction::TYPE_BONUS => 'Bonus',
                    ])
                    ->required()
                    ->default(Transaction::TYPE_ENTRY_FEE),

                Forms\Components\Select::make('status')
                    ->options([
                        Transaction::STATUS_PENDING => 'Pending',
                        Transaction::STATUS_COMPLETED => 'Completed',
                        Transaction::STATUS_FAILED => 'Failed',
                        Transaction::STATUS_CANCELLED => 'Cancelled',
                        Transaction::STATUS_REFUNDED => 'Refunded',
                    ])
                    ->required()
                    ->default(Transaction::STATUS_COMPLETED),

                Forms\Components\Select::make('payment_method')
                    ->relationship('paymentMethodModel', 'name')
                    ->preload()
                    ->label('Payment Method'),

                Forms\Components\TextInput::make('payment_provider')
                    ->maxLength(255),

                Forms\Components\TextInput::make('reference_id')
                    ->maxLength(255),

                Forms\Components\Textarea::make('notes')
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('player.nickname')
                    ->label('Player')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('IQD')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('transaction_type')
                    ->label('Type')
                    ->formatStateUsing(fn($state) => match ($state) {
                        Transaction::TYPE_ENTRY_FEE => 'Entry Fee',
                        Transaction::TYPE_PRIZE => 'Prize',
                        Transaction::TYPE_REFUND => 'Refund',
                        Transaction::TYPE_BONUS => 'Bonus',
                        default => ucfirst(str_replace('_', ' ', $state))
                    })
                    ->colors([
                        'danger' => fn($state) => $state === Transaction::TYPE_ENTRY_FEE,
                        'success' => fn($state) => in_array($state, [Transaction::TYPE_PRIZE, Transaction::TYPE_BONUS]),
                        'info' => fn($state) => $state === Transaction::TYPE_REFUND,
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->colors([
                        'gray' => fn($state) => $state === Transaction::STATUS_PENDING,
                        'success' => fn($state) => $state === Transaction::STATUS_COMPLETED,
                        'danger' => fn($state) => in_array($state, [Transaction::STATUS_FAILED, Transaction::STATUS_CANCELLED]),
                        'warning' => fn($state) => $state === Transaction::STATUS_REFUNDED,
                    ]),

                Tables\Columns\TextColumn::make('payment_method')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->getPaymentMethodDisplay();
                    })
                    ->label('Payment Method'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->options([
                        Transaction::TYPE_ENTRY_FEE => 'Entry Fee',
                        Transaction::TYPE_PRIZE => 'Prize',
                        Transaction::TYPE_REFUND => 'Refund',
                        Transaction::TYPE_BONUS => 'Bonus',
                    ])
                    ->label('Transaction Type'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Transaction::STATUS_PENDING => 'Pending',
                        Transaction::STATUS_COMPLETED => 'Completed',
                        Transaction::STATUS_FAILED => 'Failed',
                        Transaction::STATUS_CANCELLED => 'Cancelled',
                        Transaction::STATUS_REFUNDED => 'Refunded',
                    ]),

                Tables\Filters\Filter::make('completed')
                    ->query(fn(Builder $query): Builder => $query->completed())
                    ->label('Completed Transactions Only'),

                Tables\Filters\Filter::make('pending')
                    ->query(fn(Builder $query): Builder => $query->pending())
                    ->label('Pending Transactions Only'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('Mark as Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Transaction $record) => $record->isPending())
                    ->action(function (Transaction $record) {
                        $record->markAsCompleted('Manually marked as completed by admin');
                    }),
                Tables\Actions\Action::make('view_logs')
                    ->label('View Logs')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(fn(Transaction $record) => "/admin/transaction-logs?filter[transaction_id]={$record->id}")
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}