<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}