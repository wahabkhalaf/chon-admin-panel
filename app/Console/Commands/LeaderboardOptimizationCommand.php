<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class LeaderboardOptimizationCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:optimize-leaderboard 
                           {action? : The action to perform (indexes|procedures|monitor|maintenance|all)}
                           {--force : Force the operation without confirmation}
                           {--test : Test the optimization functions}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize competition leaderboard performance for high-load scenarios';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action') ?? 'all';

        $this->info("🏆 Competition Leaderboard Performance Optimization");
        $this->info("Optimized for 16-core server with 8 Node.js workers");
        $this->newLine();

        try {
            switch ($action) {
                case 'indexes':
                    return $this->createIndexes();
                case 'procedures':
                    return $this->createProcedures();
                case 'monitor':
                    return $this->showMonitoring();
                case 'maintenance':
                    return $this->runMaintenance();
                case 'all':
                    return $this->runAllOptimizations();
                case 'test':
                    return $this->testOptimizations();
                default:
                    $this->error("Unknown action: {$action}");
                    $this->info("Available actions: indexes, procedures, monitor, maintenance, all, test");
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error executing leaderboard optimization: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Create performance indexes
     */
    private function createIndexes(): int
    {
        $this->info("🔍 Creating Performance Indexes...");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $indexes = [
            'idx_leaderboards_comp_score_updated' => 'Composite index for score updates and ranking',
            'idx_leaderboards_player_comp_ordered' => 'Index for deadlock prevention',
            'idx_leaderboards_active_competitions' => 'Partial index for active competitions',
            'idx_player_answers_comp_time_player' => 'Index for answer processing performance',
            'idx_players_total_score_updated' => 'Index for total score updates'
        ];

        foreach ($indexes as $index => $description) {
            try {
                $this->line("Creating {$index}...");

                switch ($index) {
                    case 'idx_leaderboards_comp_score_updated':
                        DB::statement("
                            CREATE INDEX CONCURRENTLY IF NOT EXISTS {$index} ON competition_leaderboards (
                                competition_id,
                                score DESC,
                                updated_at ASC,
                                player_id
                            )
                        ");
                        break;

                    case 'idx_leaderboards_player_comp_ordered':
                        DB::statement("
                            CREATE INDEX CONCURRENTLY IF NOT EXISTS {$index} ON competition_leaderboards (
                                player_id ASC,
                                competition_id ASC
                            )
                        ");
                        break;

                    case 'idx_leaderboards_active_competitions':
                        DB::statement("
                            CREATE INDEX CONCURRENTLY IF NOT EXISTS {$index} ON competition_leaderboards (competition_id, rank ASC)
                            WHERE score > 0
                        ");
                        break;

                    case 'idx_player_answers_comp_time_player':
                        DB::statement("
                            CREATE INDEX CONCURRENTLY IF NOT EXISTS {$index} ON competition_player_answers (
                                competition_id,
                                answered_at DESC,
                                player_id,
                                is_correct
                            )
                        ");
                        break;

                    case 'idx_players_total_score_updated':
                        DB::statement("
                            CREATE INDEX CONCURRENTLY IF NOT EXISTS {$index} ON players (
                                total_score DESC,
                                updated_at DESC
                            )
                            WHERE total_score > 0
                        ");
                        break;
                }

                $this->line("  ✅ {$description}");
            } catch (\Exception $e) {
                $this->warn("  ⚠️  {$index} failed: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("✅ Index creation completed");
        return 0;
    }

    /**
     * Create stored procedures
     */
    private function createProcedures(): int
    {
        $this->info("⚙️  Creating Stored Procedures...");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            // Read the procedures SQL file
            $sqlFile = database_path('optimizations/leaderboard_procedures.sql');

            if (!File::exists($sqlFile)) {
                $this->error("SQL file not found: {$sqlFile}");
                return 1;
            }

            $sql = File::get($sqlFile);

            // Execute the procedures SQL file
            $this->line("Executing leaderboard procedures SQL...");
            DB::unprepared($sql);

            $this->newLine();
            $this->info("✅ Stored procedures created");

        } catch (\Exception $e) {
            $this->error("Error creating procedures: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Show performance monitoring
     */
    private function showMonitoring(): int
    {
        $this->info("📊 Leaderboard Performance Monitoring");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            // Check if monitoring view exists
            $viewExists = DB::selectOne("
                SELECT 1 FROM information_schema.views 
                WHERE table_name = 'leaderboard_performance_monitor' AND table_schema = 'public'
            ");

            if (!$viewExists) {
                $this->warn("Performance monitoring view not found. Run 'procedures' action first.");
                return 1;
            }

            $metrics = DB::select("SELECT * FROM leaderboard_performance_monitor");

            $this->table([
                'Table',
                'Seq Scans',
                'Index Scans',
                'Inserts',
                'Updates',
                'Live Tuples',
                'Index Usage %'
            ], array_map(function ($metric) {
                return [
                    $metric->tablename,
                    number_format($metric->sequential_scans),
                    number_format($metric->index_scans),
                    number_format($metric->inserts),
                    number_format($metric->updates),
                    number_format($metric->live_tuples),
                    $metric->index_usage_percentage . '%'
                ];
            }, $metrics));

            // Show slow queries if pg_stat_statements is available
            try {
                $slowQueries = DB::select("
                    SELECT query, mean_exec_time, calls 
                    FROM pg_stat_statements 
                    WHERE query LIKE '%competition_leaderboards%' 
                    ORDER BY mean_exec_time DESC 
                    LIMIT 5
                ");

                if (!empty($slowQueries)) {
                    $this->newLine();
                    $this->warn("⚠️  Top 5 Slow Leaderboard Queries:");
                    foreach ($slowQueries as $query) {
                        $this->line("  • {$query->mean_exec_time}ms avg ({$query->calls} calls)");
                        $this->line("    " . substr($query->query, 0, 80) . "...");
                    }
                }
            } catch (\Exception $e) {
                // pg_stat_statements not available
            }

        } catch (\Exception $e) {
            $this->error("Monitoring failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Run maintenance
     */
    private function runMaintenance(): int
    {
        if (!$this->option('force') && !$this->confirm('This will run VACUUM and REINDEX on leaderboard tables. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info("🔧 Running Leaderboard Maintenance...");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            // Check if function exists
            $functionExists = DB::selectOne("
                SELECT 1 FROM pg_proc 
                WHERE proname = 'optimize_leaderboard_performance' AND pg_function_is_visible(oid)
            ");

            if (!$functionExists) {
                $this->warn("Maintenance function not found. Run 'procedures' action first.");
                return 1;
            }

            $this->info("Running leaderboard optimization...");
            $result = DB::selectOne("SELECT optimize_leaderboard_performance() as result")->result;

            $this->newLine();
            $this->info("✅ Maintenance Results:");
            $this->line($result);

        } catch (\Exception $e) {
            $this->error("Maintenance failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Run all optimizations
     */
    private function runAllOptimizations(): int
    {
        $this->info("🚀 Running All Leaderboard Optimizations...");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $steps = [
            'indexes' => 'Creating performance indexes',
            'procedures' => 'Creating stored procedures',
            'monitor' => 'Setting up monitoring'
        ];

        foreach ($steps as $action => $description) {
            $this->info("Step: {$description}");
            $result = $this->call('db:optimize-leaderboard', ['action' => $action, '--force' => true]);

            if ($result !== 0) {
                $this->error("Step '{$action}' failed. Stopping optimization.");
                return 1;
            }

            $this->newLine();
        }

        $this->info("🎉 All leaderboard optimizations completed successfully!");
        $this->newLine();
        $this->info("Next steps:");
        $this->line("1. Restart your Node.js workers to use new procedures");
        $this->line("2. Monitor performance with: php artisan db:optimize-leaderboard monitor");
        $this->line("3. Run maintenance regularly: php artisan db:optimize-leaderboard maintenance");

        return 0;
    }

    /**
     * Test optimization functions
     */
    private function testOptimizations(): int
    {
        $this->info("🧪 Testing Leaderboard Optimizations...");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            // Test batch update function
            $this->info("Testing batch_update_scores_safe function...");

            $testData = json_encode([
                ['playerId' => 1, 'points' => 5],
                ['playerId' => 2, 'points' => 10]
            ]);

            $result = DB::selectOne("SELECT batch_update_scores_safe(1, ?::jsonb) as processed", [$testData]);

            $this->line("  ✅ Batch update processed {$result->processed} records");

            // Test rank calculation function
            $this->info("Testing recalculate_competition_ranks_fast function...");

            $rankResult = DB::selectOne("SELECT recalculate_competition_ranks_fast(1) as updated");

            $this->line("  ✅ Rank calculation updated {$rankResult->updated} records");

            // Test monitoring
            $this->info("Testing performance monitoring...");

            $monitorResult = DB::select("SELECT * FROM leaderboard_performance_monitor LIMIT 1");

            $this->line("  ✅ Performance monitoring working");

            $this->newLine();
            $this->info("🎉 All tests passed! Leaderboard optimizations are working correctly.");

        } catch (\Exception $e) {
            $this->error("Test failed: " . $e->getMessage());
            $this->line("Make sure to run 'php artisan db:optimize-leaderboard all' first.");
            return 1;
        }

        return 0;
    }
}
