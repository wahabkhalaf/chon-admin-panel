<?php

namespace App\Traits;

trait HasKurdishTranslation
{
    /**
     * Get the question text in the specified language
     */
    public function getQuestionText(string $language = 'en'): string
    {
        if ($language === 'ku' && $this->question_text_kurdish) {
            return $this->question_text_kurdish;
        }

        return $this->question_text;
    }

    /**
     * Get the options in the specified language
     */
    public function getOptions(string $language = 'en'): array
    {
        if ($language === 'ku' && $this->options_kurdish) {
            return $this->options_kurdish;
        }

        return $this->options ?? [];
    }

    /**
     * Get the correct answer in the specified language
     */
    public function getCorrectAnswer(string $language = 'en'): string
    {
        if ($language === 'ku' && $this->correct_answer_kurdish) {
            return $this->correct_answer_kurdish;
        }

        return $this->correct_answer;
    }

    /**
     * Get the name in the specified language
     */
    public function getName(string $language = 'en'): string
    {
        if ($language === 'ku' && $this->name_kurdish) {
            return $this->name_kurdish;
        }

        return $this->name;
    }

    /**
     * Get the description in the specified language
     */
    public function getDescription(string $language = 'en'): ?string
    {
        if ($language === 'ku' && $this->description_kurdish) {
            return $this->description_kurdish;
        }

        return $this->description;
    }

    /**
     * Get the instructions in the specified language
     */
    public function getInstructions(string $language = 'en'): ?string
    {
        if ($language === 'ku' && $this->instructions_kurdish) {
            return $this->instructions_kurdish;
        }

        return $this->instructions;
    }

    /**
     * Check if Kurdish translation is available
     */
    public function hasKurdishTranslation(): bool
    {
        return !empty($this->question_text_kurdish) ||
            !empty($this->name_kurdish) ||
            !empty($this->description_kurdish) ||
            !empty($this->instructions_kurdish);
    }

    /**
     * Get all available languages for this model
     */
    public function getAvailableLanguages(): array
    {
        $languages = ['en'];

        if ($this->hasKurdishTranslation()) {
            $languages[] = 'ku';
        }

        return $languages;
    }
}