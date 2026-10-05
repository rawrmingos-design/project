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

# Install & build Node.js assets + SSR bundle
# Bundle SSR di-build dengan `ssr.noExternal` sehingga self-contained (tidak
# butuh node_modules saat runtime) — node_modules tetap dihapus.
RUN npm ci --legacy-peer-deps \
    && npm run production \
    && npm run build \
    && npm run build:ssr \
    && rm -rf node_modules

# ---------------------------------------------------------------------------
# Dokumentasi API (Docusaurus) — sumber di docs-api/, hasil build di resources/docs-api/
#
# Dua hal penting:
#   1. Hasil build SENGAJA tidak ditaruh di public/. Kalau di public/, nginx akan
#      menyajikannya langsung dari disk (blok `location ~* \.(css|js|map|…)$`) dan
#      gate login Laravel terlewati. resources/docs-api/ dilayani lewat route
#      ber-middleware auth.
#   2. Hasil build ikut image supaya rilis docs atomic & immutable seperti kode —
#      tidak ada langkah menyalin file ke VPS setelah deploy.
#
# DOCS_API_BASE_URL wajib diisi sebagai build arg oleh workflow (host API berbeda
# per environment). TIDAK diambil dari .env: .env tidak ada di dalam image build
# context, jadi nilainya akan salah diam-diam. Contoh-contoh di docs memakai nilai
# ini lewat komponen <ApiBase />.
# ---------------------------------------------------------------------------
ARG DOCS_API_BASE_URL=https://istanatopup.com/api/v1

RUN cd docs-api \
    && npm ci --legacy-peer-deps \
    && npm run build \
    && rm -rf node_modules \
    && rm -rf /var/www/html/resources/docs-api \
    && mkdir -p /var/www/html/resources/docs-api \
    && cp -R build/. /var/www/html/resources/docs-api/ \
    && chown -R www-data:www-data /var/www/html/resources/docs-api

# Buat direktori yang dibutuhkan Laravel SEBELUM artisan commands
RUN mkdir -p bootstrap/cache \
    && mkdir -p storage/framework/sessions \
               storage/framework/views \
               storage/framework/cache/data \
               storage/logs \
    && mkdir -p public/assets/product_logo \
               public/assets/thumbnail \
               public/assets/banner \
               public/assets/banner_game \
               public/assets/logo \
               public/assets/seasonal \
               public/assets/media \
               public/assets/optimized \
               public/articles/thumbnails \
    && chmod -R 775 bootstrap/cache storage public/assets public/articles

# Permissions (ownership untuk www-data / php-fpm)
RUN chown -R www-data:www-data /var/www/html/storage \
                               /var/www/html/bootstrap/cache \
                               /var/www/html/public/assets \
                               /var/www/html/public/articles \
    && chmod -R 775 /var/www/html/storage \
                    /var/www/html/bootstrap/cache \
                    /var/www/html/public/assets \
                    /var/www/html/public/articles

# Copy Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
# Copy nginx config ke dalam container
COPY docker/nginx/app.conf /etc/nginx/nginx.conf

# Pastikan dir nginx ada
RUN mkdir -p /run/nginx

EXPOSE 8080 9001

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
