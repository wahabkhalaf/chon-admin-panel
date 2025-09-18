<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->info('🚀 Creating Batch Leaderboard Optimization...');

        // Read the SQL file
        $sqlFile = database_path('optimizations/batch_leaderboard_optimization.sql');

        if (!File::exists($sqlFile)) {
            throw new \Exception("SQL file not found: {$sqlFile}");
        }

        $sql = File::get($sqlFile);

        // Execute the SQL file
        $this->info('Executing batch leaderboard optimization SQL...');
        DB::unprepared($sql);

        $this->info('✅ Batch leaderboard optimization completed successfully!');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->info('🔄 Reverting Batch Leaderboard Optimization...');

        // Drop functions
        DB::statement('DROP FUNCTION IF EXISTS batch_update_leaderboard_nodejs(INTEGER, JSONB)');
        DB::statement('DROP FUNCTION IF EXISTS recalculate_leaderboard_ranks_batch(INTEGER, INTEGER)');
        DB::statement('DROP FUNCTION IF EXISTS get_batch_performance_stats(INTEGER)');

        // Drop materialized view
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS competition_player_stats');

        // Drop indexes
        DB::statement('DROP INDEX IF EXISTS idx_leaderboard_score_time_optimized');
        DB::statement('DROP INDEX IF EXISTS idx_leaderboard_player_competition_optimized');
        DB::statement('DROP INDEX IF EXISTS idx_leaderboard_rank_optimized');

        // Clean up test data
        DB::statement('DELETE FROM competition_leaderboards WHERE competition_id = 9999');
        DB::statement('DELETE FROM competitions WHERE id = 9999');
        DB::statement('DELETE FROM players WHERE id IN (9999, 9998, 9997)');

        $this->info('✅ Batch leaderboard optimization reverted successfully!');
    }

    private function info(string $message): void
    {
        echo $message . PHP_EOL;
    }
};