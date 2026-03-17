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
    public/assets/media \
    public/articles/thumbnails

php artisan storage:link || true
php artisan migrate --force
php artisan optimize:clear
php artisan livewire:publish --assets --silent || true
php artisan optimize

chown -R www-data:www-data public storage bootstrap/cache || true

find public -type d -exec chmod 775 {} \;
find public -type f -exec chmod 664 {} \;
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type d -exec chmod 775 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;
