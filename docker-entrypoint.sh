#!/bin/sh

set -e

echo "Running database migrations..."

php artisan migrate --force

echo "Running database seeders..."

php artisan db:seed --force

echo "Starting queue worker..."

php artisan queue:work \
    --sleep=1 \
    --tries=3 \
    --timeout=300 &

echo "Starting FrankenPHP..."

exec frankenphp run --config /etc/frankenphp/Caddyfile
