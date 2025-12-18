<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for query optimization utilities
 */
class QueryOptimizationService
{
    /**
     * Enable query logging for debugging
     */
    public static function enableQueryLog(): void
    {
        DB::enableQueryLog();
    }

    /**
     * Get executed queries
     */
    public static function getQueryLog(): array
    {
        return DB::getQueryLog();
    }

    /**
     * Log slow queries
     */
    public static function logSlowQueries(float $thresholdMs = 100): void
    {
        $queries = DB::getQueryLog();
        
        foreach ($queries as $query) {
            if ($query['time'] > $thresholdMs) {
                Log::warning('Slow query detected', [
                    'query' => $query['query'],
                    'bindings' => $query['bindings'],
                    'time' => $query['time'] . 'ms'
                ]);
            }
        }
    }

    /**
     * Optimize database tables
     */
    public static function optimizeTables(array $tables = []): void
    {
        $connection = config('database.default');
        
        if ($connection === 'pgsql') {
            foreach ($tables as $table) {
                DB::statement("VACUUM ANALYZE {$table}");
            }
        } elseif ($connection === 'mysql') {
            foreach ($tables as $table) {
                DB::statement("OPTIMIZE TABLE {$table}");
            }
        }
    }

    /**
     * Get table statistics
     */
    public static function getTableStats(string $table): array
    {
        $connection = config('database.default');
        
        if ($connection === 'pgsql') {
            $stats = DB::select("
                SELECT 
                    schemaname,
                    tablename,
                    n_live_tup as row_count,
                    n_dead_tup as dead_rows,
                    last_vacuum,
                    last_autovacuum,
                    last_analyze,
                    last_autoanalyze
                FROM pg_stat_user_tables
                WHERE tablename = ?
            ", [$table]);
            
            return $stats ? (array) $stats[0] : [];
        }
        
        return [];
    }

    /**
     * Get index usage statistics
     */
    public static function getIndexStats(string $table): array
    {
        $connection = config('database.default');
        
        if ($connection === 'pgsql') {
            return DB::select("
                SELECT 
                    indexname,
                    idx_scan,
                    idx_tup_read,
                    idx_tup_fetch
                FROM pg_stat_user_indexes
                WHERE tablename = ?
                ORDER BY idx_scan DESC
            ", [$table]);
        }
        
        return [];
    }

    /**
     * Check for missing indexes
     */
    public static function suggestIndexes(): array
    {
        $connection = config('database.default');
        
        if ($connection === 'pgsql') {
            return DB::select("
                SELECT 
                    schemaname,
                    tablename,
                    seq_scan,
                    seq_tup_read,
                    idx_scan,
                    seq_tup_read / seq_scan as avg_seq_read
                FROM pg_stat_user_tables
                WHERE seq_scan > 0
                  AND seq_tup_read / seq_scan > 10000
                ORDER BY seq_tup_read DESC
                LIMIT 10
            ");
        }
        
        return [];
    }

    /**
     * Get database connection pool stats
     */
    public static function getConnectionStats(): array
    {
        $connection = config('database.default');
        
        if ($connection === 'pgsql') {
            $stats = DB::select("
                SELECT 
                    count(*) as total_connections,
                    count(*) FILTER (WHERE state = 'active') as active_connections,
                    count(*) FILTER (WHERE state = 'idle') as idle_connections
                FROM pg_stat_activity
                WHERE datname = current_database()
            ");
            
            return $stats ? (array) $stats[0] : [];
        }
        
        return [];
    }

    /**
     * Clear query cache
     */
    public static function clearQueryCache(): void
    {
        if (config('database.default') === 'mysql') {
            DB::statement('RESET QUERY CACHE');
        }
    }

    /**
     * Get query plan for analysis
     */
    public static function explainQuery(string $query, array $bindings = []): array
    {
        $connection = config('database.default');
        
        if ($connection === 'pgsql') {
            return DB::select("EXPLAIN ANALYZE {$query}", $bindings);
        } elseif ($connection === 'mysql') {
            return DB::select("EXPLAIN {$query}", $bindings);
        }
        
        return [];
    }
}
