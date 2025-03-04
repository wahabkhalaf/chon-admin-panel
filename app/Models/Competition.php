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

    protected static function boot()
    {
        parent::boot();

        // static::saving(function ($competition) {
        //     if ($competition->end_time <= $competition->start_time) {
        //         throw new \InvalidArgumentException('End time must be after start time');
        //     }
        //     if ($competition->entry_fee < 0) {
        //         throw new \InvalidArgumentException('Entry fee cannot be negative');
        //     }
        //     if ($competition->prize_pool < 0) {
        //         throw new \InvalidArgumentException('Prize pool cannot be negative');
        //     }
        //     if ($competition->max_users < 1) {
        //         throw new \InvalidArgumentException('Maximum users must be at least 1');
        //     }
        // });
    }
}
