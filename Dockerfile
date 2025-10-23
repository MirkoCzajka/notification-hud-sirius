# Dockerfile (PHP-FPM)
FROM php:8.3-fpm-alpine

# Paquetes base
RUN apk add --no-cache \
    bash curl git unzip \
    icu-dev oniguruma-dev libzip-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    sqlite-dev mariadb-client \
    composer

# Extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql pdo_sqlite \
    mbstring intl zip bcmath pcntl exif gd

WORKDIR /var/www/html
