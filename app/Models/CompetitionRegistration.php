<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitionRegistration extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'competition_registrations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'competition_id',
        'player_id',
        'transaction_id',
        'registration_status',
        'entry_fee_paid',
        'is_free_entry',
        'registered_at',
        'expires_at',
        'registration_source',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'entry_fee_paid' => 'decimal:2',
        'is_free_entry' => 'boolean',
        'registered_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Registration status constants
    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_PAYMENT_PROCESSING = 'payment_processing';
    const STATUS_REGISTERED = 'registered';
    const STATUS_PAYMENT_FAILED = 'payment_failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_EXPIRED = 'expired';

    // Registration source constants
    const SOURCE_MOBILE_APP = 'mobile_app';
    const SOURCE_WEB = 'web';
    const SOURCE_ADMIN = 'admin';

    /**
     * Get the competition that this registration belongs to.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the player that this registration belongs to.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the transaction associated with this registration.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Check if the registration is successfully registered.
     */
    public function isRegistered(): bool
    {
        return $this->registration_status === self::STATUS_REGISTERED;
    }

    /**
     * Check if the registration is pending payment.
     */
    public function isPendingPayment(): bool
    {
        return $this->registration_status === self::STATUS_PENDING_PAYMENT;
    }

    /**
     * Check if the registration has expired.
     */
    public function isExpired(): bool
    {
        return $this->registration_status === self::STATUS_EXPIRED ||
            ($this->expires_at && $this->expires_at < now());
    }

    /**
     * Check if payment has failed.
     */
    public function hasPaymentFailed(): bool
    {
        return $this->registration_status === self::STATUS_PAYMENT_FAILED;
    }

    /**
     * Check if the registration was cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->registration_status === self::STATUS_CANCELLED;
    }

    /**
     * Check if the registration was refunded.
     */
    public function isRefunded(): bool
    {
        return $this->registration_status === self::STATUS_REFUNDED;
    }

    /**
     * Mark the registration as successfully registered.
     */
    public function markAsRegistered(): void
    {
        $this->update([
            'registration_status' => self::STATUS_REGISTERED,
            'registered_at' => now(),
            'expires_at' => null,
        ]);
    }

    /**
     * Mark the registration as payment failed.
     */
    public function markAsPaymentFailed(string $reason = null): void
    {
        $this->update([
            'registration_status' => self::STATUS_PAYMENT_FAILED,
            'notes' => $reason,
        ]);
    }

    /**
     * Mark the registration as expired.
     */
    public function markAsExpired(): void
    {
        $this->update([
            'registration_status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Cancel the registration.
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'registration_status' => self::STATUS_CANCELLED,
            'notes' => $reason,
        ]);
    }

    /**
     * Get the status color for UI display.
     */
    public function getStatusColor(): string
    {
        return match ($this->registration_status) {
            self::STATUS_REGISTERED => 'success',
            self::STATUS_PENDING_PAYMENT, self::STATUS_PAYMENT_PROCESSING => 'warning',
            self::STATUS_PAYMENT_FAILED, self::STATUS_EXPIRED, self::STATUS_CANCELLED => 'danger',
            self::STATUS_REFUNDED => 'info',
            default => 'gray',
        };
    }

    /**
     * Get the status label for UI display.
     */
    public function getStatusLabel(): string
    {
        return match ($this->registration_status) {
            self::STATUS_PENDING_PAYMENT => 'Pending Payment',
            self::STATUS_PAYMENT_PROCESSING => 'Processing Payment',
            self::STATUS_REGISTERED => 'Registered',
            self::STATUS_PAYMENT_FAILED => 'Payment Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_EXPIRED => 'Expired',
            default => ucfirst(str_replace('_', ' ', $this->registration_status)),
        };
    }

    /**
     * Scope to get only registered participants.
     */
    public function scopeRegistered($query)
    {
        return $query->where('registration_status', self::STATUS_REGISTERED);
    }

    /**
     * Scope to get pending registrations.
     */
    public function scopePending($query)
    {
        return $query->whereIn('registration_status', [
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_PAYMENT_PROCESSING,
        ]);
    }

    /**
     * Scope to get expired registrations.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($query) {
            $query->where('registration_status', self::STATUS_EXPIRED)
                ->orWhere(function ($query) {
                    $query->whereNotNull('expires_at')
                        ->where('expires_at', '<', now());
                });
        });
    }
}