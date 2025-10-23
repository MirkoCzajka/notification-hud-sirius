#!/usr/bin/env sh
set -e

PORT="${PORT:-10000}"

# Render inyecta $PORT; templating simple
sed "s/__PORT__/${PORT}/g" /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Arranca supervisor (levanta php-fpm + nginx)
exec /usr/bin/supervisord -c /etc/supervisord.conf
