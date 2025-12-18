<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to prevent resource exhaustion
 * Monitors and limits resource usage per request
 */
class PreventResourceExhaustion
{
    /**
     * Maximum allowed memory usage per request (in MB)
     */
    protected int $maxMemory = 256;

    /**
     * Maximum execution time per request (in seconds)
     */
    protected int $maxExecutionTime = 30;

    /**
     * Maximum number of database connections
     */
    protected int $maxConnections = 10;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set initial limits
        ini_set('memory_limit', $this->maxMemory . 'M');
        set_time_limit($this->maxExecutionTime);

        $memoryBefore = memory_get_usage(true);
        $timeBefore = microtime(true);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $this->logResourceExhaustion($request, $e, $memoryBefore, $timeBefore);
            throw $e;
        }

        $memoryAfter = memory_get_usage(true);
        $timeAfter = microtime(true);

        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;
        $timeUsed = ($timeAfter - $timeBefore);

        // Log warnings if resources are heavily used
        if ($memoryUsed > ($this->maxMemory * 0.8)) {
            Log::warning('High memory usage detected', [
                'url' => $request->fullUrl(),
                'memory_used_mb' => round($memoryUsed, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'time_used_seconds' => round($timeUsed, 2),
            ]);
        }

        if ($timeUsed > ($this->maxExecutionTime * 0.8)) {
            Log::warning('High execution time detected', [
                'url' => $request->fullUrl(),
                'time_used_seconds' => round($timeUsed, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ]);
        }

        // Add resource usage headers
        if (config('app.debug')) {
            $response->headers->set('X-Memory-Used-MB', round($memoryUsed, 2));
            $response->headers->set('X-Execution-Time-MS', round($timeUsed * 1000, 2));
        }

        return $response;
    }

    /**
     * Log resource exhaustion
     */
    protected function logResourceExhaustion(Request $request, \Throwable $exception, int $memoryBefore, float $timeBefore): void
    {
        $memoryAfter = memory_get_usage(true);
        $timeAfter = microtime(true);

        Log::error('Resource exhaustion error', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'memory_used_mb' => round(($memoryAfter - $memoryBefore) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'time_used_seconds' => round($timeAfter - $timeBefore, 2),
            'error' => $exception->getMessage(),
        ]);
    }
}
