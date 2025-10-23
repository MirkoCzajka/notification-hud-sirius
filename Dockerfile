# ---------- Stage 1: vendor ----------
FROM composer:2-php8.3 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress

# ---------- Stage 2: app (nginx + php-fpm + supervisord) ----------
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash curl git unzip \
    icu-dev oniguruma-dev libzip-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    sqlite-dev mariadb-client \
    nginx supervisor

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql pdo_sqlite \
    mbstring intl zip bcmath pcntl exif gd

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor

# Configs de runtime
COPY deploy/nginx.conf /etc/nginx/nginx.conf
COPY deploy/supervisord.conf /etc/supervisord.conf
COPY deploy/start.sh /start.sh
RUN chmod +x /start.sh

# Preparar storage y permisos (incluye sqlite demo)
RUN mkdir -p storage/framework/{cache,sessions,views} \
    && mkdir -p storage/logs storage/database bootstrap/cache \
    && touch storage/database/database.sqlite \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R ug+rwX storage bootstrap/cache

# Render inyecta $PORT (default 10000 por si corrés local)
ENV PORT=10000
EXPOSE 10000

CMD ["/start.sh"]
