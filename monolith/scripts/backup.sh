#!/bin/bash
# Backup database dan aplikasi

set -e

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="./backups"

# Create backup directory if not exists
mkdir -p $BACKUP_DIR

echo "Memulai backup database..."

# Backup MySQL database
docker-compose exec -T mysql mysqldump \
  -u root \
  -p$(grep MYSQL_ROOT_PASSWORD .env | cut -d '=' -f2) \
  $(grep DB_DATABASE .env | cut -d '=' -f2) > $BACKUP_DIR/db_backup_$DATE.sql

echo "Database backup selesai: db_backup_$DATE.sql"

echo "Memulai backup aplikasi..."

# Backup aplikasi (exclude vendor, node_modules, dan backups)
tar -czf $BACKUP_DIR/app_backup_$DATE.tar.gz \
  --exclude='backups' \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='.git' \
  --exclude='storage/logs' \
  .

echo "Application backup selesai: app_backup_$DATE.tar.gz"

# Cleanup old backups (keep last 7 days)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo ""
echo "Backup complete!"
echo "Backup directory: $BACKUP_DIR"
ls -lh $BACKUP_DIR | tail -5
