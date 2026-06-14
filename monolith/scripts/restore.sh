#!/bin/bash
# Restore database dari backup

set -e

if [ -z "$1" ]; then
    echo "Usage: ./restore.sh <backup_file>"
    echo ""
    echo "Available backups:"
    ls -1 ./backups/db_backup_*.sql 2>/dev/null | head -10
    exit 1
fi

BACKUP_FILE=$1

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Error: Backup file '$BACKUP_FILE' not found"
    exit 1
fi

echo "Restoring database dari: $BACKUP_FILE"
read -p "Lanjutkan? (yes/no) " -n 3 -r
echo
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "Cancelled"
    exit 1
fi

# Get database name dan credentials dari .env
DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
MYSQL_ROOT_PASSWORD=$(grep MYSQL_ROOT_PASSWORD .env | cut -d '=' -f2)

# Restore database
docker-compose exec -T mysql mysql \
  -u root \
  -p$MYSQL_ROOT_PASSWORD \
  $DB_NAME < $BACKUP_FILE

echo "Database restore selesai!"
