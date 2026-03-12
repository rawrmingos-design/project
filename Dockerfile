# =====================================================
# Dockerfile — Laravel App (PHP 8.4 FPM Alpine)
# =====================================================

FROM php:8.4-fpm-alpine AS base

# ---------------------------------------------------------------------------
# Use install-php-extensions (handles all deps automatically on Alpine)
# https://github.com/mlocati/docker-php-extension-installer
# ---------------------------------------------------------------------------
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && mkdir -p /var/log/supervisor \
    && install-php-extensions \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        redis

# ---------------------------------------------------------------------------
# System tools: git, supervisor, nginx
# ---------------------------------------------------------------------------
RUN apk add --no-cache \
        git \
        supervisor \
        nginx

# ---------------------------------------------------------------------------
# Composer
# ---------------------------------------------------------------------------
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# Node.js & NPM
# ---------------------------------------------------------------------------
RUN apk add --no-cache nodejs npm

# ---------------------------------------------------------------------------
# PHP Runtime Configuration
# ---------------------------------------------------------------------------
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-laravel.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# ---------------------------------------------------------------------------
# Working Directory
# ---------------------------------------------------------------------------
WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Production Stage
# ---------------------------------------------------------------------------
FROM base AS production

# Copy composer files first (leverage layer cache)
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev)
RUN composer install \
        --optimize-autoloader \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist

# Copy semua source code
COPY . .

# Install & build Node.js assets
RUN npm ci && npm run production && rm -rf node_modules

# Buat direktori yang dibutuhkan Laravel SEBELUM artisan commands
RUN mkdir -p bootstrap/cache \
    && mkdir -p storage/framework/sessions \
               storage/framework/views \
               storage/framework/cache/data \
               storage/logs \
    && chmod -R 775 bootstrap/cache storage

# Laravel optimization
RUN php artisan storage:link || true \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

# Permissions (ownership untuk www-data / php-fpm)
RUN chown -R www-data:www-data /var/www/html/storage \
                               /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage \
                    /var/www/html/bootstrap/cache

# Copy Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy nginx config ke dalam container
COPY docker/nginx/app.conf /etc/nginx/nginx.conf

# Pastikan dir nginx ada
RUN mkdir -p /run/nginx

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
