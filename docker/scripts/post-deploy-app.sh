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

# Sync bot order master switch: if BOT_ORDER_ENABLED=true, force-enable
# bot_order_wa_enabled in DB so the WhatsApp order bot is active after every
# deploy (idempotent; never disables an admin's manual choice).
# Reads the flag from the mounted Laravel .env directly (env() is unreliable
# under config cache), wrapped with || true so a hiccup never blocks deploy.
if grep -qE '^BOT_ORDER_ENABLED=true' /var/www/html/.env 2>/dev/null; then
    cat > /tmp/bot-order-sync.php <<'PHPEOF'
<?php
$s = App\Models\SettingWeb::first() ?? new App\Models\SettingWeb();
$s->bot_order_wa_enabled = true;
$s->save();
echo 'bot_order_wa_enabled forced to true' . PHP_EOL;
PHPEOF
    php artisan tinker /tmp/bot-order-sync.php \
        || echo "WARN: bot_order sync via tinker gagal (non-fatal)"
    rm -f /tmp/bot-order-sync.php
else
    echo "BOT_ORDER_ENABLED != true in .env — bot order left as-is"
fi

APP_PATH="${APP_PATH:-/var/www/html}"

# Repair ownership/permission after image update and bind-mount asset changes.
chown -R www-data:www-data "$APP_PATH" || true
find "$APP_PATH" -type d -exec chmod 755 {} + || true
find "$APP_PATH" -type f -exec chmod 644 {} + || true
chmod -R 775 "$APP_PATH/storage" "$APP_PATH/bootstrap/cache" || true
chmod -R 775 "$APP_PATH/public/assets" "$APP_PATH/public/articles" || true
chown -R www-data:www-data "$APP_PATH/public/assets" "$APP_PATH/public/articles" || true
