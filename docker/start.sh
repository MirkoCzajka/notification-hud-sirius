#!/usr/bin/env sh
set -e

# Ajustes de permisos/carpetas necesarias
mkdir -p storage/framework/{cache,data,sessions,views} bootstrap/cache /run/nginx
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Variable PORT (Render la inyecta). Si no está, default 8080 para local.
export PORT="${PORT:-8080}"

# Sustituir la variable ${PORT} en la plantilla del site
envsubst '$PORT' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Opcional: optimizaciones de Laravel (no fallar si falta .env en build)
php -r "file_exists('artisan') && @passthru('php artisan optimize:clear');"
php -r "file_exists('artisan') && @passthru('php artisan config:cache');" || true
php -r "file_exists('artisan') && @passthru('php artisan route:cache');" || true
php -r "file_exists('artisan') && @passthru('php artisan view:cache');" || true

# Levantar supervisor (que lanza php-fpm + nginx)
exec /usr/bin/supervisord -c /etc/supervisord.conf
