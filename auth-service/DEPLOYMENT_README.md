# 🚀 Keuangan App - CasaOS Deployment Guide

Dokumentasi lengkap untuk men-deploy aplikasi Laravel ini ke CasaOS server Anda.

## 📚 File Dokumentasi

Workspace ini sekarang dilengkapi dengan dokumentasi deployment yang komprehensif:

### 1. **QUICK_START.md** ⚡
   - Panduan singkat untuk setup cepat (5 menit)
   - Command-command yang paling sering digunakan
   - Troubleshooting cepat
   - **Mulai dari sini jika ingin setup cepat**

### 2. **DEPLOYMENT_CASAOS.md** 📖
   - Dokumentasi lengkap step-by-step
   - Multiple deployment options (Docker & Non-Docker)
   - Setup SSL/HTTPS
   - Maintenance procedures
   - Complete troubleshooting section

### 3. **PRE_DEPLOYMENT_CHECKLIST.md** ✅
   - Checklist lengkap sebelum deployment
   - Security checks
   - Testing procedures
   - Post-deployment verification

## 📁 File-File Deployment yang Dibuat

### Docker & Infrastructure
```
├── Dockerfile                 # PHP-FPM container definition
├── docker-compose.yml         # Complete stack (App, Nginx, MySQL)
├── docker/
│   └── nginx.conf            # Nginx configuration
└── .env.production           # Production environment template
```

### Scripts & Automation
```
scripts/
├── backup.sh                 # Automated backup (database + app)
├── restore.sh               # Restore dari backup file
├── update.sh                # Update aplikasi ke versi terbaru
└── health-check.sh          # Monitor kesehatan aplikasi
```

## 🎯 Quick Start (5 Menit)

### Step 1: Local Preparation
```bash
# Windows PowerShell
npm install
npm run build
git add .
git commit -m "Ready for deployment"
git push
```

### Step 2: Server Setup
```bash
# SSH ke CasaOS
ssh user@your-server-ip
cd ~/apps
git clone https://github.com/your-repo/keuangan-app.git
cd keuangan-app
```

### Step 3: Configuration
```bash
# Setup environment
cp .env.production .env
nano .env  # Update dengan domain dan password Anda
```

### Step 4: Deploy
```bash
# Deploy dengan Docker
chmod +x deploy.sh
./deploy.sh
```

**Selesai! Aplikasi Anda sudah jalan di CasaOS** 🎉

## 🐳 Docker Stack Overview

Aplikasi ini menggunakan 3 container:

```
┌─────────────────────────────────────┐
│         NGINX (Port 80/443)         │
│          Web Server & Proxy         │
└──────────────┬──────────────────────┘
               │
       ┌───────┴────────┐
       │                │
   ┌───▼────────┐  ┌───▼──────────┐
   │   PHP-FPM  │  │    MySQL     │
   │  (App)     │  │  (Database)  │
   └────────────┘  └──────────────┘
```

### Container Details

| Container | Image | Port | Purpose |
|-----------|-------|------|---------|
| `app` | php:8.1-fpm | 9000 | Laravel application server |
| `nginx` | nginx:1.25 | 80/443 | Web server & reverse proxy |
| `mysql` | mysql:8.0 | 3306 | Database server |

## 📋 Essential Commands

### Deployment
```bash
docker-compose up -d          # Start containers
docker-compose down           # Stop containers
docker-compose restart        # Restart all containers
docker-compose logs -f app    # View app logs
```

### Database
```bash
docker-compose exec app php artisan migrate        # Run migrations
docker-compose exec app php artisan tinker         # Laravel console
docker-compose exec mysql mysql -u root -p        # MySQL CLI
```

### Maintenance
```bash
./scripts/backup.sh           # Backup database & app
./scripts/restore.sh <file>   # Restore from backup
./scripts/update.sh           # Update aplikasi
./scripts/health-check.sh     # Check system health
```

### Useful Debug
```bash
docker-compose ps                          # Show container status
docker-compose logs mysql                  # Database logs
docker-compose exec app php artisan cache:clear  # Clear cache
chmod +x scripts/*.sh                      # Make scripts executable
```

