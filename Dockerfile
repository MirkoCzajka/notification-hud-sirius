# ---------- Stage 1: vendor (composer corriendo con PHP 8.3) ----------
FROM php:8.3-cli-alpine AS vendor

# Paquetes mínimos para composer + extensiones que tu app requiere al instalar
RUN apk add --no-cache bash git unzip icu-dev oniguruma-dev libzip-dev \
 && docker-php-ext-install intl zip mbstring

# Instalar el binario de composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
# (si usas autoload de rutas/seeders que requieren archivos, añade COPY de esos dirs antes del install)
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader

# ---------- Stage 2: runtime (php-fpm 8.3) ----------
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash curl git unzip \
    icu-dev oniguruma-dev libzip-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    sqlite-dev mariadb-client \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
    pdo pdo_mysql pdo_sqlite mbstring intl zip bcmath pcntl exif gd

WORKDIR /var/www/html

# Copiamos vendor resuelto con PHP 8.3
COPY --from=vendor /app/vendor ./vendor
# Copiamos el resto del código
COPY . .

# (si usas nginx/supervisord, añade aquí tu entrypoint/cmd/puerto)
