<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'player_id',
        'competition_id',
        'amount',
        'transaction_type',
        'status',
        'payment_method',
        'payment_provider',
        'payment_details',
        'reference_id',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'payment_details',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Transaction types
     */
    const TYPE_ENTRY_FEE = 'entry_fee';
    const TYPE_PRIZE = 'prize';
    const TYPE_BONUS = 'bonus';
    const TYPE_REFUND = 'refund';

    /**
     * Transaction statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Get the player that owns the transaction.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the competition associated with the transaction.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the payment method used for this transaction.
     */
    public function paymentMethodModel(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'code');
    }

    /**
     * Get the logs for this transaction.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(TransactionLog::class);
    }

    /**
     * Scope a query to only include entry fees.
     */
    public function scopeEntryFees($query)
    {
        return $query->where('transaction_type', self::TYPE_ENTRY_FEE);
    }

    /**
     * Scope a query to only include prizes.
     */
    public function scopePrizes($query)
    {
        return $query->where('transaction_type', self::TYPE_PRIZE);
    }

    /**
     * Scope a query to only include completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope a query to only include pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Check if the transaction is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Log an action for this transaction.
     */
    public function logAction(string $action, ?string $reason = null, ?array $metadata = null): TransactionLog
    {
        return $this->logs()->create([
            'action' => $action,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Mark the transaction as completed.
     */
    public function markAsCompleted(?string $reason = null): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $result = $this->save();

        if ($result) {
            $this->logAction('completed', $reason);

            // Update last_used_at for the payment method if applicable
            if ($this->payment_method) {
                $playerPaymentMethod = PlayerPaymentMethod::where('player_id', $this->player_id)
                    ->whereHas('paymentMethod', function ($query) {
                        $query->where('code', $this->payment_method);
                    })
                    ->first();

                if ($playerPaymentMethod) {
                    $playerPaymentMethod->markAsUsed();
                }
            }
        }

        return $result;
    }

    /**
     * Mark the transaction as failed.
     */
    public function markAsFailed(?string $reason = null): bool
    {
        $this->status = self::STATUS_FAILED;
        $result = $this->save();

        if ($result) {
            $this->logAction('failed', $reason);
        }

        return $result;
    }

    /**
     * Get the amount with sign (positive or negative) based on transaction type.
     */
    public function getSignedAmount(): float
    {
        $positiveTypes = [
            self::TYPE_PRIZE,
            self::TYPE_BONUS,
            self::TYPE_REFUND,
        ];

        return in_array($this->transaction_type, $positiveTypes)
            ? (float) $this->amount
            : -1 * (float) $this->amount;
    }

    /**
     * Get the fee amount for this transaction.
     */
    public function getFeeAmount(): float
    {
        if (!$this->payment_method) {
            return 0;
        }

        $paymentMethod = PaymentMethod::where('code', $this->payment_method)->first();

        if (!$paymentMethod) {
            return 0;
        }

        return $paymentMethod->calculateFee($this->amount);
    }

    /**
     * Get the total amount including fees.
     */
    public function getTotalWithFee(): float
    {
        return $this->amount + $this->getFeeAmount();
    }

    /**
     * Get a formatted display string for the payment method.
     */
    public function getPaymentMethodDisplay(): string
    {
        if (!$this->payment_method) {
            return 'N/A';
        }

        $paymentMethod = PaymentMethod::where('code', $this->payment_method)->first();

        if (!$paymentMethod) {
            return $this->payment_method;
        }

        $display = $paymentMethod->name;

        if (isset($this->payment_details['last4'])) {
            $display .= " ending in {$this->payment_details['last4']}";
        }

        return $display;
    }
}