## 🔐 Security Checklist

Sebelum go-live, pastikan:

- [ ] `APP_DEBUG=false` di `.env`
- [ ] Strong database password di `.env`
- [ ] SSL/HTTPS sudah setup
- [ ] Firewall sudah configured
- [ ] Regular backup sudah dijadwalkan
- [ ] Monitoring sudah setup

## 🛠 Troubleshooting

### 502 Bad Gateway
```bash
docker-compose restart app
docker-compose logs app | tail -50
```

### Database Connection Error
```bash
docker-compose logs mysql
# Tunggu ~30 detik untuk MySQL startup
docker-compose exec app php artisan migrate --force
```

### Permission Denied
```bash
docker-compose exec app chown -R www-data:www-data storage
docker-compose exec app chown -R www-data:www-data bootstrap/cache
```

### Assets Not Loading
```bash
npm run build
docker-compose up -d  # Restart containers
```

## 📊 System Requirements

### Minimum
- CPU: 2 cores
- RAM: 2GB
- Storage: 20GB
- Network: Stable internet connection

### Recommended
- CPU: 4 cores
- RAM: 4GB
- Storage: 50GB
- Network: 10Mbps minimum

## 📈 Monitoring

### Check Resource Usage
```bash
docker stats

# Free disk space
docker-compose exec app df -h

# Memory usage
free -h
```

### Setup Automated Backups
```bash
# Add to crontab (runs daily at 2 AM)
crontab -e

# Add line:
0 2 * * * /home/user/keuangan-app/scripts/backup.sh
```

## 🔄 Update Procedure

```bash
# Update aplikasi ke versi terbaru
./scripts/update.sh

# Or manually:
git pull origin main
docker-compose exec app composer install --optimize-autoloader --no-dev
npm install && npm run build
docker-compose down && docker-compose up -d
docker-compose exec app php artisan migrate --force
```

## 📞 Support & Resources

- **Laravel Docs:** https://laravel.com/docs
- **CasaOS Docs:** https://docs.casaos.io
- **Docker Docs:** https://docs.docker.com
- **Nginx Docs:** https://nginx.org/en/docs/

## 📋 File Structure

```
keuangan-app/
├── app/                          # Application code
├── config/                       # Configuration files
├── database/                     # Migrations & seeders
├── public/                       # Public assets
├── resources/                    # Views & assets source
├── routes/                       # API & web routes
├── storage/                      # Logs, cache, uploads
├── tests/                        # Test files
│
├── Dockerfile                    # Docker image definition
├── docker-compose.yml            # Docker services config
├── docker/
│   └── nginx.conf               # Nginx config
│
├── scripts/                      # Helper scripts
│   ├── backup.sh
│   ├── restore.sh
│   ├── update.sh
│   └── health-check.sh
│
├── DEPLOYMENT_CASAOS.md          # Full deployment guide
├── QUICK_START.md                # Quick start guide
├── PRE_DEPLOYMENT_CHECKLIST.md   # Pre-deployment checks
├── .env.production               # Production env template
└── deploy.sh                     # Automated deployment script
```

## ⚠️ Important Notes

1. **Never commit `.env` file to Git** - Use `.env.example` or `.env.production` template
2. **Always backup before updates** - Use `./scripts/backup.sh`
3. **Test on staging first** - Don't deploy directly to production
4. **Keep dependencies updated** - Run security updates regularly
5. **Monitor logs daily** - Check `docker-compose logs -f`

## 🎓 Learning Path

1. Read **QUICK_START.md** - Understand the basic process
2. Review **Dockerfile** & **docker-compose.yml** - Learn Docker setup
3. Read **DEPLOYMENT_CASAOS.md** - Deep dive into configurations
4. Use **PRE_DEPLOYMENT_CHECKLIST.md** - Verify everything is ready
5. Deploy! And monitor with helper scripts

---

## Version Info

- **Laravel Version:** 9.x
- **PHP Version:** 8.1
- **Node Version:** 18.x+
- **Docker:** Latest stable
- **Last Updated:** January 8, 2026

---

**Ready to deploy? Start with [QUICK_START.md](QUICK_START.md)** 🚀

