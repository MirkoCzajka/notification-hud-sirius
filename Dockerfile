# Dockerfile (PHP-FPM)
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash curl git unzip \
    icu-dev oniguruma-dev libzip-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    sqlite-dev mariadb-client

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql pdo_sqlite \
    mbstring intl zip bcmath pcntl exif gd

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
