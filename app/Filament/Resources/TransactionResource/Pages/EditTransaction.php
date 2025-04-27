<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        // Log the update action
        $this->record->logAction('updated', 'Updated via admin panel');

        // If the status was changed to completed, perform any necessary actions
        if ($this->record->isDirty('status') && $this->record->isCompleted()) {
            // Competition entry fee has been paid
            // You could add additional logic here if needed
        }
    }
}