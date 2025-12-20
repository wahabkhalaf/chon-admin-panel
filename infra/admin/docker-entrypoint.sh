#!/bin/sh
set -e

echo "🚀 Starting CHON Admin Panel..."

# Ensure Firebase credentials directory exists
mkdir -p /var/www/html/storage/app/firebase

# Verify Firebase credentials exist (should be mounted as volume)
if [ ! -f /var/www/html/storage/app/firebase/firebase-service-account.json ]; then
    echo "❌ ERROR: Firebase credentials not found!"
    echo "Make sure docker-compose.yml mounts the Firebase credentials file:"
    echo "  - /opt/chon/firebase-service-account.json:/var/www/html/storage/app/firebase/firebase-service-account.json:ro"
    # Don't exit - allow container to start for debugging
else
    echo "✅ Firebase credentials loaded"
    # Validate that the credentials file contains a valid private key
    if ! grep -q '"private_key".*BEGIN PRIVATE KEY' /var/www/html/storage/app/firebase/firebase-service-account.json; then
        echo "⚠️  WARNING: Firebase credentials may be invalid (no valid private key found)"
    fi
fi

# Run package discovery (deferred from build)
echo "📦 Discovering packages..."
php artisan package:discover --ansi || echo "⚠️  Package discovery failed, continuing..."

# Clear and cache config
echo "⚙️  Optimizing application..."
php artisan config:cache || echo "⚠️  Config cache failed"
php artisan route:cache || echo "⚠️  Route cache failed"
php artisan view:cache || echo "⚠️  View cache failed"

# Set proper permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Create supervisor log directory
mkdir -p /var/log/supervisor

echo "✅ Initialization complete!"

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
