<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations - Add comprehensive indexes to prevent resource exhaustion
     */
    public function up(): void
    {
        // ============================================================================
        // CRITICAL INDEXES FOR COMMON QUERIES
        // ============================================================================

        // Players table - common filters
        Schema::table('players', function (Blueprint $table) {
            $table->index(['is_verified', 'joined_at'], 'idx_players_verified_joined');
            $table->index(['total_score', 'level'], 'idx_players_score_level');
            $table->index(['language'], 'idx_players_language');
        });

        // Competitions table - status queries
        Schema::table('competitions', function (Blueprint $table) {
            $table->index(['open_time', 'start_time', 'end_time'], 'idx_competitions_times');
            $table->index(['game_type', 'entry_fee'], 'idx_competitions_game_fee');
        });

        // Transactions table - user and status queries
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['player_id', 'status', 'created_at'], 'idx_transactions_player_status_date');
            $table->index(['competition_id', 'status'], 'idx_transactions_competition_status');
            $table->index(['payment_method', 'status'], 'idx_transactions_payment_status');
            $table->index(['created_at', 'status'], 'idx_transactions_date_status');
        });

        // Notifications table - read status and timestamps
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_notifications_status_date');
            $table->index(['scheduled_at', 'status'], 'idx_notifications_scheduled_status');
        });

        // Player Notifications - read/unread queries
        Schema::table('player_notifications', function (Blueprint $table) {
            $table->index(['player_id', 'read_at'], 'idx_player_notif_player_read');
            $table->index(['player_id', 'created_at'], 'idx_player_notif_player_date');
            $table->index(['read_at', 'created_at'], 'idx_player_notif_read_date');
        });

        // Competition Questions
        Schema::table('competitions_questions', function (Blueprint $table) {
            $table->index(['competition_id', 'question_id'], 'idx_comp_questions_compound');
        });

        // Competition Player Answers - query optimization
        Schema::table('competition_player_answers', function (Blueprint $table) {
            $table->index(['player_id', 'competition_id'], 'idx_answers_player_competition');
            $table->index(['competition_id', 'is_correct'], 'idx_answers_comp_correct');
            $table->index(['answered_at', 'player_id'], 'idx_answers_time_player');
        });

        // Competition Registrations (already has good indexes, skip duplicates)
        // Indexes already exist: player_id, registration_status, competition_id

        // Questions table - language and level queries
        Schema::table('questions', function (Blueprint $table) {
            $table->index(['level', 'question_type'], 'idx_questions_level_type');
            $table->index(['created_at', 'level'], 'idx_questions_date_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex('idx_players_verified_joined');
            $table->dropIndex('idx_players_score_level');
            $table->dropIndex('idx_players_language');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropIndex('idx_competitions_times');
            $table->dropIndex('idx_competitions_game_fee');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_player_status_date');
            $table->dropIndex('idx_transactions_competition_status');
            $table->dropIndex('idx_transactions_payment_status');
            $table->dropIndex('idx_transactions_date_status');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_status_date');
            $table->dropIndex('idx_notifications_scheduled_status');
        });

        Schema::table('player_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_player_notif_player_read');
            $table->dropIndex('idx_player_notif_player_date');
            $table->dropIndex('idx_player_notif_read_date');
        });

        Schema::table('competitions_questions', function (Blueprint $table) {
            $table->dropIndex('idx_comp_questions_compound');
        });

        Schema::table('competition_player_answers', function (Blueprint $table) {
            $table->dropIndex('idx_answers_player_competition');
            $table->dropIndex('idx_answers_comp_correct');
            $table->dropIndex('idx_answers_time_player');
        });

        // Competition Registrations indexes already exist, no action needed

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('idx_questions_level_type');
            $table->dropIndex('idx_questions_date_level');
        });
    }
};
