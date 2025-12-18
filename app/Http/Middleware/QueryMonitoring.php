<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QueryMonitoring
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only enable in development or when explicitly enabled
        if (!config('app.debug') && !config('database.query_monitoring', false)) {
            return $next($request);
        }

        // Enable query logging
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        
        // Process the request
        $response = $next($request);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Get executed queries
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $totalQueryTime = array_sum(array_column($queries, 'time'));
        
        // Log slow requests
        if ($executionTime > 1000 || $queryCount > 50) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => round($executionTime, 2) . 'ms',
                'query_count' => $queryCount,
                'total_query_time' => round($totalQueryTime, 2) . 'ms',
                'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB',
            ]);
            
            // Log individual slow queries
            foreach ($queries as $query) {
                if ($query['time'] > 100) {
                    Log::warning('Slow query detected', [
                        'query' => $query['query'],
                        'bindings' => $query['bindings'],
                        'time' => $query['time'] . 'ms'
                    ]);
                }
            }
        }
        
        // Add query metrics to response headers (only in debug mode)
        if (config('app.debug')) {
            $response->headers->set('X-Query-Count', $queryCount);
            $response->headers->set('X-Query-Time', round($totalQueryTime, 2));
            $response->headers->set('X-Execution-Time', round($executionTime, 2));
            $response->headers->set('X-Memory-Peak', round(memory_get_peak_usage(true) / 1024 / 1024, 2));
        }
        
        return $response;
    }
}
