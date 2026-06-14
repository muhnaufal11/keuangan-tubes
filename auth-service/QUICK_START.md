# Quick Start Guide - Deploy ke CasaOS

## Langkah Singkat (5 Menit)

### 1. Persiapan Local (Windows)
```powershell
# Build assets
npm install
npm run build

# Commit ke Git (jika ada repository)
git add .
git commit -m "Prepare for deployment"
git push origin main
```

### 2. SSH ke CasaOS Server
```bash
ssh user@casaos-ip
cd ~/apps/keuangan-app
```

### 3. Clone/Upload Aplikasi
```bash
# Opsi A: Clone dari Git
git clone https://github.com/your-repo/keuangan-app.git .

# Opsi B: Upload via SCP (dari Windows PowerShell)
# scp -r C:\xampp\htdocs\keuangan-app user@casaos-ip:~/apps/
```

### 4. Setup Environment
```bash
cp .env.production .env
# Edit .env sesuai kebutuhan
nano .env
```

**Minimal configuration:**
```dotenv
APP_URL=https://your-domain.com
DB_PASSWORD=strong_password
MYSQL_ROOT_PASSWORD=root_strong_password
```

### 5. Deploy dengan Docker
```bash
chmod +x deploy.sh
./deploy.sh
```

**Atau manual:**
```bash
docker-compose build
docker-compose up -d
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan storage:link
```

### 6. Domain Setup
Arahkan domain Anda ke IP CasaOS:
- DNS A Record: `your-domain.com` → `<casaos-ip>`

### 7. SSL Certificate (Recommended)
```bash
# Install Certbot
sudo apt-get install certbot

# Generate certificate
sudo certbot certonly --standalone -d your-domain.com

# Update docker/nginx.conf untuk SSL
# Kemudian restart container:
docker-compose down
docker-compose up -d
```

---

## Troubleshooting Cepat

| Problem | Solusi |
|---------|--------|
| Database connection error | `docker-compose logs mysql` - tunggu 30 detik |
| 404 Not Found | Pastikan APP_URL sesuai di `.env` |
| Assets tidak loading | `npm run build` kemudian `docker-compose up -d` |
| Permission denied | `docker-compose exec app chown -R www-data:www-data storage` |
| Nginx 502 Bad Gateway | `docker-compose restart app` |

---

## Commands Berguna

```bash
# View logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f mysql

# Execute command di app
docker-compose exec app php artisan tinker

# Backup database
docker-compose exec mysql mysqldump -u root -p$MYSQL_ROOT_PASSWORD keuangan_laravel > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u root -p$MYSQL_ROOT_PASSWORD keuangan_laravel < backup.sql

# Clear cache
docker-compose exec app php artisan cache:clear

# Update aplikasi
git pull
docker-compose build
docker-compose up -d

# Stop containers
docker-compose down

# Restart containers
docker-compose restart
```

---

## Monitoring

### Check Container Status
```bash
docker-compose ps
```

### Real-time Logs
```bash
docker-compose logs -f
```

### Check Resource Usage
```bash
docker stats
```

---

## Backup Strategy

### Daily Backup Script
Buat file `backup.sh`:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
docker-compose exec -T mysql mysqldump \
  -u root \
  -p$MYSQL_ROOT_PASSWORD \
  keuangan_laravel > backups/db_backup_$DATE.sql

tar -czf backups/app_backup_$DATE.tar.gz \
  --exclude=backups \
  --exclude=vendor \
  --exclude=node_modules \
  /var/www/html

echo "Backup complete: $DATE"
```

### Schedule dengan Cron
```bash
crontab -e

# Tambahkan:
0 2 * * * /home/user/keuangan-app/backup.sh
```

---

## Maintenance Schedule

**Daily:**
- Monitor logs
- Check disk space

**Weekly:**
- Database backup
- Application backup
- Security updates check

**Monthly:**
- Full system review
- Performance optimization
- Database cleanup

---

**Butuh bantuan lebih lanjut?**
Lihat file `DEPLOYMENT_CASAOS.md` untuk dokumentasi lengkap.
