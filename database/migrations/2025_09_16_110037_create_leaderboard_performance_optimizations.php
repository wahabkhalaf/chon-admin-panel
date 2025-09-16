<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Disable automatic database transactions for this migration
     * Required for creating indexes and functions that may conflict with transactions
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     * Create leaderboard performance optimizations for high-load competition platform
     */
    public function up(): void
    {
        // ============================================================================
        // CRITICAL PERFORMANCE INDEXES
        // ============================================================================

        $this->info("Creating critical performance indexes...");

        // Drop existing inefficient indexes if they exist
        $this->dropIndexIfExists('idx_competition_leaderboards_score_desc');
        $this->dropIndexIfExists('idx_competition_leaderboards_comp_rank');

        // CRITICAL: Composite index for score updates and ranking
        $this->createIndexConcurrently('idx_leaderboards_comp_score_updated', '
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_leaderboards_comp_score_updated 
            ON competition_leaderboards (
                competition_id,
                score DESC,
                updated_at ASC,
                player_id
            )
        ');

        // CRITICAL: Index for deadlock prevention - ordered by player_id to ensure consistent locking order
        $this->createIndexConcurrently('idx_leaderboards_player_comp_ordered', '
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_leaderboards_player_comp_ordered 
            ON competition_leaderboards (
                player_id ASC,
                competition_id ASC
            )
        ');

        // CRITICAL: Partial index for active competitions only
        $this->createIndexConcurrently('idx_leaderboards_active_competitions', '
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_leaderboards_active_competitions 
            ON competition_leaderboards (competition_id, rank ASC)
            WHERE score > 0
        ');

        // Index for answer processing performance
        $this->createIndexConcurrently('idx_player_answers_comp_time_player', '
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_player_answers_comp_time_player 
            ON competition_player_answers (
                competition_id,
                answered_at DESC,
                player_id,
                is_correct
            )
        ');

        // Index for total score updates
        $this->createIndexConcurrently('idx_players_total_score_updated', '
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_players_total_score_updated 
            ON players (
                total_score DESC,
                updated_at DESC
            )
            WHERE total_score > 0
        ');

        // ============================================================================
        // HIGH-PERFORMANCE STORED PROCEDURES
        // ============================================================================

        $this->info("Creating high-performance stored procedures...");

        // Drop existing functions if they exist
        $this->dropFunctionIfExists('batch_update_scores_safe(integer, jsonb)');
        $this->dropFunctionIfExists('recalculate_competition_ranks_fast(integer)');
        $this->dropFunctionIfExists('analyze_leaderboard_tables()');
        $this->dropFunctionIfExists('optimize_leaderboard_performance()');

        // Create the stored procedures
        $this->createBatchUpdateFunction();
        $this->createRankCalculationFunction();
        $this->createAnalysisFunction();
        $this->createMaintenanceFunction();
        $this->createPerformanceMonitorView();

        \Log::info('Leaderboard performance optimizations created successfully', [
            'migration' => '2025_09_16_110037_create_leaderboard_performance_optimizations',
            'indexes_created' => [
                'idx_leaderboards_comp_score_updated',
                'idx_leaderboards_player_comp_ordered',
                'idx_leaderboards_active_competitions',
                'idx_player_answers_comp_time_player',
                'idx_players_total_score_updated'
            ],
            'functions_created' => [
                'batch_update_scores_safe',
                'recalculate_competition_ranks_fast',
                'analyze_leaderboard_tables',
                'optimize_leaderboard_performance'
            ],
            'view_created' => 'leaderboard_performance_monitor'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->info("Rolling back leaderboard performance optimizations...");

        // Drop performance monitoring view
        DB::statement('DROP VIEW IF EXISTS leaderboard_performance_monitor');

        // Drop functions
        $this->dropFunctionIfExists('optimize_leaderboard_performance()');
        $this->dropFunctionIfExists('analyze_leaderboard_tables()');
        $this->dropFunctionIfExists('recalculate_competition_ranks_fast(integer)');
        $this->dropFunctionIfExists('batch_update_scores_safe(integer, jsonb)');

        // Drop indexes
        $this->dropIndexIfExists('idx_players_total_score_updated');
        $this->dropIndexIfExists('idx_player_answers_comp_time_player');
        $this->dropIndexIfExists('idx_leaderboards_active_competitions');
        $this->dropIndexIfExists('idx_leaderboards_player_comp_ordered');
        $this->dropIndexIfExists('idx_leaderboards_comp_score_updated');

        \Log::info('Leaderboard performance optimizations rolled back', [
            'migration_rollback' => '2025_09_16_110037_create_leaderboard_performance_optimizations'
        ]);
    }

    /**
     * Create batch update function
     */
    private function createBatchUpdateFunction(): void
    {
        DB::statement('
            CREATE OR REPLACE FUNCTION batch_update_scores_safe(
                p_competition_id integer, 
                p_updates jsonb
            ) RETURNS integer
            LANGUAGE plpgsql
            AS $$
            DECLARE
                v_update JSONB;
                v_player_id INTEGER;
                v_points INTEGER;
                v_current_score INTEGER;
                v_new_score INTEGER;
                v_processed INTEGER := 0;
                v_lock_acquired BOOLEAN := FALSE;
            BEGIN
                -- CRITICAL: Acquire advisory lock to prevent deadlocks across workers
                SELECT pg_try_advisory_xact_lock(p_competition_id) INTO v_lock_acquired;
                
                IF NOT v_lock_acquired THEN
                    RETURN 0;
                END IF;
                
                -- Process each update in the batch
                FOR v_update IN SELECT * FROM jsonb_array_elements(p_updates)
                LOOP
                    v_player_id := (v_update->>\'playerId\')::INTEGER;
                    v_points := (v_update->>\'points\')::INTEGER;
                    
                    -- Validate points
                    IF v_points >= 0 AND v_points <= 100 THEN
                        -- Get current score
                        SELECT COALESCE(score, 0) INTO v_current_score
                        FROM competition_leaderboards
                        WHERE competition_id = p_competition_id AND player_id = v_player_id
                        FOR UPDATE NOWAIT;
                        
                        -- Calculate new score
                        v_new_score := COALESCE(v_current_score, 0) + v_points;
                        
                        -- UPSERT operation
                        INSERT INTO competition_leaderboards (
                            competition_id, player_id, score, rank, created_at, updated_at
                        )
                        VALUES (
                            p_competition_id, v_player_id, v_new_score, 1, NOW(), NOW()
                        )
                        ON CONFLICT (competition_id, player_id) 
                        DO UPDATE SET 
                            score = EXCLUDED.score,
                            updated_at = NOW();
                        
                        -- Update total score in players table
                        IF v_points > 0 THEN
                            UPDATE players 
                            SET total_score = COALESCE(total_score, 0) + v_points,
                                updated_at = NOW()
                            WHERE id = v_player_id;
                        END IF;
                        
                        v_processed := v_processed + 1;
                    END IF;
                END LOOP;
                
                RETURN v_processed;
                
            EXCEPTION
                WHEN lock_not_available THEN
                    RETURN 0;
                WHEN OTHERS THEN
                    RAISE NOTICE \'Error in batch_update_scores_safe: %\', SQLERRM;
                    RAISE;
            END;
            $$
        ');
    }

    /**
     * Create rank calculation function
     */
    private function createRankCalculationFunction(): void
    {
        DB::statement('
            CREATE OR REPLACE FUNCTION recalculate_competition_ranks_fast(p_competition_id integer)
            RETURNS integer
            LANGUAGE plpgsql
            AS $$
            DECLARE
                v_updated_count INTEGER := 0;
            BEGIN
                WITH ranked_players AS (
                    SELECT 
                        player_id,
                        ROW_NUMBER() OVER (ORDER BY score DESC, updated_at ASC) as new_rank
                    FROM competition_leaderboards
                    WHERE competition_id = p_competition_id
                )
                UPDATE competition_leaderboards 
                SET rank = ranked_players.new_rank,
                    updated_at = CASE 
                        WHEN rank != ranked_players.new_rank THEN NOW() 
                        ELSE updated_at 
                    END
                FROM ranked_players
                WHERE competition_leaderboards.competition_id = p_competition_id 
                AND competition_leaderboards.player_id = ranked_players.player_id
                AND competition_leaderboards.rank != ranked_players.new_rank;
                
                GET DIAGNOSTICS v_updated_count = ROW_COUNT;
                RETURN v_updated_count;
            END;
            $$
        ');
    }

    /**
     * Create analysis function
     */
    private function createAnalysisFunction(): void
    {
        DB::statement('
            CREATE OR REPLACE FUNCTION analyze_leaderboard_tables()
            RETURNS text
            LANGUAGE plpgsql
            AS $$
            BEGIN
                ANALYZE competition_leaderboards;
                ANALYZE competition_player_answers;
                ANALYZE players;
                ANALYZE competitions;
                
                RETURN \'Leaderboard tables analyzed successfully at \' || NOW();
            END;
            $$
        ');
    }

    /**
     * Create maintenance function
     */
    private function createMaintenanceFunction(): void
    {
        DB::statement('
            CREATE OR REPLACE FUNCTION optimize_leaderboard_performance()
            RETURNS text
            LANGUAGE plpgsql
            AS $$
            DECLARE
                result_text TEXT := \'\';
            BEGIN
                VACUUM (ANALYZE, VERBOSE) competition_leaderboards;
                result_text := result_text || \'Vacuumed competition_leaderboards; \';
                
                VACUUM (ANALYZE, VERBOSE) competition_player_answers;
                result_text := result_text || \'Vacuumed competition_player_answers; \';
                
                REINDEX INDEX CONCURRENTLY idx_leaderboards_comp_score_updated;
                result_text := result_text || \'Reindexed leaderboard indexes; \';
                
                RETURN result_text || \'Optimization completed at \' || NOW();
            END;
            $$
        ');
    }

    /**
     * Create performance monitoring view
     */
    private function createPerformanceMonitorView(): void
    {
        DB::statement('
            CREATE OR REPLACE VIEW leaderboard_performance_monitor AS
            SELECT
                \'Competition Leaderboard Performance\' as metric_category,
                schemaname,
                relname as tablename,
                seq_scan as sequential_scans,
                seq_tup_read as sequential_tuples_read,
                idx_scan as index_scans,
                idx_tup_fetch as index_tuples_fetched,
                n_tup_ins as inserts,
                n_tup_upd as updates,
                n_tup_del as deletes,
                n_live_tup as live_tuples,
                n_dead_tup as dead_tuples,
                CASE
                    WHEN (seq_scan + idx_scan) > 0 THEN ROUND(
                        100.0 * idx_scan / (seq_scan + idx_scan),
                        2
                    )
                    ELSE 0
                END as index_usage_percentage
            FROM pg_stat_user_tables
            WHERE
                relname IN (
                    \'competition_leaderboards\',
                    \'competition_player_answers\',
                    \'players\'
                )
            ORDER BY relname
        ');
    }

    /**
     * Create index concurrently with error handling
     */
    private function createIndexConcurrently(string $indexName, string $sql): void
    {
        try {
            $this->info("Creating index: {$indexName}");
            DB::statement($sql);
            $this->info("✅ {$indexName} created successfully");
        } catch (\Exception $e) {
            $this->warn("⚠️  {$indexName} creation failed: " . $e->getMessage());
        }
    }

    /**
     * Drop index if it exists
     */
    private function dropIndexIfExists(string $indexName): void
    {
        try {
            DB::statement("DROP INDEX IF EXISTS {$indexName}");
        } catch (\Exception $e) {
            // Ignore errors when dropping indexes
        }
    }

    /**
     * Drop function if it exists
     */
    private function dropFunctionIfExists(string $functionSignature): void
    {
        try {
            DB::statement("DROP FUNCTION IF EXISTS {$functionSignature}");
        } catch (\Exception $e) {
            // Ignore errors when dropping functions
        }
    }

    /**
     * Output info message
     */
    private function info(string $message): void
    {
        echo "ℹ️  {$message}\n";
    }

    /**
     * Output warning message
     */
    private function warn(string $message): void
    {
        echo "⚠️  {$message}\n";
    }
};
