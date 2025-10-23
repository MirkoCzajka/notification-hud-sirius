#!/usr/bin/env sh
set -e

# Render define $PORT. Si no, usa 10000 por defecto.
PORT="${PORT:-10000}"

# Generar el vhost desde la plantilla
sed "s/%%PORT%%/${PORT}/g" /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Permisos de Laravel (por si el sistema de archivos llega limpio)
mkdir -p storage/framework/{cache,data,sessions,views} bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Variables de entorno útiles para Laravel
export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"

# Migraciones automáticas si quieres (opcional, coméntalo si no aplica con SQLite)
# php artisan migrate --force || true

# Arranca todo con supervisord
exec /usr/bin/supervisord -c /etc/supervisord.conf
