<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerPointsBalance extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'player_points_balance';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'player_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'player_id',
        'current_balance',
        'total_earned',
        'total_spent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'player_id' => 'integer',
        'current_balance' => 'integer',
        'total_earned' => 'integer',
        'total_spent' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the player that owns the points balance.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the player's points transactions.
     */
    public function pointsTransactions(): HasMany
    {
        return $this->hasMany(PointsTransaction::class, 'player_id', 'player_id');
    }

    /**
     * Add points to the player's balance.
     */
    public function addPoints(int $amount): void
    {
        $this->current_balance += $amount;
        $this->total_earned += $amount;
        $this->save();
    }

    /**
     * Deduct points from the player's balance.
     * 
     * @throws \Exception if insufficient balance
     */
    public function deductPoints(int $amount): void
    {
        if ($this->current_balance < $amount) {
            throw new \Exception('Insufficient points balance');
        }

        $this->current_balance -= $amount;
        $this->total_spent += $amount;
        $this->save();
    }

    /**
     * Check if player has enough points.
     */
    public function hasEnoughPoints(int $amount): bool
    {
        return $this->current_balance >= $amount;
    }
}
