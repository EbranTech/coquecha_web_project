FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build


FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-dev \
    --no-scripts \
    --optimize-autoloader

COPY . .
COPY --from=assets /app/public/build /app/public/build


FROM php:8.4-fpm-alpine

RUN apk add --no-cache oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS oniguruma-dev \
    && docker-php-ext-install pdo_mysql mbstring bcmath \
    && apk del .build-deps

RUN apk add --no-cache ca-certificates curl \
    && curl -fsSL https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem \
       -o /etc/ssl/certs/rds-global-bundle.pem

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
