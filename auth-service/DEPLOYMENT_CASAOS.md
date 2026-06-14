# Panduan Deployment Aplikasi Laravel ke CasaOS

## Daftar Isi
1. [Persiapan](#persiapan)
2. [Setup Server CasaOS](#setup-server-casaos)
3. [Konfigurasi Aplikasi](#konfigurasi-aplikasi)
4. [Deploy dengan Docker](#deploy-dengan-docker)
5. [Reverse Proxy Setup](#reverse-proxy-setup)
6. [Maintenance](#maintenance)

---

## Persiapan

### Persyaratan
- CasaOS sudah terinstall di server Anda
- SSH access ke server CasaOS
- Git installed di server
- Docker dan Docker Compose sudah jalan di CasaOS

### Pastikan Aplikasi Siap
```bash
# Di Windows (local development):
composer install
npm install
npm run build
```

---

## Setup Server CasaOS

### 1. Akses Server CasaOS
```bash
# SSH ke server CasaOS
ssh casaos_username@casaos_ip
```

### 2. Persiapkan Direktori
```bash
# Buat direktori untuk aplikasi
mkdir -p ~/apps/keuangan-app
cd ~/apps/keuangan-app

# Clone atau upload aplikasi
# Opsi 1: Clone dari Git (jika tersedia)
git clone <your-repo-url> .

# Opsi 2: Upload dari Windows (gunakan SCP atau File Manager CasaOS)
```

### 3. Install Dependencies di Server
```bash
cd ~/apps/keuangan-app

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies dan build assets
npm install
npm run build
```

---

## Konfigurasi Aplikasi

### 1. Setup Environment File
```bash
# Copy file environment
cp .env.example .env

# Generate APP_KEY
php artisan key:generate
```

### 2. Konfigurasi .env untuk Production
Edit file `.env` di server:
```bash
nano .env
```

Update konfigurasi berikut:
```dotenv
# Production settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com  # Sesuaikan dengan domain Anda

# Database (jika menggunakan container database)
DB_CONNECTION=mysql
DB_HOST=mysql  # Nama service di docker-compose
DB_PORT=3306
DB_DATABASE=keuangan_laravel
DB_USERNAME=laravel
DB_PASSWORD=strong_password_here

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=notice

# Mail (optional)
MAIL_MAILER=smtp
MAIL_HOST=your-mail-server
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
```

### 2. Setup Database
```bash
# Migration database
php artisan migrate --force

# (Optional) Seed database dengan data default
php artisan db:seed
```

### 3. Setup File Permissions
```bash
# Set file permissions
chmod -R 755 storage bootstrap/cache
chmod -R 777 storage bootstrap/cache
```

---

## Deploy dengan Docker

Jika Anda ingin menggunakan Docker (Recommended):

### 1. Buat Dockerfile
Buat file `Dockerfile` di root aplikasi:

```dockerfile
FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    mariadb-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Copy environment file
COPY .env.production .env

# Generate key
RUN php artisan key:generate

# Set permissions
RUN chmod -R 755 storage bootstrap/cache
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

### 2. Buat docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: keuangan-app
    restart: always
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
      - ./storage:/var/www/html/storage
    networks:
      - app-network
    environment:
      DB_HOST: mysql
      DB_PORT: 3306
      DB_DATABASE: ${DB_DATABASE:-keuangan_laravel}
      DB_USERNAME: ${DB_USERNAME:-laravel}
      DB_PASSWORD: ${DB_PASSWORD:-laravel}

  mysql:
    image: mysql:8.0
    container_name: keuangan-mysql
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: keuangan_laravel
      MYSQL_USER: laravel
      MYSQL_PASSWORD: laravel
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - app-network
    ports:
      - "3306:3306"

  nginx:
    image: nginx:latest
    container_name: keuangan-nginx
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www/html
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - app
    networks:
      - app-network

volumes:
  mysql_data:

networks:
  app-network:
    driver: bridge
```

### 3. Buat nginx.conf
```nginx
server {
    listen 80;
    server_name _;
    root /var/www/html/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4. Deploy dengan Docker
```bash
cd ~/apps/keuangan-app

# Build dan start container
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Setup symlink untuk storage
docker-compose exec app php artisan storage:link
```

---

## Reverse Proxy Setup (Jika menggunakan Port lain)

Jika ingin mengakses aplikasi melalui reverse proxy di CasaOS:

### 1. Akses CasaOS Control Panel
- Buka browser: `http://casaos-ip:8089`
- Login dengan akun Anda
- Navigasi ke Network atau Ports

### 2. Setup Port Forwarding
- Forward port 80/443 ke port yang digunakan aplikasi

### 3. Setup SSL (Optional tapi Recommended)
```bash
# Menggunakan Let's Encrypt dengan Certbot
sudo apt-get install certbot python3-certbot-nginx

# Generate certificate
sudo certbot certonly --standalone -d your-domain.com

# Update nginx.conf dengan SSL
```

---

## Cara Alternative: Deploy Tanpa Docker

Jika server CasaOS tidak menggunakan Docker:

### 1. Install Dependencies di Server
```bash
# Install PHP dan dependencies
sudo apt-get update
sudo apt-get install php8.1-fpm php8.1-mysql php8.1-gd php8.1-curl php8.1-xml nginx mysql-server

# Install Composer dan Node
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install nodejs
```

### 2. Clone Aplikasi
```bash
cd /var/www/
git clone <your-repo-url> keuangan-app
cd keuangan-app

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 3. Configure Nginx
Buat file `/etc/nginx/sites-available/keuangan-app`:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/keuangan-app/public;

    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### 4. Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/keuangan-app /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 5. Setup Database & Migrations
```bash
# Buat database
mysql -u root -p
CREATE DATABASE keuangan_laravel;
CREATE USER 'laravel'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON keuangan_laravel.* TO 'laravel'@'localhost';
FLUSH PRIVILEGES;

# Run migrations
cd /var/www/keuangan-app
php artisan migrate --force
```

---

## Maintenance

### Regular Tasks

#### 1. Update Aplikasi
```bash
# Jika menggunakan Git
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Jika menggunakan Docker
docker-compose down
docker-compose up -d
```

#### 2. Backup Database
```bash
# Manual backup
mysqldump -u laravel -p keuangan_laravel > backup_$(date +%Y%m%d).sql

# Jika menggunakan Docker
docker-compose exec mysql mysqldump -u laravel -p keuangan_laravel > backup.sql
```

#### 3. Monitor Logs
```bash
# Jika menggunakan Docker
docker-compose logs -f app

# Tanpa Docker
tail -f storage/logs/laravel.log
```

#### 4. Cleanup
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Jika menggunakan Docker
docker-compose exec app php artisan cache:clear
```

---

## Troubleshooting

### Issue: 500 Internal Server Error
```bash
# Check file permissions
chmod -R 755 storage bootstrap/cache
chmod -R 777 storage bootstrap/cache

# Check logs
tail -f storage/logs/laravel.log
```

### Issue: Database Connection Failed
```bash
# Verifikasi kredensial database
php artisan tinker
# Test di console
```

### Issue: Assets tidak dimuat
```bash
# Pastikan build assets sudah dijalankan
npm run build

# Check path di .env
# Pastikan APP_URL sesuai
```

### Issue: Permissions Error
```bash
# Set proper permissions
sudo chown -R www-data:www-data /var/www/keuangan-app
chmod -R 755 /var/www/keuangan-app
chmod -R 777 /var/www/keuangan-app/storage
chmod -R 777 /var/www/keuangan-app/bootstrap/cache
```

---

## SSL Setup (HTTPS)

### Dengan Docker (Recommended)
```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Generate certificate
sudo certbot certonly --standalone -d your-domain.com

# Update nginx.conf untuk SSL (lihat nginx.conf di bawah)
```

### nginx.conf dengan SSL
```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    # Redirect HTTP ke HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Checklist Final

Sebelum production:
- [ ] .env sudah dikonfigurasi dengan benar
- [ ] Database sudah di-migrate
- [ ] Storage permissions sudah di-set
- [ ] Assets sudah di-build (npm run build)
- [ ] SSL/HTTPS sudah setup
- [ ] Backup database sudah dibuat
- [ ] Monitoring/logging sudah aktif
- [ ] Test aplikasi berjalan dengan baik

---

## Support & Resources

- [Laravel Documentation](https://laravel.com/docs)
- [CasaOS Documentation](https://docs.casaos.io)
- [Docker Documentation](https://docs.docker.com)
- [Nginx Configuration](https://nginx.org/en/docs/)

**Last Updated:** January 8, 2026
