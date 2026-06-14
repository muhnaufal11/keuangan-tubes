# 🔒 SSL/HTTPS Setup Guide untuk CasaOS

Panduan lengkap setup SSL/HTTPS menggunakan Let's Encrypt dan Certbot di CasaOS.

## 📋 Daftar Isi

1. [Prerequisites](#prerequisites)
2. [Automatic Setup (Recommended)](#automatic-setup-recommended)
3. [Manual Setup](#manual-setup)
4. [Verification](#verification)
5. [Auto-Renewal](#auto-renewal)
6. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Persyaratan
- ✅ Domain sudah pointing ke IP server CasaOS
- ✅ Port 80 accessible dari internet
- ✅ Port 443 akan digunakan untuk HTTPS
- ✅ SSH access ke server CasaOS
- ✅ Docker & Docker-Compose running

### DNS Check
Pastikan domain sudah resolve dengan benar:
```bash
nslookup your-domain.com
# Should show: <casaos-ip>

# Atau
ping your-domain.com
# Should respond dari server IP
```

---

## Automatic Setup (Recommended)

### Method 1: Using Certbot Standalone (Easiest)

#### Step 1: Stop Nginx Container Temporarily
```bash
docker-compose stop nginx
```

#### Step 2: Install Certbot
```bash
sudo apt-get update
sudo apt-get install certbot python3-certbot-nginx -y
```

#### Step 3: Generate Certificate
```bash
sudo certbot certonly --standalone \
  -d your-domain.com \
  -d www.your-domain.com \
  --email your-email@example.com \
  --agree-tos \
  --no-eff-email
```

**Output example:**
```
Successfully received certificate.
Certificate is saved at: /etc/letsencrypt/live/your-domain.com/fullchain.pem
Key is saved at: /etc/letsencrypt/live/your-domain.com/privkey.pem
```

#### Step 4: Copy Certificates to Docker Volume
```bash
# Create SSL directory in app
mkdir -p ~/keuangan-app/docker/ssl

# Copy certificates
sudo cp /etc/letsencrypt/live/your-domain.com/fullchain.pem \
  ~/keuangan-app/docker/ssl/

sudo cp /etc/letsencrypt/live/your-domain.com/privkey.pem \
  ~/keuangan-app/docker/ssl/

# Set permissions
sudo chown -R $USER:$USER ~/keuangan-app/docker/ssl
chmod 600 ~/keuangan-app/docker/ssl/*
```

#### Step 5: Update Nginx Configuration

Edit `docker/nginx.conf`:

```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    
    # Allow Let's Encrypt validation
    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }
    
    # Redirect all other traffic to HTTPS
    location / {
        return 301 https://$server_name$request_uri;
    }
}

# HTTPS Server
server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    root /var/www/html/public;

    # SSL Certificates
    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;

    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    index index.php index.html index.htm;
    charset utf-8;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1000;
    gzip_types text/plain text/css text/xml text/javascript 
               application/x-javascript application/xml+rss 
               application/javascript application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { 
        access_log off; 
        log_not_found off; 
    }

    location = /robots.txt  { 
        access_log off; 
        log_not_found off; 
    }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        include fastcgi_params;
        fastcgi_intercept_errors on;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

#### Step 6: Update docker-compose.yml Ports

```yaml
nginx:
  image: nginx:1.25-alpine
  container_name: keuangan-nginx
  restart: always
  ports:
    - "80:80"      # HTTP (akan di-redirect ke HTTPS)
    - "443:443"    # HTTPS (main)
  volumes:
    - ./:/var/www/html
    - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    - ./docker/ssl:/etc/nginx/ssl  # SSL certificates
  depends_on:
    - app
  networks:
    - keuangan-network
```

#### Step 7: Restart Nginx Container
```bash
docker-compose up -d nginx
```

#### Step 8: Verify HTTPS
```bash
# Test HTTP redirect
curl -I http://your-domain.com
# Should show: HTTP/1.1 301 Moved Permanently
# Location: https://your-domain.com

# Test HTTPS
curl -I https://your-domain.com
# Should show: HTTP/2 200 OK

# Or open di browser:
# https://your-domain.com
```

---

## Manual Setup

Jika automatic setup tidak bekerja, ikuti langkah ini:

### Option A: Using Certbot DNS Challenge

```bash
# Install Certbot
sudo apt-get install certbot -y

# Generate certificate (tanpa web server running)
sudo certbot certonly --manual \
  -d your-domain.com \
  -d www.your-domain.com \
  --email your-email@example.com \
  --agree-tos

# Ikuti instructions untuk verify DNS
# Certbot akan meminta Anda membuat DNS TXT record

# Setelah verified, copy certificates seperti Step 4 di atas
```

### Option B: Using Certbot Webroot

```bash
# Stop nginx temporarily
docker-compose stop nginx

# Create certbot directory
mkdir -p ~/certbot/webroot

# Generate certificate
sudo certbot certonly --webroot \
  -w ~/certbot/webroot \
  -d your-domain.com \
  -d www.your-domain.com \
  --email your-email@example.com \
  --agree-tos
```

---

## Verification

### Check Certificate Details
```bash
# View certificate info
sudo certbot certificates

# Test certificate validity
echo | openssl s_client -servername your-domain.com -connect your-domain.com:443 2>/dev/null | openssl x509 -noout -dates
```

### Test SSL Score
Gunakan [SSL Labs Test](https://www.ssllabs.com/ssltest/):
1. Buka https://www.ssllabs.com/ssltest/
2. Enter: `your-domain.com`
3. Tunggu hasil analisis
4. Target: Grade A atau A+

### Browser Test
```
Buka di browser: https://your-domain.com

Cek:
✓ URL shows HTTPS
✓ Green lock icon visible
✓ Certificate shows your domain
✓ No warnings
```

---

## Auto-Renewal

### Setup Automatic Renewal (Recommended)

Let's Encrypt certificates valid for 90 hari. Setup auto-renewal:

#### Method 1: Using Certbot (Built-in)

```bash
# Enable automatic renewal
sudo certbot renew --dry-run

# Check renewal status
sudo systemctl status certbot.timer

# If timer tidak aktif, enable:
sudo systemctl enable certbot.timer
```

#### Method 2: Manual Cron Job

```bash
# Open crontab
sudo crontab -e

# Add this line (renew setiap hari pukul 3 AM):
0 3 * * * certbot renew --quiet && systemctl reload nginx

# For Docker:
0 3 * * * certbot renew --quiet && docker-compose -f /path/to/docker-compose.yml exec -T nginx nginx -s reload
```

### Test Auto-Renewal
```bash
# Simulate renewal (--dry-run, no actual renewal)
sudo certbot renew --dry-run

# Expected output:
# Cert not yet due for renewal

# Manual renewal (if needed):
sudo certbot renew --force-renewal
```

### After Certificate Renewal

```bash
# Copy updated certificates
sudo cp /etc/letsencrypt/live/your-domain.com/fullchain.pem \
  ~/keuangan-app/docker/ssl/

sudo cp /etc/letsencrypt/live/your-domain.com/privkey.pem \
  ~/keuangan-app/docker/ssl/

# Restart nginx
docker-compose restart nginx
```

---

## Troubleshooting

### Issue: Certificate Generation Failed

```bash
# Check if port 80 is accessible
sudo netstat -tlnp | grep :80

# Check Docker containers
docker-compose ps

# Make sure Nginx is not running on port 80:
docker-compose stop nginx

# Try again:
sudo certbot certonly --standalone -d your-domain.com
```

### Issue: Domain Not Resolving

```bash
# Check DNS resolution
nslookup your-domain.com
dig your-domain.com

# Wait for DNS propagation (dapat sampai 24 jam)
# Gunakan online tools: https://mxtoolbox.com/
```

### Issue: Certificate Not Loading in Nginx

```bash
# Check file permissions
ls -la ~/keuangan-app/docker/ssl/

# Should show:
# -rw------- ... fullchain.pem
# -rw------- ... privkey.pem

# Fix permissions if needed:
sudo chown $USER:$USER ~/keuangan-app/docker/ssl/*
chmod 600 ~/keuangan-app/docker/ssl/*
```

### Issue: Mixed Content (HTTPS but assets not loading)

Update `.env`:
```dotenv
# Change from HTTP to HTTPS
APP_URL=https://your-domain.com
```

Then rebuild:
```bash
docker-compose restart app
```

### Issue: HSTS Header Errors

If you're testing with `--max-age`:
```bash
# Clear HSTS from browser
# 1. Chrome: chrome://net-internals/#hsts
# 2. Search domain and delete
# 3. Clear browser cache
# 4. Try again
```

### Issue: Certificate Authority Unauthorized

```bash
# Update system certificates
sudo update-ca-certificates

# Try renewal:
sudo certbot renew --force-renewal
```

---

## Production Checklist

Sebelum go-live:

- [ ] Certificate generated successfully
- [ ] HTTPS accessible via browser
- [ ] HTTP redirects to HTTPS
- [ ] SSL Labs grade is A or higher
- [ ] No mixed content warnings
- [ ] HSTS header enabled
- [ ] Auto-renewal configured
- [ ] Backup of private key created
- [ ] Team notified about certificate renewal

---

## Advanced: Custom SSL Configuration

### For High-Security Requirements

Update `docker/nginx.conf`:

```nginx
# Additional Security Headers
add_header X-Permitted-Cross-Domain-Policies "none" always;
add_header Permissions-Policy "accelerometer=(), ambient-light-sensor=(), battery=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()" always;

# SSL Session Configuration
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;
ssl_session_tickets off;

# OCSP Stapling (optional)
ssl_stapling on;
ssl_stapling_verify on;
resolver 8.8.8.8 8.8.4.4 valid=300s;
resolver_timeout 5s;
```

---

## Certificate Backup

### Backup Your Certificates
```bash
# Create backup directory
mkdir -p ~/certificates-backup

# Backup certificates
cp /etc/letsencrypt/live/your-domain.com/* ~/certificates-backup/

# Backup Certbot configuration
sudo tar -czf ~/certificates-backup/certbot-backup.tar.gz /etc/letsencrypt/

# Keep safe!
# Transfer to secure location:
# - Cloud backup
# - USB drive
# - Another server
```

---

## Cost & Renewal

- **Cost:** FREE (Let's Encrypt)
- **Duration:** 90 days
- **Auto-Renewal:** Automatic (90 days before expiry, checker runs every 12 hours)
- **Manual Renewal:** `sudo certbot renew`

---

## Resources

- [Let's Encrypt Documentation](https://letsencrypt.org/getting-started/)
- [Certbot Documentation](https://certbot.eff.org/docs/)
- [Nginx SSL Configuration](https://nginx.org/en/docs/http/ngx_http_ssl_module.html)
- [SSL Labs Best Practices](https://github.com/ssllabs/research/wiki/SSL-and-TLS-Deployment-Best-Practices)
- [OWASP SSL/TLS Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Transport_Layer_Protection_Cheat_Sheet.html)

---

**Last Updated:** January 8, 2026

**Next:** After SSL is setup, monitor with:
```bash
docker-compose logs -f nginx
./scripts/health-check.sh
```
