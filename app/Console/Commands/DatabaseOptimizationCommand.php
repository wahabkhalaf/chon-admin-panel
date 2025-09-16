<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseOptimizationCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:optimize 
                           {action? : The action to perform (status|maintenance|cleanup|health|stats)}
                           {--force : Force the operation without confirmation}
                           {--retention-days=30 : Days to retain old data for cleanup}';

    /**
     * The console command description.
     */
    protected $description = 'Manage database optimizations for high-load competition platform';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action') ?? 'status';

        $this->info("🚀 Database Optimization Tool for Competition Platform");
        $this->info("Server: 32GB RAM, 16 CPU cores, 100K+ concurrent users");
        $this->newLine();

        try {
            switch ($action) {
                case 'status':
                    return $this->showStatus();
                case 'maintenance':
                    return $this->performMaintenance();
                case 'cleanup':
                    return $this->performCleanup();
                case 'health':
                    return $this->checkHealth();
                case 'stats':
                    return $this->showStats();
                default:
                    $this->error("Unknown action: {$action}");
                    $this->info("Available actions: status, maintenance, cleanup, health, stats");
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error executing database optimization: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show database optimization status
     */
    private function showStatus(): int
    {
        $this->info("📊 Database Optimization Status");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        // Check if performance indexes exist
        $this->info("🔍 Checking Critical Performance Indexes:");

        $criticalIndexes = [
            'idx_competition_player_answers_comp_player' => 'Competition Player Answers (comp+player)',
            'idx_competition_leaderboards_comp_score' => 'Leaderboard Rankings',
            'idx_players_total_score' => 'Player Score Rankings',
            'idx_transactions_competition_status_player' => 'Transaction Processing',
            'idx_competitions_times_status' => 'Competition Time Queries'
        ];

        foreach ($criticalIndexes as $index => $description) {
            $exists = $this->indexExists($index);
            $status = $exists ? '✅ EXISTS' : '❌ MISSING';
            $this->line("  {$status} {$description}");
        }

        // Check materialized view
        $this->newLine();
        $this->info("📈 Checking Materialized Views:");
        $viewExists = $this->materializedViewExists('competition_player_stats');
        $status = $viewExists ? '✅ EXISTS' : '❌ MISSING';
        $this->line("  {$status} Competition Player Statistics View");

        // Show basic performance metrics
        $this->newLine();
        $this->info("⚡ Basic Performance Metrics:");

        try {
            // Database size
            $dbSize = DB::selectOne("SELECT pg_size_pretty(pg_database_size(current_database())) as size")->size;
            $this->line("  📦 Database Size: {$dbSize}");

            // Connection count
            $connections = DB::selectOne("SELECT count(*) as count FROM pg_stat_activity")->count;
            $this->line("  🔗 Active Connections: {$connections}");

            // Cache hit ratio
            $cacheHit = DB::selectOne("
                SELECT ROUND(100.0 * sum(blks_hit) / (sum(blks_hit) + sum(blks_read)), 2) as ratio 
                FROM pg_stat_database WHERE datname = current_database()
            ")->ratio;
            $this->line("  💾 Cache Hit Ratio: {$cacheHit}%");

        } catch (\Exception $e) {
            $this->warn("Could not retrieve performance metrics: " . $e->getMessage());
        }

        return 0;
    }

    /**
     * Perform database maintenance
     */
    private function performMaintenance(): int
    {
        if (!$this->option('force') && !$this->confirm('This will run VACUUM ANALYZE on all critical tables. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info("🔧 Performing Database Maintenance...");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $startTime = microtime(true);

        try {
            // Critical tables for maintenance (updated to skip non-existent tables)
            $criticalTables = [
                'competitions',
                'competition_leaderboards',
                'competition_player_answers',
                'competition_registrations',
                'players',
                'transactions',
                'notifications',
                'questions',
                'competitions_questions'
            ];

            $this->info("Running VACUUM ANALYZE on critical tables...");

            // Run VACUUM ANALYZE on each table individually
            foreach ($criticalTables as $table) {
                try {
                    // Check if table exists first
                    if (Schema::hasTable($table)) {
                        $this->line("  Vacuuming and analyzing {$table}...");
                        DB::statement("VACUUM ANALYZE {$table}");
                        $this->line("  ✅ {$table} completed");
                    } else {
                        $this->line("  ⏭️  {$table} skipped (table not found)");
                    }
                } catch (\Exception $e) {
                    $this->warn("  ⚠️  {$table} failed: " . $e->getMessage());
                }
            }

            // Run the maintenance function for additional tasks
            if ($this->functionExists('perform_database_maintenance')) {
                $this->info("Running additional maintenance tasks...");
                $result = DB::selectOne("SELECT perform_database_maintenance() as log")->log;
                $this->line("Additional maintenance: {$result}");
            }

            // Refresh materialized view if it exists
            if ($this->materializedViewExists('competition_player_stats')) {
                $this->info("🔄 Refreshing competition statistics view...");
                DB::statement("SELECT refresh_competition_stats()");
                $this->info("✅ Competition statistics refreshed");
            }

            $executionTime = round(microtime(true) - $startTime, 2);
            $this->newLine();
            $this->info("⏱️  Maintenance completed in {$executionTime} seconds");

        } catch (\Exception $e) {
            $this->error("Maintenance failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Perform data cleanup
     */
    private function performCleanup(): int
    {
        $retentionDays = $this->option('retention-days');

        if (!$this->option('force') && !$this->confirm("This will delete old data (retention: {$retentionDays} days). Continue?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info("🧹 Performing Data Cleanup...");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            if (!$this->functionExists('cleanup_old_data')) {
                $this->warn("Cleanup function not found. Run migrations first.");
                return 1;
            }

            $this->info("Cleaning up old data (retention: {$retentionDays} days)...");
            $result = DB::selectOne("SELECT cleanup_old_data({$retentionDays}) as log")->log;

            $this->newLine();
            $this->info("✅ Cleanup Results:");
            $this->line($result);

        } catch (\Exception $e) {
            $this->error("Cleanup failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Check database health
     */
    private function checkHealth(): int
    {
        $this->info("🏥 Database Health Check");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            if (!$this->functionExists('check_database_health')) {
                $this->warn("Health check function not found. Run migrations first.");
                return 1;
            }

            $healthChecks = DB::select("SELECT * FROM check_database_health()");

            foreach ($healthChecks as $check) {
                $statusIcon = match ($check->status) {
                    'GOOD' => '✅',
                    'ATTENTION' => '⚠️',
                    'WARNING' => '🚨',
                    default => '❓'
                };

                $this->line("{$statusIcon} {$check->check_name}: {$check->status}");
                $this->line("   Details: {$check->details}");
                $this->line("   Recommendation: {$check->recommendation}");
                $this->newLine();
            }

        } catch (\Exception $e) {
            $this->error("Health check failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Show detailed statistics
     */
    private function showStats(): int
    {
        $this->info("📈 Database Performance Statistics");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            // Performance summary
            if ($this->functionExists('get_database_performance_summary')) {
                $this->info("🎯 Performance Summary:");
                $summary = DB::select("SELECT * FROM get_database_performance_summary()");
                foreach ($summary as $metric) {
                    $this->line("  {$metric->metric_name}: {$metric->metric_value}");
                }
                $this->newLine();
            }

            // Competition load stats
            if ($this->functionExists('get_competition_load_stats')) {
                $this->info("🏆 Competition Load Statistics:");
                $loadStats = DB::select("SELECT * FROM get_competition_load_stats()");
                foreach ($loadStats as $stat) {
                    $this->line("  {$stat->metric}: {$stat->current_value} ({$stat->description})");
                }
                $this->newLine();
            }

            // Table statistics
            if ($this->functionExists('get_table_stats')) {
                $this->info("📊 Table Size Statistics:");
                $tableStats = DB::select("SELECT * FROM get_table_stats()");

                $headers = ['Table', 'Rows', 'Table Size', 'Index Size', 'Total Size'];
                $rows = [];
                foreach ($tableStats as $stat) {
                    $rows[] = [
                        $stat->table_name,
                        number_format($stat->row_count),
                        $stat->table_size,
                        $stat->index_size,
                        $stat->total_size
                    ];
                }

                $this->table($headers, $rows);
            }

            // Show slow queries if available
            if ($this->viewExists('slow_queries_report')) {
                $slowQueries = DB::select("SELECT * FROM slow_queries_report LIMIT 5");
                if (!empty($slowQueries)) {
                    $this->newLine();
                    $this->warn("⚠️  Top 5 Slow Queries (>100ms avg):");
                    foreach ($slowQueries as $query) {
                        $this->line("  • {$query->mean_time}ms avg ({$query->calls} calls)");
                        $this->line("    " . substr($query->query, 0, 80) . "...");
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error("Statistics retrieval failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $indexName): bool
    {
        try {
            $result = DB::selectOne("
                SELECT 1 FROM pg_indexes 
                WHERE indexname = ? AND schemaname = 'public'
            ", [$indexName]);

            return $result !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a materialized view exists
     */
    private function materializedViewExists(string $viewName): bool
    {
        try {
            $result = DB::selectOne("
                SELECT 1 FROM pg_matviews 
                WHERE matviewname = ? AND schemaname = 'public'
            ", [$viewName]);

            return $result !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a function exists
     */
    private function functionExists(string $functionName): bool
    {
        try {
            $result = DB::selectOne("
                SELECT 1 FROM pg_proc 
                WHERE proname = ? AND pg_function_is_visible(oid)
            ", [$functionName]);

            return $result !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a view exists
     */
    private function viewExists(string $viewName): bool
    {
        try {
            $result = DB::selectOne("
                SELECT 1 FROM information_schema.views 
                WHERE table_name = ? AND table_schema = 'public'
            ", [$viewName]);

            return $result !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
}
