<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
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
}
