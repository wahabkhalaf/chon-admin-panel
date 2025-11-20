#!/bin/sh
set -e

echo "🚀 Starting CHON Admin Panel..."

# Ensure Firebase credentials directory exists
mkdir -p /var/www/html/storage/app/firebase

# Create placeholder if Firebase credentials don't exist
if [ ! -f /var/www/html/storage/app/firebase/firebase-service-account.json ]; then
    echo "⚠️  Firebase credentials not found, creating placeholder..."
    echo '{"type":"service_account","project_id":"placeholder","client_email":"placeholder@placeholder.iam.gserviceaccount.com","private_key":"-----BEGIN PRIVATE KEY-----\nplaceholder\n-----END PRIVATE KEY-----\n"}' > /var/www/html/storage/app/firebase/firebase-service-account.json
fi

# Run package discovery (deferred from build)
echo "📦 Discovering packages..."
php artisan package:discover --ansi || echo "⚠️  Package discovery failed, continuing..."

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force || echo "⚠️  Migration failed, continuing..."

# Clear and cache config
echo "⚙️  Optimizing application..."
php artisan config:cache || echo "⚠️  Config cache failed"
php artisan route:cache || echo "⚠️  Route cache failed"
php artisan view:cache || echo "⚠️  View cache failed"

# Set proper permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ Initialization complete!"

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
