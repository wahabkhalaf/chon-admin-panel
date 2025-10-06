<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_kurdish',
        'title_arabic',
        'title_kurmanji',
        'message',
        'message_kurdish',
        'message_arabic',
        'message_kurmanji',
        'type',
        'priority',
        'data',
        'scheduled_at',
        'sent_at',
        'status',
        'api_response',
    ];

    protected $casts = [
        'data' => 'array',
        'api_response' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Custom accessor for api_response to handle both JSON strings and arrays
     */
    public function getApiResponseAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : $value;
        }
        return $value;
    }

    /**
     * Custom mutator for api_response to ensure it's stored as JSON string
     */
    public function setApiResponseAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['api_response'] = json_encode($value);
        } else {
            $this->attributes['api_response'] = $value;
        }
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at');
    }

    public function scopeReadyToSend($query)
    {
        return $query->pending()
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function isReadyToSend(): bool
    {
        return $this->status === 'pending' &&
            ($this->scheduled_at === null || $this->scheduled_at <= now());
    }

    public function markAsSent(array $apiResponse = []): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'api_response' => $apiResponse,
        ]);
    }

    public function markAsFailed(array $apiResponse = []): void
    {
        $this->update([
            'status' => 'failed',
            'api_response' => $apiResponse,
        ]);
    }

    /**
     * Get the player notifications for this notification.
     */
    public function playerNotifications()
    {
        return $this->hasMany(PlayerNotification::class);
    }

    /**
     * Get the players who received this notification.
     */
    public function players()
    {
        return $this->belongsToMany(Player::class, 'player_notifications')
            ->withPivot(['received_at', 'read_at', 'delivery_data'])
            ->withTimestamps();
    }

    /**
     * Get the count of players who received this notification.
     */
    public function recipientsCount(): int
    {
        return $this->playerNotifications()->count();
    }

    /**
     * Get the count of players who read this notification.
     */
    public function readCount(): int
    {
        return $this->playerNotifications()->read()->count();
    }

    /**
     * Get the read rate percentage.
     */
    public function readRate(): float
    {
        $total = $this->recipientsCount();
        if ($total === 0) {
            return 0;
        }

        return round(($this->readCount() / $total) * 100, 2);
    }
}
