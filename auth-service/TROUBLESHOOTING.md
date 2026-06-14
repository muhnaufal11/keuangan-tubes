# 🔧 Troubleshooting & Debugging Guide

Panduan lengkap untuk mengatasi masalah yang mungkin terjadi selama deployment dan operasional.

## 📋 Daftar Isi

1. [Common Issues & Solutions](#common-issues--solutions)
2. [Diagnostic Tools](#diagnostic-tools)
3. [Log Analysis](#log-analysis)
4. [Performance Issues](#performance-issues)
5. [Security Issues](#security-issues)
6. [Backup & Recovery](#backup--recovery)

---

## Common Issues & Solutions

### 1. Application Won't Start

#### Issue: 502 Bad Gateway

**Symptoms:**
- Nginx error: `502 Bad Gateway`
- Browser shows error accessing `http://localhost`
- App container might be crashed

**Diagnosis:**
```bash
# Check container status
docker-compose ps

# Look for app container status
# Should be: Up (running)

# If not running, check logs
docker-compose logs app | tail -50
```

**Solutions:**

```bash
# Solution 1: Restart container
docker-compose restart app

# Solution 2: Check if port 9000 is in use
sudo netstat -tlnp | grep 9000

# Solution 3: Check PHP-FPM logs
docker-compose logs app

# Solution 4: Full restart
docker-compose down
docker-compose up -d
docker-compose logs -f app
```

---

### 2. Database Connection Issues

#### Issue: Can't Connect to Database

**Symptoms:**
- Laravel error: `SQLSTATE[HY000] [2002]`
- Error message: `Connection refused`
- Database operations fail

**Diagnosis:**
```bash
# Check MySQL container status
docker-compose ps mysql

# Check MySQL logs
docker-compose logs mysql

# Try to connect manually
docker-compose exec mysql mysql -u root -p
# Enter password when prompted
```

**Solutions:**

```bash
# Solution 1: Wait for MySQL to be ready (first deploy)
# MySQL needs ~30 seconds to start
sleep 30
docker-compose exec app php artisan migrate --force

# Solution 2: Check credentials in .env
cat .env | grep DB_

# Solution 3: Verify MySQL is running
docker-compose logs mysql

# Solution 4: Reset database container
docker-compose down
docker volume rm keuangan-app_mysql_data  # WARNING: Deletes data!
docker-compose up -d
docker-compose exec app php artisan migrate --force
```

**Credentials Check:**
```bash
# Expected in .env:
# DB_HOST=mysql           (service name)
# DB_PORT=3306            (default MySQL port)
# DB_DATABASE=keuangan_laravel
# DB_USERNAME=laravel
# DB_PASSWORD=your_password
# MYSQL_ROOT_PASSWORD=root_password
```

---

### 3. Permission Issues

#### Issue: Permission Denied / Cannot Write to Storage

**Symptoms:**
- Error: `permission denied`
- Cannot create log files
- Cannot upload files
- Error in `storage/logs/`

**Diagnosis:**
```bash
# Check storage permissions
docker-compose exec app ls -la storage/

# Check bootstrap/cache permissions
docker-compose exec app ls -la bootstrap/cache/
```

**Solutions:**

```bash
# Solution 1: Fix ownership
docker-compose exec app chown -R www-data:www-data storage
docker-compose exec app chown -R www-data:www-data bootstrap/cache

# Solution 2: Fix permissions (755 for dirs, 644 for files)
docker-compose exec app chmod -R 755 storage
docker-compose exec app chmod -R 755 bootstrap/cache

# Solution 3: Full permission reset
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
docker-compose exec app chmod -R 755 /var/www/html/storage
docker-compose exec app chmod -R 777 /var/www/html/storage/logs
```

---

### 4. Assets Not Loading (CSS/JS)

#### Issue: Static Files Return 404

**Symptoms:**
- CSS/JS files not loading
- Browser console shows 404 errors
- Website looks broken/unstyled
- Resources like images not showing

**Diagnosis:**
```bash
# Check if assets were built
ls -la public/

# Check docker volume mapping
docker-compose exec app ls -la public/

# Test accessing asset directly
curl http://localhost/css/app.css
```

**Solutions:**

```bash
# Solution 1: Build assets
npm install
npm run build

# Solution 2: Rebuild in container
docker-compose exec app npm run build

# Solution 3: Check APP_URL in .env
# Should match your domain (http://localhost or https://your-domain.com)
cat .env | grep APP_URL

# Solution 4: Full restart
docker-compose down
docker-compose up -d

# Solution 5: Clear nginx cache
docker-compose exec nginx nginx -s reload
```

---

### 5. Nginx Configuration Errors

#### Issue: Nginx Won't Start / 404 Errors

**Symptoms:**
- Nginx container crashes
- 404 Not Found for all requests
- Cannot access application at all

**Diagnosis:**
```bash
# Check nginx logs
docker-compose logs nginx

# Test nginx configuration
docker-compose exec nginx nginx -t

# Check if nginx is running
docker-compose ps nginx
```

**Solutions:**

```bash
# Solution 1: Test & reload nginx config
docker-compose exec nginx nginx -t
docker-compose exec nginx nginx -s reload

# Solution 2: View current config
docker-compose exec nginx cat /etc/nginx/conf.d/default.conf

# Solution 3: Restart nginx container
docker-compose restart nginx

# Solution 4: Check error logs for details
docker-compose logs nginx | grep error

# Solution 5: Validate docker/nginx.conf
# Edit docker/nginx.conf and ensure:
# - Syntax is correct
# - Paths are correct (/var/www/html)
# - PHP-FPM address is correct (app:9000)

docker-compose down
docker-compose build
docker-compose up -d
```

---

### 6. Memory/Disk Space Issues

#### Issue: Out of Disk Space or Memory

**Symptoms:**
- Slow application
- Database errors
- Cannot write to disk
- Containers keep restarting

**Diagnosis:**
```bash
# Check disk space
df -h

# Check memory usage
free -h

# Check Docker disk usage
docker system df

# Check logs size
du -sh storage/logs/
```

**Solutions:**

```bash
# Solution 1: Clean old logs
docker-compose exec app truncate -s 0 storage/logs/laravel.log

# Solution 2: Clean Docker images/containers
docker system prune

# Solution 3: Clean Docker volumes (WARNING: Deletes data!)
docker system prune --volumes

# Solution 4: Remove old backups
ls -lh backups/
rm backups/older_than_30_days.sql

# Solution 5: Optimize database
docker-compose exec mysql mysql -u root -p$MYSQL_ROOT_PASSWORD keuangan_laravel -e "OPTIMIZE TABLE *;"
```

---

### 7. SSL/HTTPS Issues

#### Issue: Certificate Errors or HTTPS Not Working

**Symptoms:**
- Browser warning: "Not secure"
- SSL certificate error
- Cannot access via HTTPS
- Mixed content warning

**See:** [SSL_SETUP_GUIDE.md](SSL_SETUP_GUIDE.md#troubleshooting)

---

### 8. Database Backup/Restore Issues

#### Issue: Backup or Restore Failed

**Diagnosis:**
```bash
# Check backup directory
ls -la backups/

# Test manual backup
docker-compose exec mysql mysqldump -u root -p$MYSQL_ROOT_PASSWORD keuangan_laravel > test.sql

# Check file size
du -sh backups/*.sql
```

**Solutions:**

```bash
# Solution 1: Manual backup
docker-compose exec mysql mysqldump -u root -p$(grep MYSQL_ROOT_PASSWORD .env | cut -d '=' -f2) keuangan_laravel > manual_backup.sql

# Solution 2: Manual restore
docker-compose exec -T mysql mysql -u root -p$(grep MYSQL_ROOT_PASSWORD .env | cut -d '=' -f2) keuangan_laravel < backup_file.sql

# Solution 3: Use helper script
./scripts/backup.sh
./scripts/restore.sh backups/db_backup_20240108_150000.sql
```

---

## Diagnostic Tools

### 1. Container Status Check

```bash
# List all containers
docker-compose ps

# Detailed container info
docker-compose ps --all

# View container details
docker inspect keuangan-app
```

### 2. Logs Inspection

```bash
# View all logs
docker-compose logs

# View specific container logs
docker-compose logs app
docker-compose logs nginx
docker-compose logs mysql

# Follow logs (real-time)
docker-compose logs -f

# Last 50 lines
docker-compose logs --tail=50

# Logs from last hour
docker-compose logs --since 1h

# View timestamps
docker-compose logs --timestamps
```

### 3. Network Diagnostics

```bash
# Check network connectivity
docker-compose exec app ping mysql

# Test port connectivity
docker-compose exec app nc -zv mysql 3306

# View network info
docker network inspect keuangan-app_keuangan-network

# DNS check
docker-compose exec app nslookup mysql
```

### 4. Process Inspection

```bash
# View running processes
docker-compose exec app ps aux

# Monitor processes
docker stats

# Check listening ports
docker-compose exec app netstat -tlnp
```

---

## Log Analysis

### 1. Laravel Application Logs

```bash
# View recent Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log

# Search for errors
docker-compose exec app grep ERROR storage/logs/laravel.log

# Count errors
docker-compose exec app grep -c ERROR storage/logs/laravel.log

# View specific error
docker-compose exec app grep -A 5 "Exception" storage/logs/laravel.log
```

### 2. Database Logs

```bash
# MySQL error log
docker-compose logs mysql

# Check for connection errors
docker-compose logs mysql | grep -i error

# Monitor MySQL in real-time
docker-compose logs -f mysql
```

### 3. Nginx Logs

```bash
# Access logs
docker-compose exec nginx tail -f /var/log/nginx/access.log

# Error logs
docker-compose exec nginx tail -f /var/log/nginx/error.log

# 404 errors
docker-compose exec nginx grep "404" /var/log/nginx/access.log
```

---

## Performance Issues

### Issue: Slow Application

**Diagnosis:**
```bash
# Check resource usage
docker stats

# Check disk I/O
iostat -x 1

# Database query performance
docker-compose exec app php artisan tinker
# In tinker:
> DB::listen(function($query) { dump($query); });

# Check slow logs
docker-compose exec mysql mysql -u root -p -e "SHOW PROCESSLIST;"
```

**Solutions:**

```bash
# Solution 1: Optimize database
docker-compose exec app php artisan optimize:clear

# Solution 2: Clear all caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear

# Solution 3: Database optimization
docker-compose exec mysql mysql -u root -p -e "ANALYZE TABLE keuangan_laravel.*;"

# Solution 4: Increase resources
# Edit docker-compose.yml and add:
# services:
#   app:
#     mem_limit: 1g
#   mysql:
#     mem_limit: 1g

docker-compose down
docker-compose up -d
```

---

## Security Issues

### Issue: Suspicious Activity / Security Concerns

**Diagnosis:**
```bash
# Check failed login attempts
docker-compose logs app | grep -i "failed\|unauthorized"

# Check for suspicious file access
docker-compose logs app | grep -i "permission\|denied"

# View recent errors
docker-compose logs app | tail -100
```

**Solutions:**

```bash
# Solution 1: Review security settings
# Check app/Http/Kernel.php for middleware
# Check config/cors.php for CORS settings

# Solution 2: Update Laravel
./scripts/update.sh

# Solution 3: Run security scan
docker-compose exec app php artisan security:scan

# Solution 4: Review logs regularly
./scripts/health-check.sh
```

---

## Backup & Recovery

### Issue: Need to Recover from Backup

**Steps:**

```bash
# Step 1: List available backups
ls -lh backups/

# Step 2: Restore from backup
./scripts/restore.sh backups/db_backup_20240108.sql

# Step 3: Verify restoration
docker-compose exec app php artisan tinker
# In tinker: > DB::table('users')->count();

# Step 4: Restore application files (if needed)
# If you have app_backup_TIMESTAMP.tar.gz:
tar -xzf backups/app_backup_20240108.tar.gz -C ~/

# Step 5: Clear cache after restore
docker-compose restart app
```

---

## Emergency Recovery

### Issue: Complete System Failure

**Nuclear Option (Last Resort):**

```bash
# WARNING: This deletes all data!
# Only use if absolutely necessary!

# Step 1: Stop all containers
docker-compose down

# Step 2: Remove volumes (DELETE DATA!)
docker volume rm keuangan-app_mysql_data

# Step 3: Rebuild and start fresh
docker-compose build
docker-compose up -d

# Step 4: Run migrations
docker-compose exec app php artisan migrate

# Step 5: Restore from backup (if available)
docker-compose exec -T mysql mysql -u root -p$(grep MYSQL_ROOT_PASSWORD .env | cut -d '=' -f2) keuangan_laravel < latest_backup.sql

# Step 6: Verify
docker-compose ps
docker-compose logs app
```

---

## Contact Support

If issues persist:

1. **Collect diagnostic information:**
   ```bash
   docker-compose ps > status.txt
   docker-compose logs > logs.txt
   docker stats > stats.txt
   ```

2. **Save to safe location** before reporting

3. **Review documentation:**
   - [DEPLOYMENT_CASAOS.md](DEPLOYMENT_CASAOS.md)
   - [QUICK_START.md](QUICK_START.md)
   - [SSL_SETUP_GUIDE.md](SSL_SETUP_GUIDE.md)

---

## Useful Resources

- [Docker Troubleshooting](https://docs.docker.com/config/containers/logging/)
- [Laravel Debugging](https://laravel.com/docs/logging)
- [Nginx Troubleshooting](https://nginx.org/en/docs/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

---

**Last Updated:** January 8, 2026

**Remember:** Always backup before making major changes!
```bash
./scripts/backup.sh
```
