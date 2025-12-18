<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Prevent lazy loading in development to catch N+1 queries
        if (app()->environment('local', 'development')) {
            Model::preventLazyLoading();
        }

        // Prevent silently discarding attributes
        Model::preventSilentlyDiscardingAttributes();

        // Prevent accessing missing attributes
        Model::preventAccessingMissingAttributes();

        // Log slow queries
        if (config('database.log_slow_queries', false)) {
            DB::listen(function (QueryExecuted $query) {
                $threshold = config('database.slow_query_threshold', 1000); // 1 second default
                
                if ($query->time > $threshold) {
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }

        // Monitor duplicate queries (possible N+1 issues)
        if (config('database.monitor_duplicate_queries', false)) {
            $queries = [];
            
            DB::listen(function (QueryExecuted $query) use (&$queries) {
                $sql = $query->sql;
                
                if (!isset($queries[$sql])) {
                    $queries[$sql] = 0;
                }
                
                $queries[$sql]++;
                
                // Log if same query is executed more than 10 times
                if ($queries[$sql] === 10) {
                    Log::warning('Possible N+1 query issue', [
                        'sql' => $sql,
                        'count' => $queries[$sql],
                    ]);
                }
            });
        }
    }
}
