<?php

namespace App\Console\Commands;

use App\Services\QueryOptimizationService;
use App\Services\ResourceExhaustionPreventionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeResourceUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resources:optimize
                            {--memory-check : Check memory usage}
                            {--optimize-queries : Optimize query execution}
                            {--test-chunk-size : Test optimal chunk size}
                            {--connection-pool : Optimize connection pool}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize resource usage and prevent exhaustion';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Resource Optimization');
        $this->newLine();

        // Memory check
        if ($this->option('memory-check')) {
            $this->checkMemory();
        }

        // Optimize queries
        if ($this->option('optimize-queries')) {
            $this->optimizeQueries();
        }

        // Test chunk size
        if ($this->option('test-chunk-size')) {
            $this->testChunkSize();
        }

        // Optimize connection pool
        if ($this->option('connection-pool')) {
            $this->optimizeConnectionPool();
        }

        // Default: show all metrics
        if (!$this->option('memory-check') && !$this->option('optimize-queries') && 
            !$this->option('test-chunk-size') && !$this->option('connection-pool')) {
            $this->showMetrics();
        }

        $this->newLine();
        $this->info('✅ Resource optimization complete!');

        return Command::SUCCESS;
    }

    /**
     * Check memory usage
     */
    protected function checkMemory(): void
    {
        $this->info('📊 Memory Usage Analysis:');

        $metrics = ResourceExhaustionPreventionService::getMetrics();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Current Memory', $metrics['memory_usage_mb'] . ' MB'],
                ['Peak Memory', $metrics['memory_peak_mb'] . ' MB'],
                ['Memory Limit', $metrics['memory_limit_mb'] . ' MB'],
                ['Memory Threshold', $metrics['memory_threshold_mb'] . ' MB'],
            ]
        );

        if ($metrics['memory_usage_mb'] > 128) {
            $this->warn('⚠️  High memory usage detected!');
        }

        $this->newLine();
    }

    /**
     * Optimize queries
     */
    protected function optimizeQueries(): void
    {
        $this->info('🔍 Query Optimization:');

        DB::enableQueryLog();

        // Run sample queries
        \App\Models\Player::limit(100)->get();
        \App\Models\Competition::limit(100)->get();
        \App\Models\Transaction::limit(100)->get();

        $queries = DB::getQueryLog();

        $this->info('Analyzed ' . count($queries) . ' queries');

        // Show statistics
        $totalTime = array_sum(array_column($queries, 'time'));
        $avgTime = count($queries) > 0 ? $totalTime / count($queries) : 0;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Queries', count($queries)],
                ['Total Time', round($totalTime, 2) . ' ms'],
                ['Average Time', round($avgTime, 2) . ' ms'],
            ]
        );

        // Find slow queries
        $slowQueries = array_filter($queries, fn ($q) => $q['time'] > 100);

        if (!empty($slowQueries)) {
            $this->warn(count($slowQueries) . ' slow queries found (>100ms)');
        }

        $this->newLine();
    }

    /**
     * Test optimal chunk size
     */
    protected function testChunkSize(): void
    {
        $this->info('📦 Chunk Size Optimization:');

        $chunkSize = ResourceExhaustionPreventionService::calculateOptimalChunkSize();

        $this->table(
            ['Setting', 'Value'],
            [
                ['Optimal Chunk Size', $chunkSize],
                ['Memory Available', round(memory_get_usage(false) / 1024 / 1024, 2) . ' MB'],
                ['Memory Peak', round(memory_get_peak_usage(false) / 1024 / 1024, 2) . ' MB'],
            ]
        );

        $this->info("Use chunk size of $chunkSize for optimal performance");
        $this->newLine();
    }

    /**
     * Optimize connection pool
     */
    protected function optimizeConnectionPool(): void
    {
        $this->info('🔗 Connection Pool Optimization:');

        $stats = QueryOptimizationService::getConnectionStats();

        if (!empty($stats)) {
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Connections', $stats['total_connections'] ?? 'N/A'],
                    ['Active Connections', $stats['active_connections'] ?? 'N/A'],
                    ['Idle Connections', $stats['idle_connections'] ?? 'N/A'],
                ]
            );

            // Recommendations
            if ($stats['active_connections'] > $stats['total_connections'] * 0.8) {
                $this->warn('⚠️  High connection usage. Consider increasing pool size.');
            }
        }

        $this->newLine();
    }

    /**
     * Show all metrics
     */
    protected function showMetrics(): void
    {
        $this->info('📈 Resource Metrics Summary:');

        $metrics = ResourceExhaustionPreventionService::getMetrics();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Memory Usage', $metrics['memory_usage_mb'] . ' MB'],
                ['Memory Peak', $metrics['memory_peak_mb'] . ' MB'],
                ['Memory Limit', $metrics['memory_limit_mb'] . ' MB'],
                ['Default Page Size', $metrics['default_page_size']],
                ['Max Page Size', $metrics['max_page_size']],
            ]
        );

        $this->info('💡 Recommendations:');
        $this->line('1. Use --memory-check to analyze memory usage');
        $this->line('2. Use --optimize-queries to find slow queries');
        $this->line('3. Use --test-chunk-size to optimize data processing');
        $this->line('4. Use --connection-pool to optimize database connections');

        $this->newLine();
    }
}
