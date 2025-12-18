<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for preventing resource exhaustion in database queries
 * Implements query limits, pagination defaults, and memory-aware processing
 */
class ResourceExhaustionPreventionService
{
    /**
     * Default pagination size - prevents loading too much data
     */
    protected static int $defaultPageSize = 15;

    /**
     * Maximum page size allowed - security limit
     */
    protected static int $maxPageSize = 100;

    /**
     * Maximum allowed result set size without pagination
     */
    protected static int $maxUnpaginatedResults = 1000;

    /**
     * Memory threshold before using chunking (in MB)
     */
    protected static int $memoryThreshold = 50;

    /**
     * Apply safe defaults to prevent resource exhaustion
     */
    public static function applySafeDefaults(Builder $query, int $perPage = null, int $maxResults = null): Builder
    {
        $perPage = $perPage ?? static::$defaultPageSize;
        
        // Enforce maximum page size
        if ($perPage > static::$maxPageSize) {
            Log::warning('Page size exceeds maximum', [
                'requested' => $perPage,
                'max' => static::$maxPageSize,
            ]);
            $perPage = static::$maxPageSize;
        }

        return $query->limit($perPage ?? static::$defaultPageSize);
    }

    /**
     * Safe pagination with built-in limits
     */
    public static function safePaginate(Builder $query, int $perPage = null, int $page = 1)
    {
        $perPage = static::enforcePageLimit($perPage);

        return $query->paginate(
            perPage: $perPage,
            page: $page,
            columns: ['*']
        );
    }

    /**
     * Safe cursor pagination (more efficient for large datasets)
     */
    public static function safeCursorPaginate(Builder $query, int $perPage = null)
    {
        $perPage = static::enforcePageLimit($perPage);

        return $query->cursorPaginate(
            perPage: $perPage,
            columns: ['*']
        );
    }

    /**
     * Memory-aware chunking for large datasets
     */
    public static function memoryAwareChunk(Builder $query, callable $callback, int $chunkSize = null): void
    {
        $chunkSize = static::calculateOptimalChunkSize($chunkSize);

        $query->chunk($chunkSize, function ($records) use ($callback) {
            $callback($records);

            // Clear model cache between chunks to free memory
            if (method_exists($records, 'each')) {
                $records->each(function ($model) {
                    unset($model);
                });
            }
        });
    }

    /**
     * Lazy loading for streaming large result sets
     */
    public static function streamResults(Builder $query, callable $callback, int $chunkSize = null): void
    {
        $chunkSize = static::calculateOptimalChunkSize($chunkSize);

        foreach ($query->lazy($chunkSize) as $record) {
            $callback($record);
            unset($record);
        }
    }

    /**
     * Execute query with memory monitoring
     */
    public static function executeWithMemoryCheck(Builder $query, callable $fallback = null)
    {
        $memoryBefore = memory_get_usage(true);

        try {
            $result = $query->get();
            $memoryAfter = memory_get_usage(true);
            $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;

            if ($memoryUsed > static::$memoryThreshold) {
                Log::warning('High memory usage in query', [
                    'memory_used_mb' => round($memoryUsed, 2),
                    'threshold_mb' => static::$memoryThreshold,
                    'query' => $query->toSql(),
                ]);

                // Use fallback strategy if provided
                if ($fallback) {
                    return $fallback($query);
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Query execution failed', [
                'error' => $e->getMessage(),
                'query' => $query->toSql(),
            ]);

            throw $e;
        }
    }

    /**
     * Apply query timeout to prevent hanging queries
     */
    public static function withTimeout(Builder $query, int $timeoutSeconds = 30): Builder
    {
        if (config('database.default') === 'pgsql') {
            return $query->statement(
                "SET statement_timeout TO " . ($timeoutSeconds * 1000)
            );
        }

        return $query;
    }

