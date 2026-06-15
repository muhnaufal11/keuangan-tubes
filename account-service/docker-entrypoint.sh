#!/bin/sh

# Pastikan .env ada sebelum di-edit
[ -f .env ] || cp .env.example .env
# Pastikan APP_KEY terisi (kalau belum)
grep -q "^APP_KEY=base64" .env || php artisan key:generate --force

echo "==> Applying Docker environment overrides to .env..."
sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST:-account-db}|" .env
sed -i "s|^DB_PORT=.*|DB_PORT=${DB_PORT:-3306}|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-account_db}|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME:-root}|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD:-root}|" .env
sed -i "s|^REDIS_HOST=.*|REDIS_HOST=${REDIS_HOST:-redis}|" .env
sed -i "s|^REDIS_PORT=.*|REDIS_PORT=${REDIS_PORT:-6379}|" .env
[ -n "$APP_KEY" ] && sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
if grep -q "^AUTH_SERVICE_URL=" .env; then
  sed -i "s|^AUTH_SERVICE_URL=.*|AUTH_SERVICE_URL=${AUTH_SERVICE_URL:-http://auth-service:8000}|" .env
else
  echo "AUTH_SERVICE_URL=${AUTH_SERVICE_URL:-http://auth-service:8000}" >> .env
fi

echo "==> Clearing stale caches..."
php artisan optimize:clear || true

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Starting Laravel server..."
php artisan serve --host=0.0.0.0 --port=8000
