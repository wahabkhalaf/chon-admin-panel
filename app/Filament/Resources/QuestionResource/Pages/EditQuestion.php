<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn(): bool => $this->record->canEdit())
                ->before(function (Actions\DeleteAction $action): void {
                    // Double-check if the question can be deleted (redundant with visible condition but kept for safety)
                    if (!$this->record->canEdit()) {
                        Notification::make()
                            ->title('Cannot delete question')
                            ->body('This question is attached to competitions that are open for registration or already active. Questions in active competitions cannot be deleted.')
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Check if the question can be edited
        if (!$this->record->canEdit()) {
            // Redirect back with notification
            Notification::make()
                ->title('Cannot edit question')
                ->body('This question is attached to competitions that are open for registration or already active. Questions in active competitions cannot be modified.')
                ->danger()
                ->persistent()
                ->send();

            // Redirect to the list page
            $this->redirect(QuestionResource::getUrl());
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['question_type'] === 'multi_choice') {
            // Validate English options
            $options = $data['options'] ?? [];
            $correctAnswer = null;
            $hasCorrect = false;
            foreach ($options as $option) {
                if ($option['is_correct']) {
                    $correctAnswer = $option['option'];
                    $hasCorrect = true;
                    break;
                }
            }

            if (!$hasCorrect) {
                Notification::make()
                    ->title('Validation Error')
                    ->body('At least one English option must be marked as correct.')
                    ->danger()
                    ->send();

                $this->halt();
            }
            $data['correct_answer'] = $correctAnswer;

            // Validate Kurdish options
            $optionsKurdish = $data['options_kurdish'] ?? [];
            $correctAnswerKurdish = null;
            $hasCorrectKurdish = false;
            foreach ($optionsKurdish as $option) {
                if ($option['is_correct']) {
                    $correctAnswerKurdish = $option['option'];
                    $hasCorrectKurdish = true;
                    break;
                }
            }

            if (count($optionsKurdish) > 0 && !$hasCorrectKurdish) {
                Notification::make()
                    ->title('Validation Error')
                    ->body('At least one Kurdish option must be marked as correct.')
                    ->danger()
                    ->send();

                $this->halt();
            }
            $data['correct_answer_kurdish'] = $correctAnswerKurdish;
        }

        return $data;
    }
}
