FROM php:8.4-fpm-alpine AS php-base
RUN apk add --no-cache freetype-dev icu-dev libjpeg-turbo-dev libpng-dev libzip-dev postgresql-dev $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl opcache pcntl pdo_pgsql zip \
    && pecl install redis && docker-php-ext-enable redis && apk del $PHPIZE_DEPS
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

FROM php-base AS development
ENV APP_ENV=local
CMD ["php-fpm", "-F"]

FROM node:24-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php-base AS production
ENV APP_ENV=production APP_DEBUG=false
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts
COPY --chown=www-data:www-data . .
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && composer dump-autoload --no-dev --optimize --no-interaction
USER www-data
CMD ["php-fpm", "-F"]

FROM nginx:1.28-alpine AS nginx
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=production /var/www/html/public /var/www/html/public
RUN ln -s /var/www/html/storage/app/public /var/www/html/public/storage
