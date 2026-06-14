#!/bin/bash
# Update aplikasi ke versi terbaru

set -e

echo "Updating aplikasi..."

# Pull latest changes
echo "Pulling latest code dari repository..."
git pull origin main

# Install dependencies
echo "Installing PHP dependencies..."
docker-compose exec -T app composer install --optimize-autoloader --no-dev

# Build frontend assets
echo "Building frontend assets..."
npm install
npm run build

# Run migrations if any
echo "Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Clear caches
echo "Clearing caches..."
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan view:clear

# Restart containers
echo "Restarting containers..."
docker-compose down
docker-compose up -d

echo ""
echo "Update selesai!"
echo "Aplikasi versi terbaru sudah di-deploy"
