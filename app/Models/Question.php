<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Question extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'question_text',
        'question_type',
        'options',
        'correct_answer',
        'level',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'options' => 'array',
    ];

    /**
     * The question types available.
     */
    public const TYPES = [
        'multi_choice' => 'Multiple Choice',
        'puzzle' => 'Puzzle',
        'pattern_recognition' => 'Pattern Recognition',
        'true_false' => 'True/False',
        'math' => 'Math Problem',
    ];

    /**
     * The available difficulty levels.
     */
    public const LEVELS = [
        'easy' => 'Easy',
        'medium' => 'Medium',
        'hard' => 'Hard',
    ];

    /**
     * The competitions that include this question.
     */
    public function competitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class, 'competitions_questions')
            ->withTimestamps();
    }

    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($question) {
            // Set default empty array for options if not provided
            if (!isset($question->options)) {
                $question->options = [];
            }
        });
    }
}
