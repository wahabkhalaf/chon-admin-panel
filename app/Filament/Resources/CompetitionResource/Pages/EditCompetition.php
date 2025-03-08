<?php

namespace App\Filament\Resources\CompetitionResource\Pages;

use App\Filament\Resources\CompetitionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\ActionSize;

class EditCompetition extends EditRecord
{
    protected static string $resource = CompetitionResource::class;

    protected function getHeaderActions(): array
    {
        $status = $this->record->getStatus();
        $statusLabel = match ($status) {
            'upcoming' => 'Upcoming',
            'open' => 'Registration Open',
            'active' => 'Active',
            'completed' => 'Completed',
            default => ucfirst($status)
        };

        $statusColor = match ($status) {
            'upcoming' => 'warning',
            'open' => 'info',
            'active' => 'success',
            'completed' => 'secondary',
            default => 'gray'
        };

        return [
            // Add a status badge as a header action
            Actions\Action::make('status')
                ->label($statusLabel)
                ->color($statusColor)
                ->icon(match ($status) {
                    'upcoming' => 'heroicon-o-clock',
                    'open' => 'heroicon-o-clipboard-document-list',
                    'active' => 'heroicon-o-play',
                    'completed' => 'heroicon-o-check-badge',
                    default => 'heroicon-o-information-circle'
                })
                ->size(ActionSize::Small)
                ->disabled()
                ->extraAttributes([
                    'class' => 'cursor-default',
                ]),

            Actions\DeleteAction::make()
                ->before(function ($action) {
                    if (!$this->record->canDelete()) {
                        $action->cancel();
                        Notification::make()
                            ->danger()
                            ->title('Cannot Delete Competition')
                            ->body('Active, open for registration, or completed competitions cannot be deleted.')
                            ->send();
                    }
                })
                ->visible(fn($action) => $this->record->canDelete()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->refresh();

        // Show a banner notification if the competition is not upcoming
        if (!$this->record->isUpcoming()) {
            $statusText = $this->record->isOpen() ? 'open for registration' :
                ($this->record->isActive() ? 'active' : 'completed');

            Notification::make()
                ->warning()
                ->title('Read-Only Mode')
                ->body("This competition is $statusText and cannot be edited. You can only view its details.")
                ->persistent()
                ->send();
        }

        return $data;
    }

    protected function beforeValidate(): void
    {
        // If competition is not upcoming, no fields can be edited
        if (!$this->record->isUpcoming()) {
            $allFields = ['name', 'description', 'game_type', 'entry_fee', 'open_time', 'start_time', 'end_time', 'max_users'];

            foreach ($allFields as $field) {
                if (isset($this->data[$field]) && $this->data[$field] != $this->record->$field) {
                    $this->data[$field] = $this->record->$field;

                    $statusText = $this->record->isOpen() ? 'open for registration' :
                        ($this->record->isActive() ? 'active' : 'completed');

                    Notification::make()
                        ->warning()
                        ->title('Competition Not Editable')
                        ->body("Cannot modify any fields for competitions that are $statusText. Only upcoming competitions can be edited.")
                        ->send();
                }
            }

            // After showing the notification once, return to prevent further validation
            // This ensures the user only sees one notification
            if (array_diff_assoc($this->data, $this->record->toArray())) {
                $this->halt();
                return;
            }
        }

        // Only validate time relationships for upcoming competitions
        if ($this->record->isUpcoming()) {
            try {
                // Validate start time is after open time
                if (isset($this->data['start_time']) && isset($this->data['open_time'])) {
                    if ($this->data['start_time'] <= $this->data['open_time']) {
                        throw new \InvalidArgumentException('Start time must be after registration open time');
                    }
                }

                // Validate end time is after start time
                if (isset($this->data['end_time']) && isset($this->data['start_time'])) {
                    if ($this->data['end_time'] <= $this->data['start_time']) {
                        throw new \InvalidArgumentException('End time must be after start time');
                    }
                }

                // Ensure non-negative values
                if (isset($this->data['entry_fee']) && $this->data['entry_fee'] < 0) {
                    $this->data['entry_fee'] = 0;
                }

                // Ensure max_users is at least 1
                if (isset($this->data['max_users']) && $this->data['max_users'] < 1) {
                    $this->data['max_users'] = 1;
                }
            } catch (\InvalidArgumentException $e) {
                // Add form error based on the message
                if (strpos($e->getMessage(), 'Start time') !== false) {
                    $this->addError('start_time', $e->getMessage());
                } elseif (strpos($e->getMessage(), 'End time') !== false) {
                    $this->addError('end_time', $e->getMessage());
                }

                // Show notification
                Notification::make()
                    ->danger()
                    ->title('Invalid Time Range')
                    ->body($e->getMessage())
                    ->send();
            }
        }
    }

    // Disable the Save button when the competition is not upcoming
    protected function getSaveFormAction(): Actions\Action
    {
        $isUpcoming = $this->record->isUpcoming();

        $action = parent::getSaveFormAction()
            ->disabled(!$isUpcoming)
            ->label(function () use ($isUpcoming) {
                if (!$isUpcoming) {
                    return 'Cannot Save (Read-Only)';
                }
                return 'Save changes';
            });

        // Add color to the button based on status
        if (!$isUpcoming) {
            $statusText = $this->record->getStatus();

            $color = match ($statusText) {
                'open' => 'info',
                'active' => 'success',
                'completed' => 'secondary',
                default => 'warning'
            };

            $action->color($color);
        }

        return $action;
    }

    // Add a status badge to the header
    protected function getHeaderWidgets(): array
    {
        return [
            // You can add widgets here if needed
        ];
    }
}
