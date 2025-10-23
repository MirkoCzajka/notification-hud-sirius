# ---------- Stage 1: vendor (usa php cli 8.3) ----------
FROM php:8.3-cli-alpine AS vendor

# Composer bin
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Paquetes que Composer suele necesitar para extensiones/opciones
RUN apk add --no-cache \
    bash git unzip \
    icu-dev oniguruma-dev libzip-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    sqlite-dev mariadb-connector-c-dev

WORKDIR /app
COPY composer.json composer.lock ./

# Importante: NO correr scripts (no existe artisan en este stage)
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts

# ---------- Stage 2: runtime (php-fpm 8.3 + nginx + supervisor) ----------
FROM php:8.3-fpm-alpine AS app

# Paquetes del runtime
RUN apk add --no-cache \
    nginx supervisor bash curl \
    icu-dev oniguruma-dev libzip-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    sqlite-dev mariadb-connector-c-dev

# Extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
    pdo pdo_mysql pdo_sqlite mbstring intl zip bcmath pcntl exif gd

WORKDIR /var/www/html

# Copiás todo tu código primero (para que exista artisan/public/etc.)
COPY . .

# Luego el vendor generado en el stage vendor
COPY --from=vendor /app/vendor ./vendor

# Preparar permisos/carpetas
RUN mkdir -p storage/framework/{cache,data,sessions,views} bootstrap/cache /run/nginx \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Config php-fpm (socket unix para hablar con nginx)
RUN { \
  echo '[global]'; \
  echo 'daemonize = no'; \
  echo ''; \
  echo '[www]'; \
  echo 'listen = /var/run/php-fpm.sock'; \
  echo 'listen.owner = nginx'; \
  echo 'listen.group = nginx'; \
  echo 'listen.mode = 0660'; \
  echo 'user = www-data'; \
  echo 'group = www-data'; \
  echo 'pm = dynamic'; \
  echo 'pm.max_children = 5'; \
  echo 'pm.start_servers = 2'; \
  echo 'pm.min_spare_servers = 1'; \
  echo 'pm.max_spare_servers = 3'; \
} > /usr/local/etc/php-fpm.d/zz-render.conf

# Configs de nginx y supervisor + script de arranque
COPY docker/nginx.conf              /etc/nginx/nginx.conf
COPY docker/site.conf               /etc/nginx/conf.d/default.conf.template
COPY docker/supervisord.conf        /etc/supervisord.conf
COPY docker/start.sh                /start.sh
RUN chmod +x /start.sh

EXPOSE 10000
CMD ["/start.sh"]
