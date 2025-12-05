#!/bin/bash

# Laravel Docker Entrypoint Script

set -e

echo "Starting Laravel Docker Container..."

# Wait for database to be ready
echo "Waiting for database connection..."
until php artisan migrate:status > /dev/null 2>&1; do
  echo "Database not ready, waiting..."
  sleep 2
done

echo "Database is ready!"

# Generate application key if not exists
if [ ! -f "/var/www/html/.env" ] || ! grep -q "APP_KEY=" /var/www/html/.env || [ -z "$(grep '^APP_KEY=' /var/www/html/.env | cut -d'=' -f2)" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Generate JWT secret if not exists
if ! grep -q "JWT_SECRET=" /var/www/html/.env || [ -z "$(grep '^JWT_SECRET=' /var/www/html/.env | cut -d'=' -f2)" ]; then
    echo "Generating JWT secret..."
    php artisan jwt:secret --force
fi

# Clear and cache configurations
echo "Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Cache configurations for production
echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage symlink
echo "Creating storage symlink..."
php artisan storage:link

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

# Create log directories if they don't exist
mkdir -p /var/log/nginx
mkdir -p /var/log/supervisor
mkdir -p /var/lib/php/sessions
chown -R www-data:www-data /var/lib/php/sessions

echo "Starting supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf