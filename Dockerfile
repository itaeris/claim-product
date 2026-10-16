FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist
COPY artisan composer.json composer.lock ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY resources ./resources
COPY routes ./routes
RUN composer dump-autoload --optimize --no-dev --no-scripts

FROM php:8.5-fpm-bookworm
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor curl libicu-dev \
    && docker-php-ext-install pdo_mysql bcmath intl \
    && rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p /var/log/supervisor \
    && rm -rf /var/lib/apt/lists/*

COPY docker/nginx.conf /etc/nginx/sites-available/product-claim.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/product-claim.ini
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh \
    && ln -s /etc/nginx/sites-available/product-claim.conf /etc/nginx/sites-enabled/product-claim.conf

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p \
        storage/app/public \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
