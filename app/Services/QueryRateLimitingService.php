<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Service for rate limiting expensive database queries
 * Prevents abuse and resource exhaustion from repeated heavy operations
 */
class QueryRateLimitingService
{
    /**
     * Rate limit configuration for expensive operations
     */
    protected static array $limits = [
        'leaderboard_fetch' => ['attempts' => 30, 'minutes' => 1],
        'player_reports' => ['attempts' => 10, 'minutes' => 1],
        'bulk_export' => ['attempts' => 5, 'minutes' => 60],
        'statistics_calculation' => ['attempts' => 20, 'minutes' => 1],
        'search_query' => ['attempts' => 100, 'minutes' => 1],
    ];

    /**
     * Check if operation is rate limited
     */
    public static function isLimited(string $operation, string $identifier = null): bool
    {
        $identifier = $identifier ?? request()->ip();
        $key = "rate_limit:$operation:$identifier";

        if (!RateLimiter::hasKey($key)) {
            return false;
        }

        $limit = static::$limits[$operation] ?? ['attempts' => 100, 'minutes' => 1];

        return RateLimiter::tooManyAttempts($key, $limit['attempts']);
    }

    /**
     * Increment rate limit counter
     */
    public static function increment(string $operation, string $identifier = null): int
    {
        $identifier = $identifier ?? request()->ip();
        $key = "rate_limit:$operation:$identifier";
        $limit = static::$limits[$operation] ?? ['attempts' => 100, 'minutes' => 1];

        return RateLimiter::hit($key, $limit['minutes'] * 60);
    }

    /**
     * Reset rate limit
     */
    public static function reset(string $operation, string $identifier = null): void
    {
        $identifier = $identifier ?? request()->ip();
        $key = "rate_limit:$operation:$identifier";

        RateLimiter::resetAttempts($key);
    }

    /**
     * Get remaining attempts
     */
    public static function remaining(string $operation, string $identifier = null): int
    {
        $identifier = $identifier ?? request()->ip();
        $key = "rate_limit:$operation:$identifier";
        $limit = static::$limits[$operation] ?? ['attempts' => 100, 'minutes' => 1];

        return max(0, $limit['attempts'] - RateLimiter::attempts($key));
    }

    /**
     * Get seconds until limit resets
     */
    public static function secondsUntilReset(string $operation, string $identifier = null): int
    {
        $identifier = $identifier ?? request()->ip();
        $key = "rate_limit:$operation:$identifier";
        $limit = static::$limits[$operation] ?? ['attempts' => 100, 'minutes' => 1];

        if (!RateLimiter::hasKey($key)) {
            return 0;
        }

        return RateLimiter::availableAt($key) - now()->timestamp;
    }

    /**
     * Throttle a callback function
     */
    public static function throttle(string $operation, callable $callback, string $identifier = null)
    {
        if (static::isLimited($operation, $identifier)) {
            Log::warning("Rate limit exceeded for operation: {$operation}", [
                'identifier' => $identifier ?? request()->ip(),
                'remaining' => static::remaining($operation, $identifier),
            ]);

            abort(429, "Too many {$operation} requests. Please try again later.");
        }

        static::increment($operation, $identifier);

        return $callback();
    }

    /**
     * Cache expensive query results
     */
    public static function cacheExpensiveQuery(string $key, callable $query, int $ttl = 3600)
    {
        return Cache::remember($key, $ttl, $query);
    }

    /**
     * Batch expensive operations
     */
    public static function batchExpensiveOperation(array $items, callable $operation, int $batchSize = 100)
    {
        $results = [];
        $batches = array_chunk($items, $batchSize);

        foreach ($batches as $batch) {
            try {
                $batchResults = $operation($batch);
                $results = array_merge($results, (array) $batchResults);

                // Small delay between batches to prevent resource exhaustion
                usleep(100000); // 0.1 second
            } catch (\Exception $e) {
                Log::error('Batch operation failed', [
                    'error' => $e->getMessage(),
                    'batch_size' => count($batch),
                ]);

                throw $e;
            }
        }

        return $results;
    }

    /**
     * Configure rate limits
     */
    public static function configure(array $config): void
    {
        static::$limits = array_merge(static::$limits, $config);
    }

    /**
     * Get all rate limits
     */
    public static function getLimits(): array
    {
        return static::$limits;
    }

    /**
     * Get rate limit status for operation
     */
    public static function getStatus(string $operation, string $identifier = null): array
    {
        $identifier = $identifier ?? request()->ip();

        return [
            'operation' => $operation,
            'identifier' => $identifier,
            'is_limited' => static::isLimited($operation, $identifier),
            'remaining' => static::remaining($operation, $identifier),
            'seconds_until_reset' => static::secondsUntilReset($operation, $identifier),
            'limit' => static::$limits[$operation] ?? null,
        ];
    }
}