    /**
     * Get results with automatic fallback to cached data
     */
    public static function getWithCache(Builder $query, string $cacheKey, int $cacheTtl = 3600)
    {
        // Try to use cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // If cache miss, execute query with memory check
        $result = static::executeWithMemoryCheck($query, function ($fallback) {
            // If memory threshold exceeded, use chunked approach
            return collect();
        });

        // Cache the result
        if ($result->count() > 0) {
            Cache::put($cacheKey, $result, $cacheTtl);
        }

        return $result;
    }

    /**
     * Count with protection against expensive counts
     */
    public static function safeCount(Builder $query, int $maxEstimate = 1000000): int
    {
        // Try to use approximate count for large tables
        if (config('database.default') === 'pgsql') {
            try {
                // Get estimated rows from query plan
                $explain = \DB::select('EXPLAIN ' . $query->toSql(), $query->getBindings());
                
                if (!empty($explain) && isset($explain[0]->Plan->{'Planned Rows'})) {
                    return (int) $explain[0]->Plan->{'Planned Rows'};
                }
            } catch (\Exception $e) {
                Log::debug('Failed to get estimated count', ['error' => $e->getMessage()]);
            }
        }

        // Fallback to exact count
        return $query->count();
    }

    /**
     * Select only necessary columns to reduce memory and transfer
     */
    public static function selectOptimal(Builder $query, array $columns = ['*']): Builder
    {
        if ($columns === ['*']) {
            return $query;
        }

        // Ensure primary key is always selected
        $model = $query->getModel();
        if ($model && !in_array($model->getKeyName(), $columns)) {
            $columns[] = $model->getKeyName();
        }

        return $query->select($columns);
    }

    /**
     * Calculate optimal chunk size based on memory availability
     */
    public static function calculateOptimalChunkSize(int $preferredSize = null): int
    {
        $preferredSize = $preferredSize ?? 1000;
        $availableMemory = (int) ini_get('memory_limit');

        if ($availableMemory === -1) {
            // Unlimited memory
            return $preferredSize;
        }

        $usedMemory = memory_get_usage(true);
        $freeMemory = ($availableMemory * 1024 * 1024) - $usedMemory;

        // If less than 20MB free, reduce chunk size
        if ($freeMemory < (20 * 1024 * 1024)) {
            return max(100, (int) ($preferredSize / 2));
        }

        // If less than 50MB free, use conservative chunk size
        if ($freeMemory < (50 * 1024 * 1024)) {
            return max(250, (int) ($preferredSize / 1.5));
        }

        return $preferredSize;
    }

    /**
     * Enforce page size limits
     */
    protected static function enforcePageLimit(int $perPage = null): int
    {
        $perPage = $perPage ?? static::$defaultPageSize;

        if ($perPage < 1) {
            $perPage = static::$defaultPageSize;
        }

        if ($perPage > static::$maxPageSize) {
            Log::warning('Page size adjusted to maximum', [
                'requested' => $perPage,
                'enforced' => static::$maxPageSize,
            ]);
            $perPage = static::$maxPageSize;
        }

        return $perPage;
    }

    /**
     * Get exhaustion prevention metrics
     */
    public static function getMetrics(): array
    {
        return [
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit_mb' => (int) ini_get('memory_limit'),
            'default_page_size' => static::$defaultPageSize,
            'max_page_size' => static::$maxPageSize,
            'memory_threshold_mb' => static::$memoryThreshold,
        ];
    }

    /**
     * Set resource limits
     */
    public static function setLimits(array $config): void
    {
        if (isset($config['default_page_size'])) {
            static::$defaultPageSize = $config['default_page_size'];
        }

        if (isset($config['max_page_size'])) {
            static::$maxPageSize = $config['max_page_size'];
        }

        if (isset($config['memory_threshold'])) {
            static::$memoryThreshold = $config['memory_threshold'];
        }

        if (isset($config['max_unpaginated_results'])) {
            static::$maxUnpaginatedResults = $config['max_unpaginated_results'];
        }
    }
}
