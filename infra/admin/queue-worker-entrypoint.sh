#!/bin/sh
set -e

echo "🚀 Starting CHON Queue Worker..."

# Ensure Firebase credentials directory exists
mkdir -p /var/www/html/storage/app/firebase

# Verify Firebase credentials exist
if [ ! -f /var/www/html/storage/app/firebase/firebase-service-account.json ]; then
    echo "⚠️  Firebase credentials not found, continuing without FCM support..."
else
    echo "✅ Firebase credentials loaded"
fi

# Wait for database to be ready
echo "⏳ Waiting for database..."
until php artisan db:show > /dev/null 2>&1; do
    echo "Database not ready, waiting..."
    sleep 2
done
echo "✅ Database ready"

# Set proper permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ Queue worker starting..."

# Run queue worker with all arguments passed to this script
exec su-exec www-data php artisan queue:work "$@"
