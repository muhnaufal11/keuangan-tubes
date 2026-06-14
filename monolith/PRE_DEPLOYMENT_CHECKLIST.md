# 📋 Pre-Deployment Checklist

Gunakan checklist ini untuk memastikan aplikasi siap di-deploy ke CasaOS.

## ✅ Local Development Preparation

- [ ] Semua kode sudah di-commit ke Git
- [ ] `.env` sudah dikonfigurasi dengan benar
- [ ] Database sudah di-setup locally
- [ ] `npm install` sudah dijalankan
- [ ] `npm run build` sudah dijalankan dengan sukses
- [ ] Assets (CSS/JS) sudah ter-minify
- [ ] Testing sudah dijalankan: `php artisan test`
- [ ] Tidak ada error di browser console

## 📦 Deployment Files Preparation

- [ ] `Dockerfile` sudah ada
- [ ] `docker-compose.yml` sudah ada dan dikonfigurasi
- [ ] `docker/nginx.conf` sudah ada
- [ ] `.env.production` sudah dibuat
- [ ] `deploy.sh` sudah ada dan executable
- [ ] `scripts/` folder sudah ada dengan helper scripts

## 🔐 Security Checklist

- [ ] `APP_DEBUG=false` di production
- [ ] `APP_KEY` sudah di-generate
- [ ] Strong password untuk database (`DB_PASSWORD`)
- [ ] Strong password untuk MySQL root (`MYSQL_ROOT_PASSWORD`)
- [ ] `.env.production` TIDAK ter-commit ke Git
- [ ] `storage/` dan `bootstrap/cache/` permissions sudah di-set
- [ ] CORS di-configure di `config/cors.php`
- [ ] CSRF protection sudah enabled

## 🌐 Domain & SSL

- [ ] Domain sudah di-setup (DNS A Record)
- [ ] Domain sudah pointing ke IP server CasaOS
- [ ] SSL certificate plan (Let's Encrypt)
- [ ] Email untuk SSL notifications sudah siap

## 📊 Database Preparation

- [ ] Database migrations sudah di-test
- [ ] Database seeders sudah di-test (jika ada)
- [ ] Backup strategi sudah direncanakan
- [ ] Database credentials sudah di-setup di `.env.production`

## 🚀 Deployment Process

### Pre-Deployment
- [ ] Backup database lokal
- [ ] Commit final ke Git
- [ ] Test `docker-compose build` locally

### During Deployment
- [ ] SSH ke CasaOS server
- [ ] Clone/upload aplikasi
- [ ] Copy `.env.production` ke `.env`
- [ ] Run `./deploy.sh` atau manual Docker commands
- [ ] Verify database migrations
- [ ] Test aplikasi di browser

### Post-Deployment
- [ ] Verify domain accessible
- [ ] Check logs: `docker-compose logs -f`
- [ ] Test core functionality
- [ ] Setup SSL certificate
- [ ] Setup backup schedule

## 📱 Testing After Deployment

- [ ] Home page loading dengan benar
- [ ] Login/authentication working
- [ ] Database queries working
- [ ] Assets (CSS/JS) loading correctly
- [ ] File uploads working (jika ada)
- [ ] API endpoints responding correctly (jika ada)
- [ ] Email sending working (jika ada)
- [ ] Error pages displaying correctly

## 📈 Monitoring & Maintenance

- [ ] Setup log monitoring
- [ ] Setup disk space monitoring
- [ ] Setup uptime monitoring
- [ ] Create backup schedule
- [ ] Create update schedule
- [ ] Setup admin notifications

## 🔧 Production Optimizations

- [ ] Config caching: `php artisan config:cache`
- [ ] Route caching: `php artisan route:cache`
- [ ] Nginx gzip enabled (di nginx.conf)
- [ ] Static assets caching (di nginx.conf)
- [ ] Database connection pooling (jika needed)
- [ ] Redis setup (jika menggunakan caching)

## 📝 Documentation

- [ ] Update `.env.example` untuk reference
- [ ] Document any custom configurations
- [ ] Document backup procedures
- [ ] Document recovery procedures
- [ ] Document team member access details

---

## Quick Reference Commands

```bash
# View system info
docker-compose ps
docker-compose logs -f app
docker-compose exec app df -h

# Database operations
docker-compose exec app php artisan migrate:status
docker-compose exec app php artisan tinker

# Maintenance
docker-compose down
docker-compose up -d
docker-compose restart

# Backup
./scripts/backup.sh

# Update
./scripts/update.sh

# Health check
./scripts/health-check.sh
```

---

## Troubleshooting Quick Links

- Database issues → DEPLOYMENT_CASAOS.md → Troubleshooting
- Assets not loading → QUICK_START.md → Troubleshooting
- Permission errors → DEPLOYMENT_CASAOS.md → Permissions
- SSL issues → DEPLOYMENT_CASAOS.md → SSL Setup

---

**Last Updated:** January 8, 2026
**Status:** Ready for Deployment ✓
