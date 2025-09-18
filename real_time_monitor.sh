#!/bin/bash

# Real-Time Performance Monitor for PRODUCTION SERVER
# Run this during the game to see live performance

while true; do
    clear
    echo "=== REAL-TIME PERFORMANCE MONITOR - PRODUCTION ==="
    echo "Time: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "Server: $(hostname)"
    echo "=================================================="
    
    # Check recent INSERTs (last 2 minutes)
    echo "--- Recent INSERTs (Last 2 min) ---"
    sudo -u postgres psql -d chondb -c "
    SELECT 
        COUNT(*) as total_inserts,
        ROUND(AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) * 1000), 2) as avg_time_ms,
        COUNT(CASE WHEN EXTRACT(EPOCH FROM (updated_at - created_at)) * 1000 > 1000 THEN 1 END) as slow_inserts
    FROM competition_leaderboards 
    WHERE created_at >= NOW() - INTERVAL '2 minutes';
    "
    
    # Check batch function usage
    echo "--- Batch Function Usage ---"
    sudo -u postgres psql -d chondb -c "
    SELECT 
        COUNT(*) as total_calls,
        ROUND(AVG(mean_exec_time::numeric), 2) as avg_time_ms
    FROM pg_stat_statements 
    WHERE query LIKE '%batch_update_leaderboard_nodejs%'
    AND calls > 0;
    "
    
    # Check active connections
    echo "--- Active Connections ---"
    sudo -u postgres psql -d chondb -c "
    SELECT 
        count(*) as total_connections,
        count(*) FILTER (WHERE state = 'active') as active_connections
    FROM pg_stat_activity 
    WHERE datname = current_database();
    "
    
    # Check system load
    echo "--- System Load ---"
    uptime
    
    echo "=================================================="
    echo "Press Ctrl+C to stop monitoring"
    sleep 10
done
