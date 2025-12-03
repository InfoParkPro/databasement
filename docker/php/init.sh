#!/bin/sh
set -e

if [ "$APP_ENV" = "production" ]; then
    php artisan optimize
    php artisan migrate --force
fi
docker-php-entrypoint --config /etc/frankenphp/Caddyfile --adapter caddyfile
