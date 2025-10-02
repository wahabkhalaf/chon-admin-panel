<?php

namespace App\Traits;

trait HasKurdishTranslation
{
    /**
     * Get the question text in the specified language
     */
    public function getQuestionText(string $language = 'en'): string
    {
        if ($language === 'ar' && !empty($this->question_text_arabic)) {
            return $this->question_text_arabic;
        }

        if ($language === 'km' && !empty($this->question_text_kurmanji)) {
            return $this->question_text_kurmanji;
        }

        if ($language === 'ku' && !empty($this->question_text_kurdish)) {
            return $this->question_text_kurdish;
        }

        return $this->question_text;
    }

    /**
     * Get the options in the specified language
     */
    public function getOptions(string $language = 'en'): array
    {
        if ($language === 'ar' && !empty($this->options_arabic)) {
            return $this->options_arabic;
        }

        if ($language === 'km' && !empty($this->options_kurmanji)) {
            return $this->options_kurmanji;
        }

        if ($language === 'ku' && !empty($this->options_kurdish)) {
            return $this->options_kurdish;
        }

        return $this->options ?? [];
    }

    /**
     * Get the correct answer in the specified language
     */
    public function getCorrectAnswer(string $language = 'en'): string
    {
        if ($language === 'ar' && !empty($this->correct_answer_arabic)) {
            return $this->correct_answer_arabic;
        }

        if ($language === 'km' && !empty($this->correct_answer_kurmanji)) {
            return $this->correct_answer_kurmanji;
        }

        if ($language === 'ku' && !empty($this->correct_answer_kurdish)) {
            return $this->correct_answer_kurdish;
        }

        return $this->correct_answer;
    }

    /**
     * Get the name in the specified language
     */
    public function getName(string $language = 'en'): string
    {
        if ($language === 'ar' && !empty($this->name_arabic)) {
            return $this->name_arabic;
        }

        if ($language === 'km' && !empty($this->name_kurmanji)) {
            return $this->name_kurmanji;
        }

        if ($language === 'ku' && !empty($this->name_kurdish)) {
            return $this->name_kurdish;
        }

        return $this->name;
    }

    /**
     * Get the description in the specified language
     */
    public function getDescription(string $language = 'en'): ?string
    {
        if ($language === 'ar' && !empty($this->description_arabic)) {
            return $this->description_arabic;
        }

        if ($language === 'km' && !empty($this->description_kurmanji)) {
            return $this->description_kurmanji;
        }

        if ($language === 'ku' && !empty($this->description_kurdish)) {
            return $this->description_kurdish;
        }

        return $this->description;
    }

    /**
     * Get the instructions in the specified language
     */
    public function getInstructions(string $language = 'en'): ?string
    {
        if ($language === 'ar' && !empty($this->instructions_arabic)) {
            return $this->instructions_arabic;
        }

        if ($language === 'km' && !empty($this->instructions_kurmanji)) {
            return $this->instructions_kurmanji;
        }

        if ($language === 'ku' && !empty($this->instructions_kurdish)) {
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
     * Check if Arabic translation is available
     */
    public function hasArabicTranslation(): bool
    {
        return !empty($this->question_text_arabic) ||
            !empty($this->name_arabic) ||
            !empty($this->description_arabic) ||
            !empty($this->instructions_arabic);
    }

    /**
     * Check if Kurmanji translation is available
     */
    public function hasKurmanjiTranslation(): bool
    {
        return !empty($this->question_text_kurmanji) ||
            !empty($this->name_kurmanji) ||
            !empty($this->description_kurmanji) ||
            !empty($this->instructions_kurmanji);
    }

    /**
     * Get all available languages for this model
     */
    public function getAvailableLanguages(): array
    {
        $languages = ['en'];

        // Kurdish (Sorani)
        if ($this->hasKurdishTranslation()) {
            $languages[] = 'ku';
        }

        // Arabic (if Arabic fields exist on this model, e.g., Question)
        if ($this->hasArabicTranslation()) {
            $languages[] = 'ar';
        }

        // Kurmanji (if Kurmanji fields exist on this model, e.g., Question)
        if ($this->hasKurmanjiTranslation()) {
            $languages[] = 'km';
        }

        return $languages;
    }
}
