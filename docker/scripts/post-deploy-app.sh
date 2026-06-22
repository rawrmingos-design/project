#!/bin/sh

set -eu

mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    public/assets/product_logo \
    public/assets/thumbnail \
    public/assets/banner \
    public/assets/banner_game \
    public/assets/logo \
    public/assets/seasonal \
    public/assets/media \
    public/assets/optimized \
    public/articles/thumbnails

php artisan storage:link || true
php artisan migrate --force
php artisan optimize:clear
php artisan livewire:publish --assets --silent || true
php artisan optimize

APP_PATH="${APP_PATH:-/var/www/html}"

# Repair ownership/permission after image update and bind-mount asset changes.
chown -R www-data:www-data "$APP_PATH" || true
find "$APP_PATH" -type d -exec chmod 755 {} + || true
find "$APP_PATH" -type f -exec chmod 644 {} + || true
chmod -R 775 "$APP_PATH/storage" "$APP_PATH/bootstrap/cache" || true
chmod -R 775 "$APP_PATH/public/assets" "$APP_PATH/public/articles" || true
chown -R www-data:www-data "$APP_PATH/public/assets" "$APP_PATH/public/articles" || true
