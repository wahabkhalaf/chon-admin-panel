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
        return $this->competitions()->whereIn('competitions.id', function ($query) {
            $query->select('id')
                ->from('competitions')
                ->where('open_time', '>', now());
        })->get();
    }

    /**
     * Determine if the question can be edited.
     * 
     * @return bool
     */
    public function canEdit(): bool
    {
        // Check if the question is attached to any active or started competitions
        $now = now();

        // Count competitions where:
        // 1. Competition has already started but not ended (start_time <= now < end_time) OR
        // 2. Competition is open for registration (open_time <= now < start_time)
        $activeOrOpenCompetitionsCount = $this->competitions()
            ->where(function ($query) use ($now) {
                $query->where(function ($query) use ($now) {
                    // Already started but not ended
                    $query->where('start_time', '<=', $now)
                        ->where('end_time', '>', $now);
                })
                    ->orWhere(function ($query) use ($now) {
                        // Open for registration
                        $query->where('open_time', '<=', $now)
                            ->where('start_time', '>', $now);
                    });
            })
            ->count();

        // Question can be edited only if it's not attached to any active or open competitions
        return $activeOrOpenCompetitionsCount === 0;
    }
}
