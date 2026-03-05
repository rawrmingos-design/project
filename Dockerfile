# =====================================================
# Dockerfile — Laravel App (PHP 8.2 FPM)
# Stack : PHP 8.2 + FPM + Composer + Node.js
# =====================================================

FROM php:8.2-fpm-alpine AS base

# ---------------------------------------------------------------------------
# System dependencies & PHP Extensions
# ---------------------------------------------------------------------------
RUN apk add --no-cache \
        # Build tools
        $PHPIZE_DEPS \
        # Compression
        libzip-dev \
        zip \
        unzip \
        # Image processing (intervention/image)
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        # XML / cURL / ICU
        libxml2-dev \
        curl-dev \
        icu-dev \
        icu-libs \
        # Git (for Composer dev installs)
        git \
        # Supervisor (process manager)
        supervisor \
        # Nginx (optional: remove if using separate nginx container)
        nginx \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        xml \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# ---------------------------------------------------------------------------
# Composer
# ---------------------------------------------------------------------------
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# Node.js & NPM (untuk build assets Vite/Mix)
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
# Production Stage: install dependencies & copy code
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
RUN npm ci && npm run build && rm -rf node_modules

# Laravel optimization
RUN php artisan storage:link || true \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

# ---------------------------------------------------------------------------
# Permissions
# ---------------------------------------------------------------------------
RUN chown -R www-data:www-data /var/www/html/storage \
                               /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage \
                    /var/www/html/bootstrap/cache

# Copy Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 9000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
