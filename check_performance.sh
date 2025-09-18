#!/bin/bash

# Simple Performance Check
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
LOG_FILE="./performance_check_$(date '+%Y%m%d_%H%M').log"

echo "=== Performance Check - $TIMESTAMP ===" > $LOG_FILE

# Check recent INSERTs
echo "--- Recent INSERTs (Last 5 min) ---" >> $LOG_FILE
./vendor/bin/sail psql -c "
SELECT 
    COUNT(*) as total_inserts,
    ROUND(AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) * 1000), 2) as avg_time_ms,
    COUNT(CASE WHEN EXTRACT(EPOCH FROM (updated_at - created_at)) * 1000 > 1000 THEN 1 END) as slow_inserts
FROM competition_leaderboards 
WHERE created_at >= NOW() - INTERVAL '5 minutes';
" >> $LOG_FILE 2>&1

# Check batch function usage
echo "--- Batch Function Usage ---" >> $LOG_FILE
./vendor/bin/sail psql -c "
SELECT 
    COUNT(*) as total_calls,
    ROUND(AVG(mean_exec_time::numeric), 2) as avg_time_ms
FROM pg_stat_statements 
WHERE query LIKE '%batch_update_leaderboard_nodejs%'
AND calls > 0;
" >> $LOG_FILE 2>&1

# Check slow queries
echo "--- Slow Queries ---" >> $LOG_FILE
./vendor/bin/sail psql -c "
SELECT 
    LEFT(query, 80) as query_snippet,
    calls,
    ROUND(mean_exec_time::numeric, 2) as avg_time_ms
FROM pg_stat_statements 
WHERE query LIKE '%INSERT INTO competition_leaderboards%'
ORDER BY mean_exec_time DESC 
LIMIT 3;
" >> $LOG_FILE 2>&1

echo "=== End of Check - $TIMESTAMP ===" >> $LOG_FILE

echo "Performance check completed at $TIMESTAMP"
echo "Log saved to: $LOG_FILE"
echo ""
echo "=== LOG CONTENT ==="
cat $LOG_FILE
