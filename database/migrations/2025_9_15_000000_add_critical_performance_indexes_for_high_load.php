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

        $this->createIndexIfTableExists('competition_player_answers', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_player_answers_comp_player ON competition_player_answers (competition_id, player_id)');

        $this->createIndexIfTableExists('competition_player_answers', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_player_answers_answered_at ON competition_player_answers (answered_at)');

        $this->createIndexIfTableExists('competition_player_answers', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_player_answers_comp_correct ON competition_player_answers (competition_id, is_correct, answered_at)');

        $this->createIndexIfTableExists('competition_player_answers', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_player_answers_comp_correct_time ON competition_player_answers (competition_id, is_correct, answered_at, player_id)');

        $this->createIndexIfTableExists('competition_player_answers', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_answers_performance ON competition_player_answers (competition_id, is_correct, answered_at DESC)');

        $this->createIndexIfTableExists('competition_player_answers', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_answers_player_time ON competition_player_answers (player_id, answered_at DESC, competition_id)');

        // ============================================================================
        // COMPETITION LEADERBOARDS - For fast ranking queries
        // ============================================================================

        $this->createIndexIfTableExists('competition_leaderboards', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_leaderboards_score_desc ON competition_leaderboards (score DESC, updated_at ASC)');

        $this->createIndexIfTableExists('competition_leaderboards', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_leaderboards_comp_rank ON competition_leaderboards (competition_id, rank ASC)');

        $this->createIndexIfTableExists('competition_leaderboards', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_leaderboards_comp_score ON competition_leaderboards (competition_id, score DESC, updated_at ASC)');

        $this->createIndexIfTableExists('competition_leaderboards', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_leaderboards_player_comp ON competition_leaderboards (player_id, competition_id)');

        // ============================================================================
        // PLAYERS - For user management and score tracking
        // ============================================================================

        $this->createIndexIfTableExists('players', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_players_total_score ON players (total_score DESC) WHERE total_score > 0');

        $this->createIndexIfTableExists('players', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_players_phone_verified ON players (phone_number) WHERE is_verified = true');

        $this->createIndexIfTableExists('players', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_players_fcm_token ON players (fcm_token) WHERE fcm_token IS NOT NULL');

        // ============================================================================
        // COMPETITIONS - For competition queries and filtering
        // ============================================================================

        $this->createIndexIfTableExists('competitions', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competitions_times_status ON competitions (start_time, end_time, status)');

        $this->createIndexIfTableExists('competitions', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competitions_active ON competitions (start_time, end_time) WHERE start_time <= NOW() AND end_time >= NOW()');

        $this->createIndexIfTableExists('competitions', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competitions_entry_fee ON competitions (entry_fee, start_time) WHERE entry_fee > 0');

        // ============================================================================
        // COMPETITION REGISTRATIONS - For participant tracking
        // ============================================================================

        $this->createIndexIfTableExists('competition_registrations', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_registrations_comp_player ON competition_registrations (competition_id, player_id)');

        $this->createIndexIfTableExists('competition_registrations', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competition_registrations_player_created ON competition_registrations (player_id, created_at DESC)');

        // ============================================================================
        // TRANSACTIONS - For payment and financial queries
        // ============================================================================

        $this->createIndexIfTableExists('transactions', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_player_status ON transactions (player_id, status, created_at)');

        $this->createIndexIfTableExists('transactions', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_comp_status ON transactions (competition_id, status, created_at)');

        $this->createIndexIfTableExists('transactions', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_competition_status_player ON transactions (competition_id, status, player_id) WHERE status = \'completed\'');

        // ============================================================================
        // PLAYER NOTIFICATIONS - For notification queries (only if table exists)
        // ============================================================================

        $this->createIndexIfTableExists('player_notifications', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_player_notifications_player_read ON player_notifications (player_id, read_at, received_at)');

        // ============================================================================
        // NOTIFICATIONS - For scheduled notifications and admin notifications
        // ============================================================================

        $this->createIndexIfTableExists('notifications', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_notifications_scheduled_status ON notifications (scheduled_at, status) WHERE scheduled_at IS NOT NULL');

        // ============================================================================
        // QUESTIONS - For question management
        // ============================================================================

        $this->createIndexIfTableExists('questions', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_questions_difficulty_type ON questions (difficulty_level, question_type)');

        // ============================================================================
        // COMPETITIONS_QUESTIONS - For competition-question relationships
        // ============================================================================

        $this->createIndexIfTableExists('competitions_questions', 'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_competitions_questions_comp_order ON competitions_questions (competition_id, question_order)');

        \Log::info('Critical performance indexes creation completed', [
            'migration' => '2025_9_15_000000_add_critical_performance_indexes_for_high_load',
            'note' => 'Indexes created only for existing tables'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all the indexes we created
        $indexes = [
            'idx_competition_player_answers_comp_player',
            'idx_competition_player_answers_answered_at',
            'idx_competition_player_answers_comp_correct',
            'idx_competition_player_answers_comp_correct_time',
            'idx_competition_answers_performance',
            'idx_competition_answers_player_time',
            'idx_competition_leaderboards_score_desc',
            'idx_competition_leaderboards_comp_rank',
            'idx_competition_leaderboards_comp_score',
            'idx_competition_leaderboards_player_comp',
            'idx_players_total_score',
            'idx_players_phone_verified',
            'idx_players_fcm_token',
            'idx_competitions_times_status',
            'idx_competitions_active',
            'idx_competitions_entry_fee',
            'idx_competition_registrations_comp_player',
            'idx_competition_registrations_player_created',
            'idx_transactions_player_status',
            'idx_transactions_comp_status',
            'idx_transactions_competition_status_player',
            'idx_player_notifications_player_read',
            'idx_notifications_scheduled_status',
            'idx_questions_difficulty_type',
            'idx_competitions_questions_comp_order'
        ];

        foreach ($indexes as $index) {
            try {
                DB::statement("DROP INDEX IF EXISTS {$index}");
            } catch (\Exception $e) {
                \Log::warning("Could not drop index {$index}: " . $e->getMessage());
            }
        }

        \Log::info('Critical performance indexes dropped', [
            'migration_rollback' => '2025_9_15_000000_add_critical_performance_indexes_for_high_load'
        ]);
    }

    /**
     * Check if a table exists
     */
    private function tableExists(string $tableName): bool
    {
        try {
            return Schema::hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create index only if table exists
     */
    private function createIndexIfTableExists(string $tableName, string $sql): void
    {
        if ($this->tableExists($tableName)) {
            try {
                DB::statement($sql);
            } catch (\Exception $e) {
                \Log::warning("Could not create index for table {$tableName}: " . $e->getMessage());
            }
        } else {
            \Log::info("Skipping index creation for non-existent table: {$tableName}");
        }
    }
};
