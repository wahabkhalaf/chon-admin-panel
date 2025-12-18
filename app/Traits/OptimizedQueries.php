<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Trait for optimized database queries
 * Provides common query optimization patterns for models
 */
trait OptimizedQueries
{
    /**
     * Cached query results for dropdown options
     * Cache duration in seconds (default: 1 hour)
     */
    protected static int $dropdownCacheDuration = 3600;

    /**
     * Get cached options for dropdowns (id => name)
     * Useful for Filament select fields
     */
    public static function getCachedOptions(string $labelColumn = 'name', string $valueColumn = 'id', array $conditions = []): array
    {
        $cacheKey = static::class . '_options_' . $labelColumn . '_' . $valueColumn . '_' . md5(json_encode($conditions));
        
        return Cache::remember($cacheKey, static::$dropdownCacheDuration, function () use ($labelColumn, $valueColumn, $conditions) {
            $query = static::query();
            
            // Apply conditions if any
            foreach ($conditions as $field => $value) {
                $query->where($field, $value);
            }
            
            return $query->pluck($labelColumn, $valueColumn)->toArray();
        });
    }

    /**
     * Get cached count for performance
     */
    public static function getCachedCount(array $conditions = [], int $duration = 300): int
    {
        $cacheKey = static::class . '_count_' . md5(json_encode($conditions));
        
        return Cache::remember($cacheKey, $duration, function () use ($conditions) {
            $query = static::query();
            
            foreach ($conditions as $field => $value) {
                $query->where($field, $value);
            }
            
            return $query->count();
        });
    }

    /**
     * Clear cache for this model
     */
    public static function clearQueryCache(): void
    {
        Cache::tags([static::class])->flush();
    }

    /**
     * Scope a query to only include active records
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for efficient pagination with cursor
     */
    public function scopeOptimizedPagination(Builder $query, int $perPage = 15)
    {
        return $query->cursorPaginate($perPage);
    }

    /**
     * Scope to select only necessary columns
     */
    public function scopeSelectMinimal(Builder $query, array $columns): Builder
    {
        return $query->select($columns);
    }

    /**
     * Chunk query for memory-efficient processing
     */
    public static function processInChunks(callable $callback, int $chunkSize = 1000): void
    {
        static::query()->chunk($chunkSize, $callback);
    }

    /**
     * Lazy load for very large datasets
     */
    public static function lazyLoadAll(int $chunkSize = 1000)
    {
        return static::query()->lazy($chunkSize);
    }
}
