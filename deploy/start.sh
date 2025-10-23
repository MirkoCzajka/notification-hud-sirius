#!/usr/bin/env sh
set -e

PORT="${PORT:-10000}"
# Ajustar puerto en nginx.conf
sed -i "s/listen 0.0.0.0:10000;/listen 0.0.0.0:${PORT};/" /etc/nginx/nginx.conf

# Warmups/migraciones (si falla, seguimos)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php artisan migrate --force || true

# Levantar nginx + php-fpm
exec /usr/bin/supervisord -c /etc/supervisord.conf
