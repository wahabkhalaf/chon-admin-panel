#!/bin/bash

echo "=== Performance Log Viewer ==="
echo "Log directory: /tmp/performance_logs"
echo ""

# List all log files
echo "Available log files:"
ls -la /tmp/performance_logs/performance_*.log 2>/dev/null || echo "No log files found"

echo ""
echo "1. View latest check log"
echo "2. View latest monitor log" 
echo "3. View latest alerts log"
echo "4. View all logs"
echo "5. Search logs"
echo "6. Real-time monitoring"
echo "7. Clean old logs"
echo ""

read -p "Choose option (1-7): " choice

case $choice in
    1)
        echo "=== Latest Check Log ==="
        ls -t /tmp/performance_logs/performance_check_*.log 2>/dev/null | head -1 | xargs cat
        ;;
    2)
        echo "=== Latest Monitor Log ==="
        ls -t /tmp/performance_logs/performance_monitor_*.log 2>/dev/null | head -1 | xargs cat
        ;;
    3)
        echo "=== Latest Alerts Log ==="
        ls -t /tmp/performance_logs/performance_alerts_*.log 2>/dev/null | head -1 | xargs cat
        ;;
    4)
        echo "=== All Logs ==="
        cat /tmp/performance_logs/performance_*.log 2>/dev/null
        ;;
    5)
        read -p "Enter search term: " search_term
        grep -i "$search_term" /tmp/performance_logs/performance_*.log 2>/dev/null
        ;;
    6)
        echo "=== Real-time Monitoring ==="
        tail -f $(ls -t /tmp/performance_logs/performance_*.log 2>/dev/null | head -1)
        ;;
    7)
        echo "=== Cleaning Old Logs ==="
        find /tmp/performance_logs -name "performance_*.log" -mtime +7 -delete
        echo "Old logs cleaned (kept last 7 days)"
        ;;
    *)
        echo "Invalid option"
        ;;
esac
