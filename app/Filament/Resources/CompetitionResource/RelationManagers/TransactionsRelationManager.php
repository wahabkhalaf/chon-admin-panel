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

    protected static ?string $title = 'Entry Fee Payments';

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

                Forms\Components\Select::make('status')
                    ->options([
                        Transaction::STATUS_PENDING => 'Pending',
                        Transaction::STATUS_COMPLETED => 'Completed',
                        Transaction::STATUS_FAILED => 'Failed',
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
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => 
                $query->with(['player:id,nickname', 'paymentMethodModel:id,code,name'])
            )
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

                Tables\Columns\BadgeColumn::make('status')
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->colors([
                        'gray' => fn($state) => $state === Transaction::STATUS_PENDING,
                        'success' => fn($state) => $state === Transaction::STATUS_COMPLETED,
                        'danger' => fn($state) => $state === Transaction::STATUS_FAILED,
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Transaction::STATUS_PENDING => 'Pending',
                        Transaction::STATUS_COMPLETED => 'Completed',
                        Transaction::STATUS_FAILED => 'Failed',
                    ]),

                Tables\Filters\Filter::make('completed')
                    ->query(fn(Builder $query): Builder => $query->completed())
                    ->label('Completed Transactions Only'),

                Tables\Filters\Filter::make('pending')
                    ->query(fn(Builder $query): Builder => $query->pending())
                    ->label('Pending Transactions Only'),

                Tables\Filters\Filter::make('failed')
                    ->query(fn(Builder $query): Builder => $query->failed())
                    ->label('Failed Transactions Only'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
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
                    ->url(fn(Transaction $record) => route('filament.admin.resources.transactions.view', ['record' => $record->id]) . '?activeRelationManager=logs')
                    ->openUrlInNewTab(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}