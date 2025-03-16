<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Transaction Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Transaction ID'),
                        Infolists\Components\TextEntry::make('player.nickname')
                            ->label('Player'),
                        Infolists\Components\TextEntry::make('amount')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('transaction_type')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                Transaction::TYPE_DEPOSIT => 'success',
                                Transaction::TYPE_WITHDRAWAL => 'danger',
                                Transaction::TYPE_ENTRY_FEE => 'warning',
                                Transaction::TYPE_PRIZE => 'success',
                                Transaction::TYPE_BONUS => 'info',
                                Transaction::TYPE_REFUND => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                Transaction::STATUS_PENDING => 'warning',
                                Transaction::STATUS_COMPLETED => 'success',
                                Transaction::STATUS_FAILED => 'danger',
                                Transaction::STATUS_CANCELLED => 'gray',
                                Transaction::STATUS_REFUNDED => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('reference_id')
                            ->visible(fn($record) => !empty($record->reference_id)),
                    ])->columns(2),

                Infolists\Components\Section::make('Payment Method')
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Payment Method'),
                        Infolists\Components\TextEntry::make('payment_provider')
                            ->label('Payment Provider'),
                        Infolists\Components\KeyValueEntry::make('payment_details')
                            ->label('Payment Details'),
                    ])
                    ->visible(fn($record) => !empty($record->payment_method)),

                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])->columns(2),
            ]);
    }
}