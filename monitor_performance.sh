#!/bin/bash

# Performance Monitoring Script for Competition Platform - PRODUCTION SERVER
# Run this during high-load periods (9 PM onwards)

TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
LOG_FILE="./performance_monitor_$(date '+%Y%m%d').log"

echo "=== Performance Monitor - $TIMESTAMP ===" >> $LOG_FILE

# 1. Check current slow queries
echo "--- Slow Queries Check ---" >> $LOG_FILE
sudo -u postgres psql -d chondb -c "
SELECT 
    LEFT(query, 100) as query_snippet,
    calls,
    ROUND(mean_exec_time::numeric, 2) as avg_time_ms
FROM pg_stat_statements 
WHERE query LIKE '%INSERT INTO competition_leaderboards%'
ORDER BY mean_exec_time DESC 
LIMIT 5;
" >> $LOG_FILE 2>&1

# 2. Check batch function usage
echo "--- Batch Function Usage ---" >> $LOG_FILE
sudo -u postgres psql -d chondb -c "
SELECT 
    'Batch Function Calls' as metric,
    COUNT(*) as total_calls,
    ROUND(AVG(mean_exec_time::numeric), 2) as avg_time_ms
FROM pg_stat_statements 
WHERE query LIKE '%batch_update_leaderboard_nodejs%'
AND calls > 0;
" >> $LOG_FILE 2>&1

# 3. Check recent activity (last 5 minutes)
echo "--- Recent Activity (Last 5 min) ---" >> $LOG_FILE
sudo -u postgres psql -d chondb -c "
SELECT 
    'Recent Activity' as period,
    COUNT(*) as total_inserts,
    ROUND(AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) * 1000), 2) as avg_insert_time_ms,
    COUNT(CASE WHEN EXTRACT(EPOCH FROM (updated_at - created_at)) * 1000 > 1000 THEN 1 END) as slow_inserts
FROM competition_leaderboards 
WHERE created_at >= NOW() - INTERVAL '5 minutes';
" >> $LOG_FILE 2>&1

# 4. Check active connections
echo "--- Active Connections ---" >> $LOG_FILE
sudo -u postgres psql -d chondb -c "
SELECT 
    count(*) as total_connections,
    count(*) FILTER (WHERE state = 'active') as active_connections,
    count(*) FILTER (WHERE state = 'idle') as idle_connections
FROM pg_stat_activity 
WHERE datname = current_database();
" >> $LOG_FILE 2>&1

# 5. Check for blocking locks
echo "--- Blocking Locks ---" >> $LOG_FILE
sudo -u postgres psql -d chondb -c "
SELECT 
    l.locktype,
    l.relation::regclass as table_name,
    l.mode,
    l.granted,
    a.pid,
    now() - a.query_start as duration
FROM pg_locks l
JOIN pg_stat_activity a ON l.pid = a.pid
WHERE l.relation::regclass::text = 'competition_leaderboards'
AND NOT l.granted;
" >> $LOG_FILE 2>&1

# 6. Check system load
echo "--- System Load ---" >> $LOG_FILE
uptime >> $LOG_FILE
free -h >> $LOG_FILE

echo "=== End of Monitor - $TIMESTAMP ===" >> $LOG_FILE
echo "" >> $LOG_FILE

# Display current status
echo "Performance check completed at $TIMESTAMP"
echo "Log saved to: $LOG_FILE"
