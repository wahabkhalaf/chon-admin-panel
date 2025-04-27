<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrizeTier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'competition_id',
        'rank_from',
        'rank_to',
        'prize_type',
        'prize_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rank_from' => 'integer',
        'rank_to' => 'integer',
        'prize_value' => 'decimal:2',
    ];

    /**
     * Valid prize types.
     */
    public const PRIZE_TYPES = [
        'cash' => 'Cash',
        'item' => 'Item',
        'points' => 'Points',
    ];

    /**
     * Get the competition that owns the prize tier.
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get a formatted description of the rank range.
     */
    public function getRankRangeDescription(): string
    {
        if ($this->rank_from === $this->rank_to) {
            return "Rank {$this->rank_from}";
        }

        return "Ranks {$this->rank_from} - {$this->rank_to}";
    }

    /**
     * Get a formatted description of the prize.
     */
    public function getPrizeDescription(): string
    {
        $type = self::PRIZE_TYPES[$this->prize_type] ?? ucfirst($this->prize_type);

        return match ($this->prize_type) {
            'cash' => "IQD {$this->prize_value}",
            'points' => "{$this->prize_value} points",
            default => "{$type}: {$this->prize_value}",
        };
    }
}