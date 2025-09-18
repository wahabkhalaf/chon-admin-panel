#!/bin/bash

echo "=== Performance Log Cleanup ==="
echo "Log directory: /tmp/performance_logs"
echo ""

# Show current log files
echo "Current log files:"
ls -la /tmp/performance_logs/performance_*.log 2>/dev/null || echo "No log files found"

echo ""
echo "1. Clean logs older than 3 days"
echo "2. Clean logs older than 7 days"
echo "3. Clean logs older than 30 days"
echo "4. Clean all logs"
echo "5. Show log sizes"
echo ""

read -p "Choose option (1-5): " choice

case $choice in
    1)
        echo "Cleaning logs older than 3 days..."
        find /tmp/performance_logs -name "performance_*.log" -mtime +3 -delete
        echo "Done!"
        ;;
    2)
        echo "Cleaning logs older than 7 days..."
        find /tmp/performance_logs -name "performance_*.log" -mtime +7 -delete
        echo "Done!"
        ;;
    3)
        echo "Cleaning logs older than 30 days..."
        find /tmp/performance_logs -name "performance_*.log" -mtime +30 -delete
        echo "Done!"
        ;;
    4)
        echo "Cleaning all logs..."
        rm -f /tmp/performance_logs/performance_*.log
        echo "Done!"
        ;;
    5)
        echo "Log file sizes:"
        du -h /tmp/performance_logs/performance_*.log 2>/dev/null || echo "No log files found"
        ;;
    *)
        echo "Invalid option"
        ;;
esac
