<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertising extends Model
{
    use HasFactory;

    protected $table = 'advertisements';

    protected $fillable = [
        'company_name',
        'phone_number',
        'image',
        'is_active',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the image URL
     */
    public function getImageUrlAttribute(): string
    {
        if (!$this->image) {
            return '';
        }

        // If image is already a full URL (external or absolute), return as is
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // Determine the correct base URL based on environment
        $baseUrl = $this->getBaseUrl();
        return rtrim($baseUrl, '/') . '/storage/' . ltrim($this->image, '/');
    }

    /**
     * Get the correct base URL for the current environment
     */
    private function getBaseUrl(): string
    {
        // Simple and fast environment detection
        $host = request()->getHost();
        
        // Check if we're in production (chonapp.net)
        if (str_contains($host, 'chonapp.net')) {
            return 'http://chonapp.net';
        }
        
        // Check if we're in local development
        if (in_array($host, ['localhost', '127.0.0.1'])) {
            return 'http://localhost';
        }
        
        // For API calls or external access, default to production
        if (request()->is('api/*')) {
            return 'http://chonapp.net';
        }
        
        // Fallback to production for any other case
        return 'http://chonapp.net';
    }

    /**
     * Force production URL (useful for API calls)
     */
    public function getProductionImageUrlAttribute(): string
    {
        if (!$this->image) {
            return '';
        }

        // If image is already a full URL (external or absolute), return as is
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // Use the CORS-enabled route instead of direct storage access
        return 'http://chonapp.net/storage/advertisements/' . ltrim($this->image, '/');
    }

    /**
     * Scope to get active advertisements
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
