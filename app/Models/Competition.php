<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * @property string $id id of the competition
 * @property string $name Name of the competition
 * @property string|null $description Description of the competition
 * @property float $entry_fee Entry fee for participation
 * @property float $prize_pool Total prize pool amount
 * @property \DateTime $start_time Competition start time
 * @property \DateTime $end_time Competition end time
 * @property int $max_users Maximum number of users allowed
 * @property string $status Competition status (upcoming/active/completed/closed)
 * @property \DateTime $created_at Timestamp of creation
 * @property \DateTime $updated_at Timestamp of last update
 */
class Competition extends Model
{
    use HasFactory;

    // Competition uses auto-incrementing integer IDs

    protected $fillable = [
        'name',
        'description',
        'entry_fee',
        'prize_pool',
        'start_time',
        'end_time',
        'max_users',
        'status'
    ];

    protected $casts = [
        'entry_fee' => 'decimal:2',
        'prize_pool' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];

    public function isActive(): bool
    {
        $now = Carbon::now();
        return $this->status === 'active' && 
               $now->between($this->start_time, $this->end_time);
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'upcoming' && 
               Carbon::now()->lt($this->start_time);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' || 
               Carbon::now()->gt($this->end_time);
    }

    public static function getActiveCompetitionsCount(): int
    {
        return static::where('status', 'active')
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->count();
    }

    public static function getUpcomingCompetitionsCount(): int
    {
        return static::where('status', 'upcoming')
            ->where('start_time', '>', now())
            ->count();
    }

    public static function getTotalPrizePool(): float
    {
        return static::where('status', 'active')
            ->sum('prize_pool');
    }

    public static function getAverageEntryFee(): float
    {
        return static::where('status', 'active')
            ->avg('entry_fee') ?? 0;
    }

    public static function getCompletedCompetitionsCount(): int
    {
        return static::where('status', 'completed')
            ->count();
    }

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($competition) {
            if ($competition->end_time <= $competition->start_time) {
                throw new \InvalidArgumentException('End time must be after start time');
            }
            if ($competition->entry_fee < 0) {
                $competition->entry_fee = 0;
            }
            if ($competition->prize_pool < 0) {
                $competition->prize_pool = 0;
            }
        });
    }
    /**
     * Determine if the competition can be deleted
     */
    public function canDelete(): bool
    {
        // Can only delete if competition is 'upcoming' or 'closed'
        if ($this->status === 'active' || $this->status === 'completed') {
            return false;
        }

        // Additional check for time-based constraints
        $now = Carbon::now();
        
        // Cannot delete if competition has already started
        if ($now->gte($this->start_time)) {
            return false;
        }

        return true;
    }
    public function canEditField(string $field): bool
    {
        if (in_array($this->status, ['active', 'completed'])) {
            // List of protected fields when competition is active/completed
            $protectedFields = [
                'entry_fee',
                'prize_pool',
                'start_time',
                'end_time',
                'max_users'
            ];
            return !in_array($field, $protectedFields);
        }
        return true;
    }
}
