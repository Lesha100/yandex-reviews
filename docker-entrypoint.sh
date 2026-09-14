#!/bin/sh

set -e

echo "Running database migrations..."

php artisan migrate --force

echo "Starting FrankenPHP..."

exec frankenphp run --config /etc/frankenphp/Caddyfile
