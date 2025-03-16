<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerPaymentMethod extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'player_id',
        'payment_method_id',
        'token',
        'external_id',
        'nickname',
        'details',
        'is_default',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'details' => 'array',
        'is_default' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'token',
        'external_id',
    ];

    /**
     * Get the player that owns the payment method.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the payment method.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Set this payment method as the default for the player.
     */
    public function setAsDefault(): bool
    {
        // First, unset any existing default
        self::where('player_id', $this->player_id)
            ->where('id', '!=', $this->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        // Then set this one as default
        $this->is_default = true;
        return $this->save();
    }

    /**
     * Update the last used timestamp.
     */
    public function markAsUsed(): bool
    {
        $this->last_used_at = now();
        return $this->save();
    }

    /**
     * Get a display name for this payment method.
     */
    public function getDisplayName(): string
    {
        if ($this->nickname) {
            return $this->nickname;
        }

        $methodName = $this->paymentMethod->name;

        if (isset($this->details['last4'])) {
            return "{$methodName} ending in {$this->details['last4']}";
        }

        if (isset($this->details['email'])) {
            return "{$methodName} ({$this->details['email']})";
        }

        if (isset($this->details['account_number'])) {
            $last4 = substr($this->details['account_number'], -4);
            return "{$methodName} ending in {$last4}";
        }

        return $methodName;
    }
}