#!/usr/bin/env bash
set -e

APP_DIR="/var/www/html"
cd "$APP_DIR"

# Create required Laravel dirs (volumes override image contents)
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache

# Fix the annoying /var/www/storage mismatch if anything points there
mkdir -p /var/www || true
if [ ! -e /var/www/storage ]; then
  ln -s "$APP_DIR/storage" /var/www/storage || true
fi

# Ensure Laravel can write
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true
find storage bootstrap/cache -type d -exec chmod 775 {} \; || true

# Make sure sessions dir is usable
touch storage/framework/sessions/.keep || true

# Optional: clear caches
php artisan optimize:clear || true

# Run CMD (supervisord)
exec "$@"
