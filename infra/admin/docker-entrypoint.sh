#!/bin/sh
set -e

echo "🚀 Starting CHON Admin Panel..."

# Run package discovery (deferred from build)
echo "📦 Discovering packages..."
php artisan package:discover --ansi || true

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force || echo "⚠️  Migration failed, continuing..."

# Clear and cache config
echo "⚙️  Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ Initialization complete!"

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
