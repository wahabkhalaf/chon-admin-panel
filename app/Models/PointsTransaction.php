<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsTransaction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'points_transactions';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'player_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'player_id' => 'integer',
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Transaction types
     */
    const TYPE_PURCHASE = 'purchase';
    const TYPE_SPEND = 'spend';
    const TYPE_ADMIN_CREDIT = 'admin_credit';
    const TYPE_REFUND = 'refund';

    /**
     * Reference types
     */
    const REF_COMPETITION = 'competition';
    const REF_PACKAGE_PURCHASE = 'package_purchase';
    const REF_ADMIN_ACTION = 'admin_action';

    /**
     * Get the player that owns the transaction.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by reference type.
     */
    public function scopeOfReferenceType($query, string $referenceType)
    {
        return $query->where('reference_type', $referenceType);
    }

    /**
     * Scope to get purchases.
     */
    public function scopePurchases($query)
    {
        return $query->where('type', self::TYPE_PURCHASE);
    }

    /**
     * Scope to get spending.
     */
    public function scopeSpending($query)
    {
        return $query->where('type', self::TYPE_SPEND);
    }

    /**
     * Check if this is a credit transaction (adds points).
     */
    public function isCredit(): bool
    {
        return in_array($this->type, [self::TYPE_PURCHASE, self::TYPE_ADMIN_CREDIT, self::TYPE_REFUND]);
    }

    /**
     * Check if this is a debit transaction (removes points).
     */
    public function isDebit(): bool
    {
        return $this->type === self::TYPE_SPEND;
    }

    /**
     * Get human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PURCHASE => 'Purchase',
            self::TYPE_SPEND => 'Spend',
            self::TYPE_ADMIN_CREDIT => 'Admin Credit',
            self::TYPE_REFUND => 'Refund',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get human-readable reference type label.
     */
    public function getReferenceTypeLabelAttribute(): ?string
    {
        if (!$this->reference_type) {
            return null;
        }

        return match ($this->reference_type) {
            self::REF_COMPETITION => 'Competition',
            self::REF_PACKAGE_PURCHASE => 'Package Purchase',
            self::REF_ADMIN_ACTION => 'Admin Action',
            default => ucfirst(str_replace('_', ' ', $this->reference_type)),
        };
    }
}
