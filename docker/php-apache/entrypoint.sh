#!/usr/bin/env bash
set -e

APP_DIR="/var/www/html"
cd "$APP_DIR"

# Create required Laravel dirs (volumes override image contents)
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache

# Ensure Laravel can write
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true
find storage bootstrap/cache -type d -exec chmod 775 {} \; || true

# Make sure sessions dir is usable
touch storage/framework/sessions/.keep || true

# Optional: clear caches
php artisan optimize:clear || true

# Start Supervisor (NEW)

echo "Starting Supervisor..."
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf


exec apache2ctl -D FOREGROUND