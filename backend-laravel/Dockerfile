FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

FROM php:8.4-fpm-alpine

RUN apk add --no-cache oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS oniguruma-dev \
    && docker-php-ext-install pdo_mysql mbstring bcmath \
    && apk del .build-deps

WORKDIR /var/www/html

COPY --from=vendor /app/vendor /var/www/html/vendor

EXPOSE 9000

CMD ["php-fpm"]
