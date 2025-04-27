<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionLeaderboard extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'competition_id',
        'player_id',
        'score',
        'rank',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score' => 'integer',
        'rank' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the competition that owns the leaderboard entry.
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the player that owns the leaderboard entry.
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the prize tier that matches this leaderboard entry's rank.
     */
    public function getPrizeTier()
    {
        return $this->competition->prizeTiers()
            ->where('rank_from', '<=', $this->rank)
            ->where('rank_to', '>=', $this->rank)
            ->first();
    }

    /**
     * Get a human-readable description of the prize for this rank.
     */
    public function getPrizeDescription(): ?string
    {
        $prizeTier = $this->getPrizeTier();

        if (!$prizeTier) {
            return null;
        }

        return $prizeTier->getPrizeDescription();
    }
}