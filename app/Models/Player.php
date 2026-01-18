<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Player extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'players';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'whatsapp_number',
        'nickname',
        'total_score',
        'level',
        'experience_points',
        'is_verified',
        'fcm_token',
        'language',
        'joined_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'joined_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the OTPs for the player.
     */
    public function otps(): HasMany
    {
        return $this->hasMany(PlayerOtp::class);
    }

    /**
     * Scope to limit results and prevent resource exhaustion
     */
    public function scopeLimited($query, int $limit = 100)
    {
        return $query->limit($limit);
    }

    /**
     * Scope for safe pagination
     */
    public function scopeSafePaginated($query, int $perPage = 15)
    {
        // Enforce maximum page size
        $perPage = min($perPage, 100);
        return $query->paginate($perPage);
    }

    /**
     * Scope to get only verified players
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for efficient sorting by score
     */
    public function scopeOrderByScore($query, string $direction = 'desc')
    {
        return $query->orderBy('total_score', $direction);
    }

    /**
     * Scope to exclude sensitive information
     */
    public function scopePublic($query)
    {
        return $query->select([
            'id', 'nickname', 'total_score', 'level', 
            'experience_points', 'created_at'
        ]);
    }

    /**
     * Get the player's transactions with resource protection.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the payment methods for the player.
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PlayerPaymentMethod::class);
    }

    /**
     * Get the competition registrations for the player.
     */
    public function competitionRegistrations(): HasMany
    {
        return $this->hasMany(CompetitionRegistration::class);
    }

    /**
     * Get the successfully registered competitions for the player.
     */
    public function registeredCompetitions()
    {
        return $this->competitionRegistrations()->registered()->with('competition');
    }

    /**
     * Get the player's points balance.
     */
    public function pointsBalance(): HasOne
    {
        return $this->hasOne(PlayerPointsBalance::class);
    }

    /**
     * Get the player's points transactions.
     */
    public function pointsTransactions(): HasMany
    {
        return $this->hasMany(PointsTransaction::class);
    }

    /**
     * Get the player's notifications.
     */
    public function notifications()
    {
        return $this->hasMany(PlayerNotification::class);
    }

    /**
     * Get the player's unread notifications with limits.
     */
    public function unreadNotifications()
    {
        // Limit to prevent resource exhaustion
        return $this->notifications()
                   ->unread()
                   ->with('notification')
                   ->limit(100);
    }

    /**
     * Get the player's read notifications with limits.
     */
    public function readNotifications()
    {
        // Paginated to prevent large data loads
        return $this->notifications()
                   ->read()
                   ->with('notification')
                   ->latest('read_at');
    }

    /**
     * Get the player's recent notifications (last 30 days).
     */
    public function recentNotifications()
    {
        return $this->notifications()
                   ->recent()
                   ->with('notification')
                   ->limit(50);
    }

    /**
     * Get the count of unread notifications - cached to prevent expensive counts.
     */
    public function unreadNotificationsCount(): int
    {
        return \Cache::remember(
            "player_{$this->id}_unread_count",
            300, // 5 minutes
            fn () => $this->notifications()->unread()->count()
        );
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsAsRead(): void
    {
        $this->notifications()->unread()->update(['read_at' => now()]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markNotificationAsRead(int $notificationId): bool
    {
        $playerNotification = $this->notifications()
            ->where('notification_id', $notificationId)
            ->first();

        if ($playerNotification) {
            $playerNotification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Generate and store a new OTP for this player
     * 
     * @param string $purpose The purpose of the OTP (login, registration, verification)
     * @param int $length The length of the OTP code (default: 6)
     * @param int $expiryMinutes Minutes until the OTP expires (default: 10)
     * @return PlayerOtp
     */
    public function generateOtp(string $purpose = 'login', int $length = 6, int $expiryMinutes = 10): PlayerOtp
    {
        // Generate OTP code
        $otpCode = '';
        for ($i = 0; $i < $length; $i++) {
            $otpCode .= mt_rand(0, 9);
        }

        // Invalidate any existing non-verified OTPs for this purpose
        $this->otps()
            ->where('purpose', $purpose)
            ->where('is_verified', false)
            ->delete();

        // Create new OTP
        return $this->otps()->create([
            'otp_code' => $otpCode,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    /**
     * Verify an OTP code
     * 
     * @param string $otpCode The OTP code to verify
     * @param string $purpose The purpose of the OTP
     * @return bool Whether the OTP was verified successfully
     */
    public function verifyOtp(string $otpCode, string $purpose = 'login'): bool
    {
        $otp = $this->otps()
            ->where('otp_code', $otpCode)
            ->where('purpose', $purpose)
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return false;
        }

        // Mark OTP as verified
        $otp->update(['is_verified' => true]);

        return true;
    }

    /**
     * Create a new transaction for this player.
     * 
     * @param float $amount The transaction amount
     * @param string $type The transaction type (deposit, withdrawal, etc.)
     * @param int|null $competitionId The competition ID (if applicable)
     * @param string|null $referenceId External reference ID
     * @param string|null $notes Additional notes
     * @param string|null $paymentMethod Payment method code
     * @param string|null $paymentProvider Payment provider
     * @param array|null $paymentDetails Payment details
     * @return Transaction
     */
    public function createTransaction(
        float $amount,
        string $type,
        ?int $competitionId = null,
        ?string $referenceId = null,
        ?string $notes = null,
        ?string $paymentMethod = null,
        ?string $paymentProvider = null,
        ?array $paymentDetails = null
    ): Transaction {
        $transaction = $this->transactions()->create([
            'competition_id' => $competitionId,
            'amount' => $amount,
            'transaction_type' => $type,
            'payment_method' => $paymentMethod,
            'payment_provider' => $paymentProvider,
            'payment_details' => $paymentDetails,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);

        // Log the creation
        $transaction->logAction('created');

        return $transaction;
    }

    /**
     * Get the default payment method for this player.
     * 
     * @param string $transactionType The transaction type (entry_fee, prize, etc.)
     * @return PlayerPaymentMethod|null
     */
    public function getDefaultPaymentMethod(): ?PlayerPaymentMethod
    {
        return $this->paymentMethods()
            ->where('is_default', true)
            ->whereHas('paymentMethod', function ($query) {
                $query->where('is_active', true);
            })
            ->first();
    }

    /**
     * Get available payment methods for this player.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailablePaymentMethods()
    {
        return $this->paymentMethods()
            ->whereHas('paymentMethod', function ($query) {
                $query->where('is_active', true);
            })
            ->get();
    }

    /**
     * Add a new payment method for this player.
     * 
     * @param int $paymentMethodId The payment method ID
     * @param string|null $token Payment token from provider
     * @param string|null $externalId External ID from payment provider
     * @param string|null $nickname User-defined nickname
     * @param array|null $details Payment details
     * @param bool $setAsDefault Whether to set this as the default payment method
     * @return PlayerPaymentMethod
     */
    public function addPaymentMethod(
        int $paymentMethodId,
        ?string $token = null,
        ?string $externalId = null,
        ?string $nickname = null,
        ?array $details = null,
        bool $setAsDefault = false
    ): PlayerPaymentMethod {
        $paymentMethod = $this->paymentMethods()->create([
            'payment_method_id' => $paymentMethodId,
            'token' => $token,
            'external_id' => $externalId,
            'nickname' => $nickname,
            'details' => $details,
            'is_default' => $setAsDefault,
            'last_used_at' => now(),
        ]);

        // If set as default, unset any other default methods
        if ($setAsDefault) {
            $this->paymentMethods()
                ->where('id', '!=', $paymentMethod->id)
                ->update(['is_default' => false]);
        }

        return $paymentMethod;
    }
}