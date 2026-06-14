#!/bin/sh

# Pastikan .env ada sebelum di-edit
[ -f .env ] || cp .env.example .env
# Pastikan APP_KEY terisi (kalau belum)
grep -q "^APP_KEY=base64" .env || php artisan key:generate --force

echo "==> Applying Docker environment overrides to .env..."
# Override .env with actual Docker environment variables
# (Local .env may be mounted via volume with wrong host values)
sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST:-auth-db}|" .env
sed -i "s|^DB_PORT=.*|DB_PORT=${DB_PORT:-3306}|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-auth_db}|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME:-root}|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD:-root}|" .env
sed -i "s|^REDIS_HOST=.*|REDIS_HOST=${REDIS_HOST:-redis}|" .env
sed -i "s|^REDIS_PORT=.*|REDIS_PORT=${REDIS_PORT:-6379}|" .env
sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env

echo "==> Clearing stale caches..."
php artisan config:clear
php artisan cache:clear

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding admin user..."
php artisan db:seed --class=AdminUserSeeder --force

echo "==> Starting Laravel server..."
php artisan serve --host=0.0.0.0 --port=8000
