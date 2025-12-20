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
     * Get the image URL from DigitalOcean Spaces CDN
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

        // Return CDN URL for DigitalOcean Spaces
        $cdnEndpoint = config('filesystems.disks.spaces.url');
        return rtrim($cdnEndpoint, '/') . '/' . ltrim($this->image, '/');
    }

    /**
     * Scope to get active advertisements
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
