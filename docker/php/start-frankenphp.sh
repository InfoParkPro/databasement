#!/bin/sh
set -e

if [ "$APP_ENV" = "production" ]; then
    php artisan optimize
fi
php artisan db:wait --allow-missing-db
php artisan migrate --force
frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile
