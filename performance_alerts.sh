#!/bin/bash

# Performance Alert System
# Run this to check for performance issues

# Check for slow INSERTs
SLOW_INSERTS=$(sudo -u postgres psql -d chondb -t -c "
SELECT COUNT(*) 
FROM competition_leaderboards 
WHERE created_at >= NOW() - INTERVAL '5 minutes'
AND EXTRACT(EPOCH FROM (updated_at - created_at)) * 1000 > 1000;
")

if [ "$SLOW_INSERTS" -gt 0 ]; then
    echo "�� ALERT: $SLOW_INSERTS slow INSERTs detected in last 5 minutes!"
    echo "Time: $(date)"
    echo "Check performance immediately!"
fi

# Check for high connection count
CONNECTIONS=$(sudo -u postgres psql -d chondb -t -c "
SELECT COUNT(*) FROM pg_stat_activity WHERE datname = current_database();
")

if [ "$CONNECTIONS" -gt 100 ]; then
    echo "⚠️  WARNING: High connection count: $CONNECTIONS"
fi

# Check batch function usage
BATCH_CALLS=$(sudo -u postgres psql -d chondb -t -c "
SELECT COUNT(*) FROM pg_stat_statements 
WHERE query LIKE '%batch_update_leaderboard_nodejs%' AND calls > 0;
")

if [ "$BATCH_CALLS" -eq 0 ]; then
    echo "🚨 ALERT: No batch function calls detected!"
    echo "Node.js may not be using the optimized batch function!"
fi
