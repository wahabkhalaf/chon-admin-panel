<?php

namespace App\Filament\Resources\PlayerWalletResource\Pages;

use App\Filament\Resources\PlayerWalletResource;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewPlayerWallet extends ViewRecord
{
    protected static string $resource = PlayerWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_funds')
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
                ->action(function (array $data) {
                    // Create a deposit transaction
                    $transaction = $this->record->player->createTransaction(
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
                    $this->record->refresh();
                }),
            Actions\Action::make('withdraw_funds')
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
                            fn(): \Closure => function (string $attribute, $value, \Closure $fail) {
                                if ($value > $this->record->balance) {
                                    $fail("The amount cannot exceed the current balance of $" . number_format($this->record->balance, 2));
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
                ->action(function (array $data) {
                    // Create a withdrawal transaction
                    $transaction = $this->record->player->createTransaction(
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
                    $this->record->refresh();
                }),
        ];
    }
}