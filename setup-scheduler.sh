#!/bin/bash

echo "🚀 Setting up Laravel Task Scheduler for Competition Notifications"
echo "================================================================"

# Get the current directory (project root)
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo "Project root: $PROJECT_ROOT"

# Create the cron job entry
CRON_ENTRY="* * * * * cd $PROJECT_ROOT && php artisan schedule:run >> /dev/null 2>&1"

echo ""
echo "📋 To enable automatic scheduled notifications, add this line to your crontab:"
echo ""
echo "$CRON_ENTRY"
echo ""
echo "🔧 To add it automatically, run:"
echo "crontab -e"
echo ""
echo "Then add the line above and save."
echo ""
echo "🧪 To test the scheduler manually, run:"
echo "cd $PROJECT_ROOT && php artisan schedule:run"
echo ""
echo "📊 To test scheduled notifications processing, run:"
echo "cd $PROJECT_ROOT && php artisan notifications:process-scheduled"
echo ""
echo "✅ Setup complete! The scheduler will now process scheduled notifications every minute."
