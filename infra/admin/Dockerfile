# ==========================================
# CHON Admin Panel - Laravel + Filament
# ==========================================
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    nodejs \
    npm \
    icu-dev \
    libzip-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd intl zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create required directories
RUN mkdir -p storage/app/firebase storage/logs bootstrap/cache

# Install PHP dependencies (skip scripts to avoid Firebase validation during build)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Install NPM dependencies and build assets
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy Nginx config
COPY infra/admin/nginx-default.conf /etc/nginx/http.d/default.conf

# Copy Supervisor config
COPY infra/admin/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY infra/admin/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 80

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
