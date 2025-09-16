<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Disable automatic database transactions for this migration
     * Required because CREATE INDEX CONCURRENTLY cannot run inside a transaction
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     * Critical Performance Indexes for 100K+ Users Competition Platform
     * These indexes will dramatically improve query performance for high-load scenarios
     */
    public function up(): void
    {
        // Try to enable pg_stat_statements extension for query monitoring
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_stat_statements');
        } catch (\Exception $e) {
            \Log::warning('Could not create pg_stat_statements extension. This is expected if not configured in shared_preload_libraries.', [
                'error' => $e->getMessage()
            ]);
        }

        // ============================================================================
        // COMPETITION PLAYER ANSWERS - Critical for leaderboard calculations
        // ============================================================================

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_player_answers_comp_player ON competition_player_answers (competition_id, player_id)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_player_answers_answered_at ON competition_player_answers (answered_at)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_player_answers_comp_correct ON competition_player_answers (competition_id, is_correct, answered_at)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_player_answers_comp_correct_time ON competition_player_answers (competition_id, is_correct, answered_at, player_id)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_answers_performance ON competition_player_answers (competition_id, is_correct, answered_at DESC)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_answers_player_time ON competition_player_answers (player_id, answered_at DESC, competition_id)');

        // ============================================================================
        // COMPETITION LEADERBOARDS - For fast ranking queries
        // ============================================================================

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_leaderboards_comp_score ON competition_leaderboards (competition_id, score DESC)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_leaderboards_comp_rank ON competition_leaderboards (competition_id, rank ASC)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_leaderboard_competition_score_time ON competition_leaderboards (competition_id, score DESC, updated_at ASC)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_leaderboard_player_competition ON competition_leaderboards (player_id, competition_id, score DESC)');

        // ============================================================================
        // COMPETITION REGISTRATIONS - For registration status checks
        // ============================================================================

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_registrations_status_time ON competition_registrations (registration_status, registered_at)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_registrations_comp_status ON competition_registrations (competition_id, registration_status)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_registrations_player_status ON competition_registrations (player_id, registration_status)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_registrations_competition_status ON competition_registrations (competition_id, registration_status, created_at)');

        // ============================================================================
        // PLAYERS - For player lookups and rankings
        // ============================================================================

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_players_total_score ON players (total_score DESC)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_players_level_score ON players (level, total_score DESC)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_players_total_score_updated ON players (total_score DESC, updated_at DESC)');

        // ============================================================================
        // COMPETITIONS - For time-based queries and status filtering
        // ============================================================================

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competitions_times_status ON competitions (start_time, end_time, open_time)');

        // Additional indexes for competition time-based queries (without partial conditions due to NOW() immutability)
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competitions_open_time ON competitions (open_time, start_time, id)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competitions_start_end_time ON competitions (start_time, end_time, id)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competitions_end_time ON competitions (end_time DESC, id)');

        // ============================================================================
        // TRANSACTIONS - For payment processing and player statistics
        // ============================================================================

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_player_status ON transactions (player_id, status, created_at)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_comp_status ON transactions (competition_id, status, created_at)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_competition_status_player ON transactions (competition_id, status, player_id) WHERE status = \'completed\'');

        // ============================================================================
        // PLAYER NOTIFICATIONS - For notification queries
        // ============================================================================

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_player_notifications_player_read ON player_notifications (player_id, read_at, received_at)');

        // ============================================================================
        // NOTIFICATIONS - For scheduled notifications and admin notifications
        // ============================================================================

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_notifications_scheduled_status ON notifications (scheduled_at, status) WHERE scheduled_at IS NOT NULL');

        // Skip GIN index on JSON data column due to operator class compatibility issues
        // DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_notifications_data_gin ON notifications USING gin (data) WHERE data IS NOT NULL');

        // Instead, create a regular index on commonly queried JSON fields if needed
        // You can add specific indexes like: CREATE INDEX ON notifications ((data->>'field_name'));
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_notifications_data_btree ON notifications ((data::text)) WHERE data IS NOT NULL');

        // ============================================================================
        // QUESTIONS - For question fetching and assignment
        // ============================================================================

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_questions_level_type ON questions (level, question_type)');

        // ============================================================================
        // COMPETITION QUESTIONS - For question assignment to competitions
        // ============================================================================

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competitions_questions_comp ON competitions_questions (competition_id, created_at)');

        // ============================================================================
        // MATERIALIZED VIEW FOR COMPETITION STATISTICS (Optional but recommended)
        // ============================================================================

        DB::statement('
            CREATE MATERIALIZED VIEW IF NOT EXISTS competition_player_stats AS
            SELECT
                competition_id,
                player_id,
                COUNT(CASE WHEN is_correct = true THEN 1 END) as correct_answers,
                COUNT(*) as total_answers,
                AVG(CASE WHEN is_correct = true THEN EXTRACT(EPOCH FROM answered_at) END) as avg_correct_response_time,
                MIN(answered_at) as first_answer_time,
                MAX(answered_at) as last_answer_time
            FROM competition_player_answers
            GROUP BY competition_id, player_id
        ');

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_competition_player_stats_unique ON competition_player_stats (competition_id, player_id)');

        // ============================================================================
        // ANALYZE TABLES FOR BETTER QUERY PLANNING
        // ============================================================================

        // Update table statistics for better query planning
        DB::statement("
            UPDATE pg_class SET reltuples = -1 
            WHERE relname IN (
                'competitions', 'competition_leaderboards', 'competition_player_answers',
                'transactions', 'players', 'notifications', 'competition_registrations',
                'player_notifications', 'questions', 'competitions_questions'
            )
        ");

        // Analyze all critical tables
        $tables = [
            'competitions',
            'competition_leaderboards',
            'competition_player_answers',
            'competition_registrations',
            'players',
            'transactions',
            'player_notifications',
            'notifications',
            'questions',
            'competitions_questions'
        ];

        foreach ($tables as $table) {
            DB::statement("ANALYZE {$table}");
        }

        // Reset pg_stat_statements to start fresh monitoring (if available)
        try {
            DB::statement('SELECT pg_stat_statements_reset()');
        } catch (\Exception $e) {
            \Log::info('pg_stat_statements_reset not available - extension not loaded in shared_preload_libraries');
        }

        // Log completion
        \Log::info('Critical performance indexes migration completed successfully', [
            'migration' => '2025_01_15_000000_add_critical_performance_indexes_for_high_load',
            'indexes_created' => 'All critical performance indexes for 100K+ users',
            'tables_analyzed' => count($tables),
            'materialized_view' => 'competition_player_stats created'
        ]);
    }

    /**
     * Reverse the migrations.
     * WARNING: Dropping these indexes will severely impact performance
     */
    public function down(): void
    {
        // Log warning
        \Log::warning('Dropping critical performance indexes - this will severely impact performance!');

        // Drop materialized view first
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS competition_player_stats');

        // Drop all performance indexes
        $indexes = [
            // Competition Player Answers indexes
            'idx_competition_player_answers_comp_player',
            'idx_competition_player_answers_answered_at',
            'idx_competition_player_answers_comp_correct',
            'idx_competition_player_answers_comp_correct_time',
            'idx_competition_answers_performance',
            'idx_competition_answers_player_time',

            // Competition Leaderboards indexes
            'idx_competition_leaderboards_comp_score',
            'idx_competition_leaderboards_comp_rank',
            'idx_leaderboard_competition_score_time',
            'idx_leaderboard_player_competition',

            // Competition Registrations indexes
            'idx_competition_registrations_status_time',
            'idx_competition_registrations_comp_status',
            'idx_competition_registrations_player_status',
            'idx_registrations_competition_status',

            // Players indexes
            'idx_players_total_score',
            'idx_players_level_score',
            'idx_players_total_score_updated',

            // Competitions indexes
            'idx_competitions_times_status',
            'idx_competitions_open',
            'idx_competitions_active',
            'idx_competitions_completed',

            // Transactions indexes
            'idx_transactions_player_status',
            'idx_transactions_comp_status',
            'idx_transactions_competition_status_player',

            // Notifications indexes
            'idx_player_notifications_player_read',
            'idx_notifications_scheduled_status',
            'idx_notifications_data_gin',

            // Questions indexes
            'idx_questions_level_type',

            // Competition Questions indexes
            'idx_competitions_questions_comp',

            // Materialized view index
            'idx_competition_player_stats_unique'
        ];

        foreach ($indexes as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }

        \Log::info('Critical performance indexes dropped', [
            'migration_rollback' => '2025_01_15_000000_add_critical_performance_indexes_for_high_load',
            'indexes_dropped' => count($indexes)
        ]);
    }
};
