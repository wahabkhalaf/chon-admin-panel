<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionPlayerAnswer extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'competition_player_answers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'player_id',
        'competition_id',
        'question_id',
        'player_answer',
        'correct_answer',
        'is_correct',
        'answered_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    /**
     * Get the competition associated with the answer.
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the player associated with the answer.
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the question associated with the answer.
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}