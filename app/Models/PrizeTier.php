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
        'item_details',
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
        'item_details' => 'json',
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
     * Common item types for prizes
     */
    public const ITEM_TYPES = [
        'smartphone' => 'Smartphone',
        'laptop' => 'Laptop',
        'tablet' => 'Tablet',
        'car' => 'Car',
        'watch' => 'Watch',
        'gift_card' => 'Gift Card',
        'gaming_console' => 'Gaming Console',
        'other' => 'Other',
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

        if ($this->prize_type === 'item') {
            $itemDetails = $this->item_details;
            $itemName = $itemDetails['name'] ?? '';
            $itemType = $itemDetails['type'] ?? 'other';
            $quantity = $itemDetails['quantity'] ?? 1;

            $typeName = self::ITEM_TYPES[$itemType] ?? ucfirst($itemType);

            if (!empty($itemName)) {
                if ($quantity > 1) {
                    return "{$quantity}x {$itemName}";
                }
                return $itemName;
            }

            if ($quantity > 1) {
                return "{$quantity}x {$typeName}";
            }
            return $typeName;
        }

        return match ($this->prize_type) {
            'cash' => "IQD {$this->prize_value}",
            'points' => "{$this->prize_value} points",
            default => "{$type}: {$this->prize_value}",
        };
    }
}