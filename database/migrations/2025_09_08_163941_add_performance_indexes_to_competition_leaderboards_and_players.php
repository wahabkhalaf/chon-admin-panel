<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to competition_leaderboards table
        Schema::table('competition_leaderboards', function (Blueprint $table) {
            // Index for competition_id and player_id queries (if not already exists as unique constraint)
            $table->index(['competition_id', 'player_id'], 'idx_competition_leaderboards_competition_player');

            // Index for score-based queries (competition_id, score DESC, updated_at ASC)
            $table->index(['competition_id', 'score', 'updated_at'], 'idx_competition_leaderboards_score_desc');
        });

        // Add indexes to players table
        Schema::table('players', function (Blueprint $table) {
            // Index for total_score updates (id, total_score)
            $table->index(['id', 'total_score'], 'idx_players_total_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from competition_leaderboards table
        Schema::table('competition_leaderboards', function (Blueprint $table) {
            $table->dropIndex('idx_competition_leaderboards_competition_player');
            $table->dropIndex('idx_competition_leaderboards_score_desc');
        });

        // Drop indexes from players table
        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex('idx_players_total_score');
        });
    }
};
