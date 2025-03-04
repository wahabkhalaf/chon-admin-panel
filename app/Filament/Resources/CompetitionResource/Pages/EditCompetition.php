<?php

namespace App\Filament\Resources\CompetitionResource\Pages;

use App\Filament\Resources\CompetitionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCompetition extends EditRecord
{
    protected static string $resource = CompetitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function ($action) {
                    if (!$this->record->canDelete()) {
                        $action->cancel();
                        Notification::make()
                            ->danger()
                            ->title('Cannot Delete Competition')
                            ->body('Active or completed competitions cannot be deleted.')
                            ->send();
                    }
                })
                ->visible(fn ($action) => $this->record->canDelete()), 
        ];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->refresh();
        return $data;
    }

    protected function beforeValidate(): void
    {
        $protectedFields = ['entry_fee', 'prize_pool', 'start_time', 'end_time', 'max_users'];

        if (in_array($this->record->status, ['active', 'completed'])) {
            foreach ($protectedFields as $field) {
                if ($this->data[$field] != $this->record->$field) {
                    $this->data[$field] = $this->record->$field;
                    Notification::make()
                        ->warning()
                        ->title('Field Not Editable')
                        ->body("Cannot modify $field for active/completed competitions")
                        ->send();
                }
            }
        }
    }
}
