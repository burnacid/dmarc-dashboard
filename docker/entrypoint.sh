#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  [ -f database/database.sqlite ] || touch database/database.sqlite
fi

chown -R www-data:www-data storage bootstrap/cache database

if [ -f .env ] && [ -z "${APP_KEY:-}" ] && grep -qE '^APP_KEY=$' .env; then
  php artisan key:generate --force --no-interaction || true
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force --no-interaction || true
fi

exec "$@"

