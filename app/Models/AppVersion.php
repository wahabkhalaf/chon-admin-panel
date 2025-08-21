<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'version',
        'build_number',
        'app_store_url',
        'release_notes',
        'is_force_update',
        'is_active',
        'released_at',
    ];

    protected $casts = [
        'is_force_update' => 'boolean',
        'is_active' => 'boolean',
        'released_at' => 'datetime',
        'build_number' => 'integer',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('build_number', 'desc');
    }

    // Get latest version for a specific platform
    public static function getLatestVersion($platform)
    {
        return static::active()
            ->platform($platform)
            ->latest()
            ->first();
    }

    // Check if update is required
    public function isUpdateRequired($currentBuildNumber)
    {
        return $this->build_number > $currentBuildNumber;
    }

    // Check if this is a force update
    public function isForceUpdate($currentBuildNumber)
    {
        return $this->is_force_update && $this->build_number > $currentBuildNumber;
    }
}
