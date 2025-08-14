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
        // Check if we're in production (chonapp.net)
        if (request()->getHost() === 'chonapp.net' || 
            request()->getHost() === 'www.chonapp.net') {
            return 'http://chonapp.net';
        }
        
        // Check if we're in local development
        if (request()->getHost() === 'localhost' || 
            request()->getHost() === '127.0.0.1') {
            return 'http://localhost';
        }
        
        // Check the current request URL
        $currentUrl = request()->url();
        if (str_contains($currentUrl, 'chonapp.net')) {
            return 'http://chonapp.net';
        }
        
        // Check if we're accessing via chonapp.net domain
        if (request()->server('HTTP_HOST') === 'chonapp.net' ||
            request()->server('HTTP_HOST') === 'www.chonapp.net') {
            return 'http://chonapp.net';
        }
        
        // Force production URL if we're not on localhost
        // This ensures API calls from external sources get the correct URL
        if (request()->getHost() !== 'localhost' && 
            request()->getHost() !== '127.0.0.1') {
            return 'http://chonapp.net';
        }
        
        // Additional check: if this is an API call and we're not on localhost, use production
        if (request()->is('api/*') && 
            !in_array(request()->getHost(), ['localhost', '127.0.0.1'])) {
            return 'http://chonapp.net';
        }
        
        // Fallback to config
        return config('app.url', 'http://localhost');
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

        // Always use production URL for this method
        return 'http://chonapp.net/storage/' . ltrim($this->image, '/');
    }

    /**
     * Scope to get active advertisements
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
