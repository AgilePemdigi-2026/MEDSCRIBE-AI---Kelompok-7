# syntax=docker/dockerfile:1.7

# Stage 1: Frontend
FROM node:22-alpine AS frontend-build
WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js .
RUN npm run build

# Stage 2: Base PHP (Dipakai untuk Composer & Runtime sekaligus)
FROM php:8.4-apache AS php-base
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ffmpeg unzip \
    && install-php-extensions @composer bcmath curl intl mbstring opcache pcntl pdo_mysql xml zip \
    && rm -rf /var/lib/apt/lists/*

# Stage 3: Vendor Dependencies
FROM php-base AS php-dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader

# Stage 4: Production Runtime
FROM php-base
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    APP_ENV=production \
    APP_DEBUG=false

RUN a2enmod rewrite headers expires \
    && sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY --from=php-dependencies /app/vendor ./vendor
COPY . .
COPY --from=frontend-build /app/public/build ./public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/medscribe.ini

RUN mkdir -p storage/app/private storage/app/public storage/framework/cache \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=5 \
    CMD curl --fail --silent --show-error --max-time 5 http://127.0.0.1/up >/dev/null || exit 1

EXPOSE 80
CMD ["apache2-foreground"]