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

    /**
     * Check if the question is new (less than 7 days old)
     */
    public function isNew(): bool
    {
        return $this->created_at->diffInDays(now()) < 7;
    }

    /**
     * Count competitions this question is attached to
     */
    public function getCompetitionsCountAttribute(): int
    {
        return $this->competitions()->count();
    }

    /**
     * Get all upcoming competitions this question is attached to
     */
    public function getUpcomingCompetitionsAttribute()
    {
        return $this->competitions()->whereIn('id', function ($query) {
            $query->select('id')
                ->from('competitions')
                ->where('open_time', '>', now());
        })->get();
    }

    /**
     * Check if the question can be edited
     * Only allow editing if not attached to any non-upcoming competitions
     */
    public function canEdit(): bool
    {
        // Get count of non-upcoming competitions this question is attached to
        $nonUpcomingCount = $this->competitions()->whereIn('id', function ($query) {
            $query->select('id')
                ->from('competitions')
                ->where('open_time', '<=', now());
        })->count();

        // Can edit if not attached to any non-upcoming competitions
        return $nonUpcomingCount === 0;
    }
}
