<?php

namespace App\Filament\Resources\PointsTransactionResource\Pages;

use App\Filament\Resources\PointsTransactionResource;
use App\Models\PlayerPointsBalance;
use App\Models\PointsTransaction;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePointsTransaction extends CreateRecord
{
    protected static string $resource = PointsTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Get or create the player's points balance
        $balance = PlayerPointsBalance::firstOrCreate(
            ['player_id' => $data['player_id']],
            ['current_balance' => 0, 'total_earned' => 0, 'total_spent' => 0]
        );

        $data['balance_before'] = $balance->current_balance;

        // Calculate balance after based on transaction type
        $isCredit = in_array($data['type'], [
            PointsTransaction::TYPE_PURCHASE,
            PointsTransaction::TYPE_ADMIN_CREDIT,
            PointsTransaction::TYPE_REFUND,
        ]);

        if ($isCredit) {
            $data['balance_after'] = $balance->current_balance + $data['amount'];
        } else {
            $data['balance_after'] = max(0, $balance->current_balance - $data['amount']);
        }

        // Set default reference type for admin actions
        if (empty($data['reference_type'])) {
            $data['reference_type'] = PointsTransaction::REF_ADMIN_ACTION;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        
        // Update the player's balance
        $balance = PlayerPointsBalance::firstOrCreate(
            ['player_id' => $record->player_id],
            ['current_balance' => 0, 'total_earned' => 0, 'total_spent' => 0]
        );

        $isCredit = in_array($record->type, [
            PointsTransaction::TYPE_PURCHASE,
            PointsTransaction::TYPE_ADMIN_CREDIT,
            PointsTransaction::TYPE_REFUND,
        ]);

        if ($isCredit) {
            $balance->current_balance += $record->amount;
            $balance->total_earned += $record->amount;
        } else {
            $balance->current_balance = max(0, $balance->current_balance - $record->amount);
            $balance->total_spent += $record->amount;
        }

        $balance->save();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
