<?php

namespace App\Filament\Resources\CompetitionResource\RelationManagers;

use App\Models\CompetitionRegistration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'Registrations';

    protected static ?string $modelLabel = 'Registration';

    protected static ?string $pluralModelLabel = 'Registrations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('player_id')
                    ->relationship('player', 'nickname')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('registration_status')
                    ->options([
                        CompetitionRegistration::STATUS_PENDING_PAYMENT => 'Pending Payment',
                        CompetitionRegistration::STATUS_PAYMENT_PROCESSING => 'Payment Processing',
                        CompetitionRegistration::STATUS_REGISTERED => 'Registered',
                        CompetitionRegistration::STATUS_PAYMENT_FAILED => 'Payment Failed',
                        CompetitionRegistration::STATUS_CANCELLED => 'Cancelled',
                        CompetitionRegistration::STATUS_REFUNDED => 'Refunded',
                        CompetitionRegistration::STATUS_EXPIRED => 'Expired',
                    ])
                    ->required()
                    ->default(CompetitionRegistration::STATUS_PENDING_PAYMENT),

                Forms\Components\TextInput::make('entry_fee_paid')
                    ->numeric()
                    ->prefix('IQD')
                    ->default(0)
                    ->minValue(0),

                Forms\Components\Toggle::make('is_free_entry')
                    ->label('Free Entry')
                    ->default(false),

                Forms\Components\DateTimePicker::make('registered_at')
                    ->label('Registered At'),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Payment Expires At'),

                Forms\Components\Select::make('registration_source')
                    ->options([
                        CompetitionRegistration::SOURCE_MOBILE_APP => 'Mobile App',
                        CompetitionRegistration::SOURCE_WEB => 'Web',
                        CompetitionRegistration::SOURCE_ADMIN => 'Admin',
                    ])
                    ->default(CompetitionRegistration::SOURCE_MOBILE_APP),

                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Forms\Components\Select::make('transaction_id')
                    ->relationship('transaction', 'reference_id')
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('player.nickname')
                    ->label('Player')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('player.whatsapp_number')
                    ->label('WhatsApp')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('registration_status')
                    ->label('Status')
                    ->formatStateUsing(fn($record) => $record->getStatusLabel())
                    ->colors([
                        'success' => CompetitionRegistration::STATUS_REGISTERED,
                        'warning' => [
                            CompetitionRegistration::STATUS_PENDING_PAYMENT,
                            CompetitionRegistration::STATUS_PAYMENT_PROCESSING,
                        ],
                        'danger' => [
                            CompetitionRegistration::STATUS_PAYMENT_FAILED,
                            CompetitionRegistration::STATUS_EXPIRED,
                            CompetitionRegistration::STATUS_CANCELLED,
                        ],
                        'info' => CompetitionRegistration::STATUS_REFUNDED,
                    ]),

                Tables\Columns\TextColumn::make('entry_fee_paid')
                    ->label('Fee Paid')
                    ->money('IQD')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_free_entry')
                    ->label('Free Entry')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('registered_at')
                    ->label('Registered At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Payment Expires')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('registration_source')
                    ->label('Source')
                    ->formatStateUsing(fn($state) => match ($state) {
                        CompetitionRegistration::SOURCE_MOBILE_APP => 'Mobile App',
                        CompetitionRegistration::SOURCE_WEB => 'Web',
                        CompetitionRegistration::SOURCE_ADMIN => 'Admin',
                        default => ucfirst($state),
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('transaction.reference_id')
                    ->label('Transaction Ref')
                    ->searchable()
                    ->toggleable()
                    ->limit(15),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('registration_status')
                    ->options([
                        CompetitionRegistration::STATUS_PENDING_PAYMENT => 'Pending Payment',
                        CompetitionRegistration::STATUS_PAYMENT_PROCESSING => 'Payment Processing',
                        CompetitionRegistration::STATUS_REGISTERED => 'Registered',
                        CompetitionRegistration::STATUS_PAYMENT_FAILED => 'Payment Failed',
                        CompetitionRegistration::STATUS_CANCELLED => 'Cancelled',
                        CompetitionRegistration::STATUS_REFUNDED => 'Refunded',
                        CompetitionRegistration::STATUS_EXPIRED => 'Expired',
                    ]),

                Tables\Filters\SelectFilter::make('registration_source')
                    ->options([
                        CompetitionRegistration::SOURCE_MOBILE_APP => 'Mobile App',
                        CompetitionRegistration::SOURCE_WEB => 'Web',
                        CompetitionRegistration::SOURCE_ADMIN => 'Admin',
                    ]),

                Tables\Filters\TernaryFilter::make('is_free_entry')
                    ->label('Free Entry'),

                Tables\Filters\Filter::make('has_transaction')
                    ->query(fn($query) => $query->whereNotNull('transaction_id'))
                    ->label('Has Transaction'),

                Tables\Filters\Filter::make('expired_payments')
                    ->query(fn($query) => $query->expired())
                    ->label('Expired Payments'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                // Quick action buttons for status changes
                Tables\Actions\Action::make('mark_registered')
                    ->label('Mark Registered')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn(CompetitionRegistration $record) => $record->markAsRegistered())
                    ->visible(fn(CompetitionRegistration $record) => !$record->isRegistered())
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('mark_failed')
                    ->label('Mark Failed')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn(CompetitionRegistration $record) => $record->markAsPaymentFailed())
                    ->visible(fn(CompetitionRegistration $record) => $record->isPendingPayment())
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->action(fn(CompetitionRegistration $record) => $record->cancel())
                    ->visible(fn(CompetitionRegistration $record) => !$record->isCancelled() && !$record->isRegistered())
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}