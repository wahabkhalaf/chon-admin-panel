#!/bin/bash

echo "🚀 Starting Laravel Sail optimization..."

echo "📦 Clearing all caches..."
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan event:clear

echo "🎨 Clearing Filament cached components..."
./vendor/bin/sail artisan filament:clear-cached-components

# echo "🧪 Optimizing test environment..."
# ./vendor/bin/sail artisan test:clear
# ./vendor/bin/sail test --parallel --coverage

echo "⚙️ Optimizing application..."
./vendor/bin/sail artisan optimize
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan clear-compiled

echo "📝 Rebuilding caches..."
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan view:cache
# ./vendor/bin/sail artisan model:cache

echo "🔍 Caching Filament components..."
./vendor/bin/sail artisan filament:cache-components
./vendor/bin/sail artisan icons:cache

echo "🔄 Discovering and optimizing packages..."
./vendor/bin/sail artisan package:discover
./vendor/bin/sail composer dump-autoload -o

echo "🎯 Running final optimizations..."
./vendor/bin/sail artisan storage:link
./vendor/bin/sail artisan schedule:clear-cache

echo "✅ Optimization completed successfully!"
