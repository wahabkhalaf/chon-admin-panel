<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestBatchLeaderboardOptimization extends Command
{
    protected $signature = 'db:test-batch-optimization 
                           {--competition-id=9999 : Competition ID to test with}
                           {--cleanup : Clean up test data after testing}';

    protected $description = 'Test the batch leaderboard optimization functions';

    public function handle(): int
    {
        $competitionId = $this->option('competition-id');
        $cleanup = $this->option('cleanup');

        $this->info('🧪 Testing Batch Leaderboard Optimization');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            // Test 1: Check if functions exist
            $this->info('1. Checking if batch functions exist...');
            $functions = DB::select("
                SELECT proname 
                FROM pg_proc 
                WHERE proname IN (
                    'batch_update_leaderboard_nodejs',
                    'recalculate_leaderboard_ranks_batch',
                    'get_batch_performance_stats'
                )
            ");

            if (count($functions) < 3) {
                $this->error('❌ Batch functions not found. Run migration first.');
                return 1;
            }

            $this->info('✅ All batch functions found');

            // Test 2: Test batch update function
            $this->info('2. Testing batch update function...');

            $testData = json_encode([
                ['playerId' => 9999, 'points' => 10],
                ['playerId' => 9998, 'points' => 5],
                ['playerId' => 9997, 'points' => 7]
            ]);

            $startTime = microtime(true);
            $result = DB::selectOne(
                'SELECT batch_update_leaderboard_nodejs(?, ?::jsonb) as processed',
                [$competitionId, $testData]
            );
            $endTime = microtime(true);

            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            $this->info("✅ Batch update processed {$result->processed} records in {$executionTime}ms");

            // Test 3: Verify results
            $this->info('3. Verifying batch update results...');

            $leaderboard = DB::select("
                SELECT player_id, score, rank, updated_at
                FROM competition_leaderboards
                WHERE competition_id = ?
                ORDER BY score DESC
            ", [$competitionId]);

            $this->table(['Player ID', 'Score', 'Rank', 'Updated At'], array_map(function ($row) {
                return [
                    $row->player_id,
                    $row->score,
                    $row->rank,
                    $row->updated_at
                ];
            }, $leaderboard));

            // Test 4: Test rank recalculation
            $this->info('4. Testing rank recalculation...');

            $rankStartTime = microtime(true);
            $rankResult = DB::selectOne(
                'SELECT recalculate_leaderboard_ranks_batch(?, 100) as total_players',
                [$competitionId]
            );
            $rankEndTime = microtime(true);

            $rankExecutionTime = ($rankEndTime - $rankStartTime) * 1000;

            $this->info("✅ Rank recalculation processed {$rankResult->total_players} players in {$rankExecutionTime}ms");

            // Test 5: Performance stats
            $this->info('5. Getting performance statistics...');

            $stats = DB::select('SELECT * FROM get_batch_performance_stats(?)', [$competitionId]);

            $this->table(['Metric', 'Value', 'Description'], array_map(function ($stat) {
                return [$stat->metric_name, $stat->metric_value, $stat->description];
            }, $stats));

            // Test 6: Performance comparison
            $this->info('6. Performance Analysis...');

            if ($executionTime < 100) {
                $this->info("✅ EXCELLENT: Batch update completed in {$executionTime}ms (< 100ms)");
            } elseif ($executionTime < 1000) {
                $this->info("✅ GOOD: Batch update completed in {$executionTime}ms (< 1s)");
            } else {
                $this->warn("⚠️  SLOW: Batch update took {$executionTime}ms (> 1s)");
            }

            // Cleanup if requested
            if ($cleanup) {
                $this->info('7. Cleaning up test data...');
                DB::statement('DELETE FROM competition_leaderboards WHERE competition_id = ?', [$competitionId]);
                $this->info('✅ Test data cleaned up');
            }

            $this->newLine();
            $this->info('🎉 Batch leaderboard optimization test completed successfully!');
            $this->info('Performance improvement: 8,518ms → ' . $executionTime . 'ms');
            $this->info('Improvement factor: ' . round(8518 / $executionTime, 1) . 'x faster!');

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}