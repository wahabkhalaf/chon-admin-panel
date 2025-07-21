<?php

namespace App\Models;

use App\Traits\HasKurdishTranslation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id id of the competition
 * @property string $name Name of the competition
 * @property string|null $description Description of the competition
 * @property float $entry_fee Entry fee for participation
 * @property \DateTime $open_time Registration opening time
 * @property \DateTime $start_time Competition start time
 * @property \DateTime $end_time Competition end time
 * @property int $max_users Maximum number of users allowed
 * @property string $game_type Type of game for this competition
 * @property \DateTime $created_at Timestamp of creation
 * @property \DateTime $updated_at Timestamp of last update
 */
class Competition extends Model
{
    use HasFactory, HasKurdishTranslation;

    // Competition uses auto-incrementing integer IDs

    protected $fillable = [
        'name',
        'name_kurdish',
        'description',
        'description_kurdish',
        'entry_fee',
        'open_time',
        'start_time',
        'end_time',
        'max_users',
        'game_type'
    ];

    protected $casts = [
        'entry_fee' => 'decimal:2',
        'open_time' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];

    public function getStatus(): string
    {
        $current = now();

        if ($current < $this->open_time) {
            return 'upcoming';
        } elseif ($current >= $this->open_time && $current < $this->start_time) {
            return 'open';
        } elseif ($current >= $this->start_time && $current < $this->end_time) {
            return 'active';
        } else {
            return 'completed';
        }
    }

    public function isOpen(): bool
    {
        return $this->getStatus() === 'open';
    }

    public function isActive(): bool
    {
        return $this->getStatus() === 'active';
    }

    public function isUpcoming(): bool
    {
        return $this->getStatus() === 'upcoming';
    }

    public function isCompleted(): bool
    {
        return $this->getStatus() === 'completed';
    }

    public static function getOpenCompetitionsCount(): int
    {
        return static::where('open_time', '<=', now())
            ->where('start_time', '>', now())
            ->count();
    }

    public static function getActiveCompetitionsCount(): int
    {
        return static::where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->count();
    }

    public static function getUpcomingCompetitionsCount(): int
    {
        return static::where('open_time', '>', now())
            ->count();
    }

    public static function getCompletedCompetitionsCount(): int
    {
        return static::where('end_time', '<', now())
            ->count();
    }

    public static function getAverageEntryFee(): float
    {
        $activeCompetitions = static::where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->get();

        if ($activeCompetitions->isEmpty()) {
            return 0;
        }

        return $activeCompetitions->avg('entry_fee') ?? 0;
    }

    public static function getMostPopularGameType(): string
    {
        $counts = static::select('game_type')
            ->selectRaw('count(*) as count')
            ->groupBy('game_type')
            ->orderByDesc('count')
            ->first();

        return $counts ? ucfirst($counts->game_type) : 'None';
    }

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($competition) {
            // Validate time sequence
            if ($competition->start_time <= $competition->open_time) {
                throw new \InvalidArgumentException('Start time must be after registration open time');
            }

            if ($competition->end_time <= $competition->start_time) {
                throw new \InvalidArgumentException('End time must be after start time');
            }

            if ($competition->entry_fee < 0) {
                $competition->entry_fee = 0;
            }
        });
    }

    /**
     * Determine if the competition can be deleted
     */
    public function canDelete(): bool
    {
        // Can only delete if competition is 'upcoming'
        if (!$this->isUpcoming()) {
            return false;
        }

        return true;
    }

    public function canEditField(string $field): bool
    {
        // Only allow editing if competition is upcoming
        // For all other statuses (open, active, completed), no fields can be edited
        return $this->isUpcoming();
    }

    /**
     * The questions associated with the competition.
     */
    public function questions()
    {
        return $this->belongsToMany(Question::class, 'competitions_questions')
            ->withTimestamps();
    }

    /**
     * Get the player answers for this competition.
     */
    public function competitionPlayerAnswers()
    {
        return $this->hasMany(CompetitionPlayerAnswer::class, 'competition_id');
    }

    /**
     * Get the leaderboard entries for this competition.
     */
    public function competitionLeaderboard()
    {
        return $this->hasMany(CompetitionLeaderboard::class, 'competition_id');
    }

    /**
     * Get the prize tiers for this competition.
     */
    public function prizeTiers()
    {
        return $this->hasMany(PrizeTier::class, 'competition_id')->orderBy('rank_from');
    }

    /**
     * Get the transactions for this competition.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'competition_id');
    }

    public function setOpenTimeAttribute($value)
    {
        $this->attributes['open_time'] = \Carbon\Carbon::parse($value, 'Asia/Baghdad')->format('Y-m-d H:i:s');
    }

    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = \Carbon\Carbon::parse($value, 'Asia/Baghdad')->format('Y-m-d H:i:s');
    }

    public function setEndTimeAttribute($value)
    {
        $this->attributes['end_time'] = \Carbon\Carbon::parse($value, 'Asia/Baghdad')->format('Y-m-d H:i:s');
    }
}
