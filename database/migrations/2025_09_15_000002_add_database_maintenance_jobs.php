<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Disable automatic database transactions for this migration
     * Required for creating functions and procedures that may conflict with transactions
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     * Create automated database maintenance procedures
     */
    public function up(): void
    {
        // ============================================================================
        // AUTOMATED MAINTENANCE FUNCTIONS
        // ============================================================================

        // Function to perform routine database maintenance (ANALYZE only - VACUUM must be run separately)
        DB::statement('
            CREATE OR REPLACE FUNCTION perform_database_maintenance()
            RETURNS TEXT AS $$
            DECLARE
                maintenance_log TEXT := \'\';
                table_name TEXT;
                tables TEXT[] := ARRAY[
                    \'competitions\', \'competition_leaderboards\', \'competition_player_answers\',
                    \'competition_registrations\', \'players\', \'transactions\', 
                    \'player_notifications\', \'notifications\', \'questions\', \'competitions_questions\'
                ];
            BEGIN
                maintenance_log := \'Database maintenance started at \' || NOW() || \'; \';
                
                -- Analyze critical tables (VACUUM must be run separately outside function)
                FOREACH table_name IN ARRAY tables
                LOOP
                    EXECUTE \'ANALYZE \' || table_name;
                    maintenance_log := maintenance_log || table_name || \' analyzed; \';
                END LOOP;
                
                -- Refresh materialized view if it exists
                IF EXISTS (SELECT 1 FROM pg_matviews WHERE matviewname = \'competition_player_stats\') THEN
                    PERFORM refresh_competition_stats();
                    maintenance_log := maintenance_log || \'competition_player_stats refreshed; \';
                END IF;
                
                -- Update statistics
                EXECUTE \'UPDATE pg_class SET reltuples = -1 WHERE relname = ANY($1)\' USING tables;
                maintenance_log := maintenance_log || \'table statistics updated; \';
                
                -- Log completion
                maintenance_log := maintenance_log || \'maintenance completed at \' || NOW();
                
                RETURN maintenance_log;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Function to clean up old data (configurable retention periods)
        DB::statement('
            CREATE OR REPLACE FUNCTION cleanup_old_data(
                notification_retention_days INTEGER DEFAULT 90,
                player_notification_retention_days INTEGER DEFAULT 30,
                transaction_log_retention_days INTEGER DEFAULT 365
            )
            RETURNS TEXT AS $$
            DECLARE
                cleanup_log TEXT := \'\';
                deleted_count INTEGER;
            BEGIN
                cleanup_log := \'Data cleanup started at \' || NOW() || \'; \';
                
                -- Clean up old notifications (keep only recent ones)
                DELETE FROM notifications 
                WHERE created_at < NOW() - INTERVAL \'1 day\' * notification_retention_days
                AND status IN (\'sent\', \'failed\');
                
                GET DIAGNOSTICS deleted_count = ROW_COUNT;
                cleanup_log := cleanup_log || deleted_count || \' old notifications deleted; \';
                
                -- Clean up old player notifications that have been read
                DELETE FROM player_notifications 
                WHERE read_at IS NOT NULL 
                AND read_at < NOW() - INTERVAL \'1 day\' * player_notification_retention_days;
                
                GET DIAGNOSTICS deleted_count = ROW_COUNT;
                cleanup_log := cleanup_log || deleted_count || \' old player notifications deleted; \';
                
                -- Clean up old transaction logs (if table exists)
                IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = \'transaction_logs\') THEN
                    EXECUTE \'DELETE FROM transaction_logs WHERE created_at < NOW() - INTERVAL \'\'1 day\'\' * \' || transaction_log_retention_days;
                    GET DIAGNOSTICS deleted_count = ROW_COUNT;
                    cleanup_log := cleanup_log || deleted_count || \' old transaction logs deleted; \';
                END IF;
                
                cleanup_log := cleanup_log || \'cleanup completed at \' || NOW();
                
                RETURN cleanup_log;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Function to check and report database health
        DB::statement('
            CREATE OR REPLACE FUNCTION check_database_health()
            RETURNS TABLE (
                check_name TEXT,
                status TEXT,
                details TEXT,
                recommendation TEXT
            ) AS $$
            BEGIN
                -- Check cache hit ratio
                RETURN QUERY
                SELECT 
                    \'Cache Hit Ratio\'::TEXT,
                    CASE 
                        WHEN (SELECT ROUND(100.0 * sum(blks_hit) / (sum(blks_hit) + sum(blks_read)), 2) 
                              FROM pg_stat_database WHERE datname = current_database()) > 95 
                        THEN \'GOOD\'::TEXT
                        ELSE \'WARNING\'::TEXT
                    END,
                    (SELECT ROUND(100.0 * sum(blks_hit) / (sum(blks_hit) + sum(blks_read)), 2)::TEXT || \'%\' 
                     FROM pg_stat_database WHERE datname = current_database())::TEXT,
                    \'Should be > 95%. Consider increasing shared_buffers if low.\'::TEXT;
                
                -- Check for slow queries (only if pg_stat_statements is available)
                IF EXISTS (SELECT 1 FROM pg_extension WHERE extname = \'pg_stat_statements\') THEN
                    RETURN QUERY
                    SELECT 
                        \'Slow Queries\'::TEXT,
                        CASE 
                            WHEN (SELECT count(*) FROM pg_stat_statements WHERE COALESCE(mean_exec_time, mean_time, 0) > 1000) > 10 
                            THEN \'WARNING\'::TEXT
                            WHEN (SELECT count(*) FROM pg_stat_statements WHERE COALESCE(mean_exec_time, mean_time, 0) > 1000) > 0 
                            THEN \'ATTENTION\'::TEXT
                            ELSE \'GOOD\'::TEXT
                        END,
                        (SELECT count(*)::TEXT || \' queries with mean time > 1s\' FROM pg_stat_statements WHERE COALESCE(mean_exec_time, mean_time, 0) > 1000)::TEXT,
                        \'Review and optimize slow queries. Consider adding indexes.\'::TEXT;
                ELSE
                    RETURN QUERY
                    SELECT 
                        \'Slow Queries\'::TEXT,
                        \'INFO\'::TEXT,
                        \'pg_stat_statements extension not enabled\'::TEXT,
                        \'Enable pg_stat_statements in postgresql.conf for query monitoring.\'::TEXT;
                END IF;
                
                -- Check connection count
                RETURN QUERY
                SELECT 
                    \'Connection Count\'::TEXT,
                    CASE 
                        WHEN (SELECT count(*) FROM pg_stat_activity) > 400 
                        THEN \'WARNING\'::TEXT
                        WHEN (SELECT count(*) FROM pg_stat_activity) > 200 
                        THEN \'ATTENTION\'::TEXT
                        ELSE \'GOOD\'::TEXT
                    END,
                    (SELECT count(*)::TEXT || \' active connections\' FROM pg_stat_activity)::TEXT,
                    \'Monitor connection pooling. Consider connection limits.\'::TEXT;
                
                -- Check table bloat (simplified check)
                RETURN QUERY
                SELECT 
                    \'Table Maintenance\'::TEXT,
                    CASE 
                        WHEN (SELECT count(*) FROM pg_stat_user_tables WHERE n_dead_tup > n_live_tup * 0.1) > 5 
                        THEN \'WARNING\'::TEXT
                        WHEN (SELECT count(*) FROM pg_stat_user_tables WHERE n_dead_tup > n_live_tup * 0.05) > 0 
                        THEN \'ATTENTION\'::TEXT
                        ELSE \'GOOD\'::TEXT
                    END,
                    (SELECT count(*)::TEXT || \' tables need maintenance\' FROM pg_stat_user_tables WHERE n_dead_tup > n_live_tup * 0.1)::TEXT,
                    \'Run VACUUM ANALYZE on tables with high dead tuple ratio.\'::TEXT;
                
                -- Check unused indexes
                RETURN QUERY
                SELECT 
                    \'Index Usage\'::TEXT,
                    CASE 
                        WHEN (SELECT count(*) FROM pg_stat_user_indexes WHERE idx_scan = 0) > 10 
                        THEN \'WARNING\'::TEXT
                        WHEN (SELECT count(*) FROM pg_stat_user_indexes WHERE idx_scan = 0) > 0 
                        THEN \'ATTENTION\'::TEXT
                        ELSE \'GOOD\'::TEXT
                    END,
                    (SELECT count(*)::TEXT || \' unused indexes found\' FROM pg_stat_user_indexes WHERE idx_scan = 0)::TEXT,
                    \'Consider dropping unused indexes to improve write performance.\'::TEXT;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Function to get competition statistics for monitoring
        DB::statement('
            CREATE OR REPLACE FUNCTION get_competition_load_stats()
            RETURNS TABLE (
                metric TEXT,
                current_value BIGINT,
                description TEXT
            ) AS $$
            BEGIN
                RETURN QUERY
                SELECT 
                    \'Active Competitions\'::TEXT,
                    (SELECT count(*)::BIGINT FROM competitions WHERE start_time <= NOW() AND end_time >= NOW()),
                    \'Competitions currently running\'::TEXT
                UNION ALL
                SELECT 
                    \'Total Players Online\'::TEXT,
                    (SELECT count(DISTINCT player_id)::BIGINT 
                     FROM competition_player_answers 
                     WHERE answered_at > NOW() - INTERVAL \'5 minutes\'),
                    \'Players active in last 5 minutes\'::TEXT
                UNION ALL
                SELECT 
                    \'Answers Per Minute\'::TEXT,
                    (SELECT count(*)::BIGINT 
                     FROM competition_player_answers 
                     WHERE answered_at > NOW() - INTERVAL \'1 minute\'),
                    \'Total answers submitted in last minute\'::TEXT
                UNION ALL
                SELECT 
                    \'Registrations Today\'::TEXT,
                    (SELECT count(*)::BIGINT 
                     FROM competition_registrations 
                     WHERE created_at > CURRENT_DATE),
                    \'New registrations today\'::TEXT;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Create a simple logging table for maintenance activities
        DB::statement('
            CREATE TABLE IF NOT EXISTS database_maintenance_log (
                id SERIAL PRIMARY KEY,
                maintenance_type VARCHAR(100) NOT NULL,
                details TEXT,
                execution_time INTERVAL,
                created_at TIMESTAMP DEFAULT NOW()
            )
        ');

        // Function to log maintenance activities
        DB::statement('
            CREATE OR REPLACE FUNCTION log_maintenance_activity(
                activity_type TEXT,
                activity_details TEXT DEFAULT NULL,
                start_time TIMESTAMP DEFAULT NOW()
            )
            RETURNS VOID AS $$
            BEGIN
                INSERT INTO database_maintenance_log (maintenance_type, details, execution_time, created_at)
                VALUES (activity_type, activity_details, NOW() - start_time, NOW());
            END;
            $$ LANGUAGE plpgsql;
        ');

        \Log::info('Database maintenance functions created successfully', [
            'migration' => '2025_01_15_000002_add_database_maintenance_jobs',
            'functions_created' => [
                'perform_database_maintenance',
                'cleanup_old_data',
                'check_database_health',
                'get_competition_load_stats',
                'log_maintenance_activity'
            ],
            'table_created' => 'database_maintenance_log'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop maintenance functions
        DB::statement('DROP FUNCTION IF EXISTS perform_database_maintenance()');
        DB::statement('DROP FUNCTION IF EXISTS cleanup_old_data(INTEGER, INTEGER, INTEGER)');
        DB::statement('DROP FUNCTION IF EXISTS check_database_health()');
        DB::statement('DROP FUNCTION IF EXISTS get_competition_load_stats()');
        DB::statement('DROP FUNCTION IF EXISTS log_maintenance_activity(TEXT, TEXT, TIMESTAMP)');

        // Drop maintenance log table
        DB::statement('DROP TABLE IF EXISTS database_maintenance_log');

        \Log::info('Database maintenance functions and tables dropped', [
            'migration_rollback' => '2025_01_15_000002_add_database_maintenance_jobs'
        ]);
    }
};
