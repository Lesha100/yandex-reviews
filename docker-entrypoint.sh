#!/bin/sh

set -e

echo "Running database migrations..."

php artisan migrate --force

echo "Running database seeders..."

php artisan db:seed --force

echo "Starting FrankenPHP..."

exec frankenphp run --config /etc/frankenphp/Caddyfile
