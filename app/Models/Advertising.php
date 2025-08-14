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
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return '';
    }

    /**
     * Scope to get active advertisements
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
