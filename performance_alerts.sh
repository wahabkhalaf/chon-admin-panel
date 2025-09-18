#!/bin/bash

# Performance Alert System for PRODUCTION SERVER
# Run this to check for performance issues

ALERT_FILE="/var/log/performance_alerts_$(date '+%Y%m%d').log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

echo "=== Performance Alert Check - $TIMESTAMP ===" >> $ALERT_FILE

# Check for slow INSERTs
SLOW_INSERTS=$(sudo -u postgres psql -d chondb -t -c "
SELECT COUNT(*) 
FROM competition_leaderboards 
WHERE created_at >= NOW() - INTERVAL '5 minutes'
AND EXTRACT(EPOCH FROM (updated_at - created_at)) * 1000 > 1000;
" 2>/dev/null | tr -d ' ')

if [ "$SLOW_INSERTS" -gt 0 ] 2>/dev/null; then
    echo "�� ALERT: $SLOW_INSERTS slow INSERTs detected in last 5 minutes!" >> $ALERT_FILE
    echo "Time: $TIMESTAMP" >> $ALERT_FILE
    echo "Check performance immediately!" >> $ALERT_FILE
    echo "�� ALERT: $SLOW_INSERTS slow INSERTs detected in last 5 minutes!"
fi

# Check for high connection count
CONNECTIONS=$(sudo -u postgres psql -d chondb -t -c "
SELECT COUNT(*) FROM pg_stat_activity WHERE datname = current_database();
" 2>/dev/null | tr -d ' ')

if [ "$CONNECTIONS" -gt 100 ] 2>/dev/null; then
    echo "⚠️  WARNING: High connection count: $CONNECTIONS" >> $ALERT_FILE
    echo "⚠️  WARNING: High connection count: $CONNECTIONS"
fi

# Check batch function usage
BATCH_CALLS=$(sudo -u postgres psql -d chondb -t -c "
SELECT COUNT(*) FROM pg_stat_statements 
WHERE query LIKE '%batch_update_leaderboard_nodejs%' AND calls > 0;
" 2>/dev/null | tr -d ' ')

if [ "$BATCH_CALLS" -eq 0 ] 2>/dev/null; then
    echo "�� ALERT: No batch function calls detected!" >> $ALERT_FILE
    echo "Node.js may not be using the optimized batch function!" >> $ALERT_FILE
    echo "🚨 ALERT: No batch function calls detected!"
fi

# Check for long-running queries
LONG_QUERIES=$(sudo -u postgres psql -d chondb -t -c "
SELECT COUNT(*) FROM pg_stat_activity 
WHERE state != 'idle' 
AND now() - query_start > interval '30 seconds';
" 2>/dev/null | tr -d ' ')

if [ "$LONG_QUERIES" -gt 0 ] 2>/dev/null; then
    echo "⚠️  WARNING: $LONG_QUERIES long-running queries detected!" >> $ALERT_FILE
    echo "⚠️  WARNING: $LONG_QUERIES long-running queries detected!"
fi

echo "=== End of Alert Check - $TIMESTAMP ===" >> $ALERT_FILE
