<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'provider',
        'icon',
        'config',
        'is_active',
        'supports_deposit',
        'supports_withdrawal',
        'min_amount',
        'max_amount',
        'fee_fixed',
        'fee_percentage',
        'processing_time_hours',
        'instructions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'supports_deposit' => 'boolean',
        'supports_withdrawal' => 'boolean',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'fee_fixed' => 'decimal:2',
        'fee_percentage' => 'decimal:2',
    ];

    /**
     * Get the player payment methods for this payment method.
     */
    public function playerPaymentMethods(): HasMany
    {
        return $this->hasMany(PlayerPaymentMethod::class);
    }

    /**
     * Get the transactions for this payment method.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payment_method', 'code');
    }

    /**
     * Calculate the fee for a given amount.
     */
    public function calculateFee(float $amount): float
    {
        return $this->fee_fixed + ($amount * $this->fee_percentage / 100);
    }

    /**
     * Calculate the total amount including fees.
     */
    public function calculateTotalWithFee(float $amount): float
    {
        return $amount + $this->calculateFee($amount);
    }

    /**
     * Check if the payment method is available for a specific transaction type and amount.
     */
    public function isAvailableFor(string $transactionType, float $amount): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($transactionType === 'deposit' && !$this->supports_deposit) {
            return false;
        }

        if ($transactionType === 'withdrawal' && !$this->supports_withdrawal) {
            return false;
        }

        if ($amount < $this->min_amount) {
            return false;
        }

        if ($this->max_amount !== null && $amount > $this->max_amount) {
            return false;
        }

        return true;
    }
}