<?php

namespace App\Filament\Resources\CompetitionResource\Pages;

use App\Filament\Resources\CompetitionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\QueryException;

class CreateCompetition extends CreateRecord
{
    protected static string $resource = CompetitionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        try {
            // Validate start time is after open time
            if ($this->data['start_time'] <= $this->data['open_time']) {
                throw new \InvalidArgumentException('Start time must be after registration open time');
            }

            // Validate end time is after start time
            if ($this->data['end_time'] <= $this->data['start_time']) {
                throw new \InvalidArgumentException('End time must be after start time');
            }

            // Ensure non-negative values
            if ($this->data['entry_fee'] < 0) {
                $this->data['entry_fee'] = 0;
            }

            // Ensure max_users is at least 1
            if ($this->data['max_users'] < 1) {
                $this->data['max_users'] = 1;
            }
        } catch (\InvalidArgumentException $e) {
            $this->halt();

            // Add form error
            if (strpos($e->getMessage(), 'Start time') !== false) {
                $this->addError('data.start_time', $e->getMessage());
            } elseif (strpos($e->getMessage(), 'End time') !== false) {
                $this->addError('data.end_time', $e->getMessage());
            }

            // Show notification
            Notification::make()
                ->danger()
                ->title('Invalid Time Range')
                ->body($e->getMessage())
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
                ->danger()
                ->title('Database Error')
                ->body('Please try again.')
                ->send();
            return;
        }

        if ($exception instanceof \InvalidArgumentException) {
            $this->halt();

            // Add form error based on the message
            if (strpos($exception->getMessage(), 'Start time') !== false) {
                $this->addError('data.start_time', $exception->getMessage());
            } elseif (strpos($exception->getMessage(), 'End time') !== false) {
                $this->addError('data.end_time', $exception->getMessage());
            }

            // Show notification
            Notification::make()
                ->danger()
                ->title('Invalid Time Range')
                ->body($exception->getMessage())
                ->send();

            return;
        }

        parent::handleRecordCreationException($exception);
    }
}