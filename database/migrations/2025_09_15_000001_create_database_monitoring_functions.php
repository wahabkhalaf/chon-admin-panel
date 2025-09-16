<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Disable automatic database transactions for this migration
     * Required for creating functions and views that may conflict with transactions
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     * Create database monitoring functions and views for performance tracking
     */
    public function up(): void
    {
        // ============================================================================
        // PERFORMANCE MONITORING VIEWS
        // ============================================================================

        // Create view for slow query monitoring (compatible with different pg_stat_statements versions)
        try {
            DB::statement('
                CREATE OR REPLACE VIEW slow_queries_report AS
                SELECT 
                    query,
                    calls,
                    COALESCE(total_exec_time, total_time) as total_time,
                    COALESCE(mean_exec_time, mean_time) as mean_time,
                    COALESCE(max_exec_time, max_time) as max_time,
                    rows,
                    100.0 * shared_blks_hit / nullif(shared_blks_hit + shared_blks_read, 0) AS hit_percent
                FROM pg_stat_statements 
                WHERE COALESCE(mean_exec_time, mean_time) > 100  -- queries taking more than 100ms on average
                ORDER BY COALESCE(mean_exec_time, mean_time) DESC 
                LIMIT 20
            ');
        } catch (\Exception $e) {
            \Log::warning('Could not create slow_queries_report view - pg_stat_statements may not be available', [
                'error' => $e->getMessage()
            ]);
        }

        // Create view for index usage monitoring
        DB::statement('
            CREATE OR REPLACE VIEW index_usage_report AS
            SELECT 
                schemaname,
                relname as tablename,
                indexrelname as indexname,
                idx_tup_read,
                idx_tup_fetch,
                idx_scan,
                CASE 
                    WHEN idx_scan = 0 THEN 0
                    ELSE ROUND(100.0 * idx_tup_fetch / idx_scan, 2)
                END as avg_tuples_per_scan
            FROM pg_stat_user_indexes
            ORDER BY idx_scan DESC
        ');

        // Create view for table scan vs index scan ratio
        DB::statement('
            CREATE OR REPLACE VIEW table_scan_report AS
            SELECT 
                schemaname,
                relname as tablename,
                seq_scan,
                seq_tup_read,
                idx_scan,
                idx_tup_fetch,
                CASE 
                    WHEN seq_scan + idx_scan = 0 THEN 0
                    ELSE ROUND(100.0 * idx_scan / (seq_scan + idx_scan), 2)
                END as index_scan_percentage,
                CASE 
                    WHEN seq_scan + idx_scan = 0 THEN 0
                    ELSE ROUND(100.0 * seq_scan / (seq_scan + idx_scan), 2)
                END as seq_scan_percentage
            FROM pg_stat_user_tables
            WHERE schemaname = \'public\'
            ORDER BY seq_tup_read DESC
        ');

        // Create view for missing indexes on foreign keys
        DB::statement('
            CREATE OR REPLACE VIEW missing_foreign_key_indexes AS
            SELECT 
                c.conname AS constraint_name,
                t.relname AS table_name,
                ARRAY_AGG(a.attname ORDER BY a.attnum) AS columns
            FROM pg_constraint c
            JOIN pg_class t ON c.conrelid = t.oid
            JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(c.conkey)
            WHERE c.contype = \'f\'
            AND NOT EXISTS (
                SELECT 1 FROM pg_index i
                WHERE i.indrelid = c.conrelid
                AND c.conkey[1:array_length(c.conkey,1)] @> i.indkey[0:array_length(c.conkey,1)-1]
            )
            GROUP BY c.conname, t.relname
            ORDER BY t.relname
        ');

        // Create view for competition performance metrics
        DB::statement('
            CREATE OR REPLACE VIEW competition_performance_metrics AS
            SELECT 
                c.id as competition_id,
                c.name as competition_name,
                c.start_time,
                c.end_time,
                COUNT(DISTINCT cpa.player_id) as total_participants,
                COUNT(cpa.id) as total_answers,
                COUNT(CASE WHEN cpa.is_correct = true THEN 1 END) as correct_answers,
                ROUND(
                    100.0 * COUNT(CASE WHEN cpa.is_correct = true THEN 1 END) / COUNT(cpa.id), 
                    2
                ) as correct_answer_percentage,
                AVG(EXTRACT(EPOCH FROM (cpa.answered_at - cpa.created_at))) as avg_response_time_seconds
            FROM competitions c
            LEFT JOIN competition_player_answers cpa ON c.id = cpa.competition_id
            GROUP BY c.id, c.name, c.start_time, c.end_time
            ORDER BY c.start_time DESC
        ');

        // ============================================================================
        // PERFORMANCE MONITORING FUNCTIONS
        // ============================================================================

        // Function to get database performance summary
        DB::statement('
            CREATE OR REPLACE FUNCTION get_database_performance_summary()
            RETURNS TABLE (
                metric_name TEXT,
                metric_value TEXT,
                description TEXT
            ) AS $$
            BEGIN
                RETURN QUERY
                SELECT 
                    \'Total Connections\'::TEXT,
                    (SELECT count(*)::TEXT FROM pg_stat_activity)::TEXT,
                    \'Current active database connections\'::TEXT
                UNION ALL
                SELECT 
                    \'Cache Hit Ratio\'::TEXT,
                    (SELECT ROUND(100.0 * sum(blks_hit) / (sum(blks_hit) + sum(blks_read)), 2)::TEXT || \'%\' 
                     FROM pg_stat_database WHERE datname = current_database())::TEXT,
                    \'Percentage of database reads served from cache\'::TEXT
                UNION ALL
                SELECT 
                    \'Slow Queries (>100ms)\'::TEXT,
                    (SELECT count(*)::TEXT FROM pg_stat_statements WHERE mean_time > 100)::TEXT,
                    \'Number of queries with average execution time > 100ms\'::TEXT
                UNION ALL
                SELECT 
                    \'Total Transactions\'::TEXT,
                    (SELECT (xact_commit + xact_rollback)::TEXT FROM pg_stat_database WHERE datname = current_database())::TEXT,
                    \'Total committed and rolled back transactions\'::TEXT
                UNION ALL
                SELECT 
                    \'Database Size\'::TEXT,
                    (SELECT pg_size_pretty(pg_database_size(current_database())))::TEXT,
                    \'Current database size on disk\'::TEXT;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Function to refresh materialized view safely
        DB::statement('
            CREATE OR REPLACE FUNCTION refresh_competition_stats()
            RETURNS BOOLEAN AS $$
            BEGIN
                -- Refresh the materialized view concurrently if possible
                BEGIN
                    REFRESH MATERIALIZED VIEW CONCURRENTLY competition_player_stats;
                    RETURN TRUE;
                EXCEPTION WHEN OTHERS THEN
                    -- If concurrent refresh fails, do regular refresh
                    REFRESH MATERIALIZED VIEW competition_player_stats;
                    RETURN TRUE;
                END;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Function to analyze all critical tables
        DB::statement('
            CREATE OR REPLACE FUNCTION analyze_critical_tables()
            RETURNS TEXT AS $$
            DECLARE
                table_name TEXT;
                tables TEXT[] := ARRAY[
                    \'competitions\', \'competition_leaderboards\', \'competition_player_answers\',
                    \'competition_registrations\', \'players\', \'transactions\', 
                    \'player_notifications\', \'notifications\', \'questions\', \'competitions_questions\'
                ];
                result TEXT := \'\';
            BEGIN
                FOREACH table_name IN ARRAY tables
                LOOP
                    EXECUTE \'ANALYZE \' || table_name;
                    result := result || table_name || \' analyzed; \';
                END LOOP;
                
                RETURN \'Successfully analyzed tables: \' || result;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Function to get table sizes and row counts
        DB::statement('
            CREATE OR REPLACE FUNCTION get_table_stats()
            RETURNS TABLE (
                table_name TEXT,
                row_count BIGINT,
                table_size TEXT,
                index_size TEXT,
                total_size TEXT
            ) AS $$
            BEGIN
                RETURN QUERY
                SELECT 
                    t.table_name::TEXT,
                    (SELECT reltuples::BIGINT FROM pg_class WHERE relname = t.table_name),
                    pg_size_pretty(pg_total_relation_size(t.table_name::regclass) - pg_indexes_size(t.table_name::regclass)),
                    pg_size_pretty(pg_indexes_size(t.table_name::regclass)),
                    pg_size_pretty(pg_total_relation_size(t.table_name::regclass))
                FROM information_schema.tables t
                WHERE t.table_schema = \'public\'
                AND t.table_type = \'BASE TABLE\'
                AND t.table_name IN (
                    \'competitions\', \'competition_leaderboards\', \'competition_player_answers\',
                    \'competition_registrations\', \'players\', \'transactions\', 
                    \'player_notifications\', \'notifications\', \'questions\', \'competitions_questions\'
                )
                ORDER BY pg_total_relation_size(t.table_name::regclass) DESC;
            END;
            $$ LANGUAGE plpgsql;
        ');

        \Log::info('Database monitoring functions and views created successfully', [
            'migration' => '2025_01_15_000001_create_database_monitoring_functions',
            'views_created' => [
                'slow_queries_report',
                'index_usage_report',
                'table_scan_report',
                'missing_foreign_key_indexes',
                'competition_performance_metrics'
            ],
            'functions_created' => [
                'get_database_performance_summary',
                'refresh_competition_stats',
                'analyze_critical_tables',
                'get_table_stats'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop functions
        DB::statement('DROP FUNCTION IF EXISTS get_database_performance_summary()');
        DB::statement('DROP FUNCTION IF EXISTS refresh_competition_stats()');
        DB::statement('DROP FUNCTION IF EXISTS analyze_critical_tables()');
        DB::statement('DROP FUNCTION IF EXISTS get_table_stats()');

        // Drop views
        DB::statement('DROP VIEW IF EXISTS slow_queries_report');
        DB::statement('DROP VIEW IF EXISTS index_usage_report');
        DB::statement('DROP VIEW IF EXISTS table_scan_report');
        DB::statement('DROP VIEW IF EXISTS missing_foreign_key_indexes');
        DB::statement('DROP VIEW IF EXISTS competition_performance_metrics');

        \Log::info('Database monitoring functions and views dropped', [
            'migration_rollback' => '2025_01_15_000001_create_database_monitoring_functions'
        ]);
    }
};
