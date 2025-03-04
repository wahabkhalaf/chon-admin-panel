<?php

namespace App\Filament\Resources\CompetitionResource\Pages;

use App\Filament\Resources\CompetitionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\QueryException;
use Filament\Notifications\Notification;

class CreateCompetition extends CreateRecord
{
    protected static string $resource = CompetitionResource::class;

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {

        // Validate end time is after start time
        if ($this->data['end_time'] <= $this->data['start_time']) {
            $this->addError('data.end_time', 'End time must be after start time');
            $this->halt();
        }

        // Ensure non-negative values
        if ($this->data['entry_fee'] < 0) {
            $this->data['entry_fee'] = 0;
        }
        if ($this->data['prize_pool'] < 0) {
            $this->data['prize_pool'] = 0;
        }
        $data = $this->data;
        if ($data['end_time'] <= $data['start_time']) {
            $this->halt();
            Notification::make()
                ->error()
                ->title('Invalid Time Range')
                ->body('End time must be after start time.')
                ->send();
            return;
        }
    }

    // protected function afterCreate(): void
    // {
    //     Notification::make()
    //         ->success()
    //         ->title('Competition Created')
    //         ->body('Competition has been created successfully.')
    //         ->send();
    // }

    protected function handleRecordCreationException(\Exception $exception): void
    {
        if ($exception instanceof QueryException) {
            Notification::make()
                ->error()
                ->title('Database Error')
                ->body('Please try again.')
                ->send();
            return;
        }

        parent::handleRecordCreationException($exception);
    }
}