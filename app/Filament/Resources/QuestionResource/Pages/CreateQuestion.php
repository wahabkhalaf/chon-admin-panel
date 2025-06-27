<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
            // we will only validate if there is at least one kurdish option
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
