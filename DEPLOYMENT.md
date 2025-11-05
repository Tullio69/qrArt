# QrArt - Deployment Guide

**Complete deployment guide for QrArt application**

---

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Quick Start](#quick-start)
4. [Deployment Methods](#deployment-methods)
5. [Environment Configuration](#environment-configuration)
6. [Troubleshooting](#troubleshooting)
7. [Monitoring & Maintenance](#monitoring--maintenance)
8. [Rollback Procedures](#rollback-procedures)

---

## Overview

QrArt supports two deployment methods:

1. **Traditional Deployment** - Direct server deployment with Apache/Nginx
2. **Docker Deployment** - Containerized deployment (Recommended)

### Architecture

```
┌─────────────────────────────────────┐
│         Load Balancer (Optional)     │
└──────────────┬──────────────────────┘
               │
       ┌───────┴────────┐
       │   Web Server    │
       │  (Apache/Nginx) │
       └───────┬─────────┘
               │
    ┌──────────┼──────────┐
    │          │          │
┌───▼───┐  ┌───▼───┐  ┌───▼───┐
│  PHP  │  │ MySQL │  │ Redis │
│  App  │  │   DB  │  │ Cache │
└───────┘  └───────┘  └───────┘
```

---

## Prerequisites

### System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| **OS** | Ubuntu 20.04+ | Ubuntu 22.04 LTS |
| **PHP** | 8.1 | 8.2+ |
| **MySQL** | 8.0 | 8.0+ |
| **Memory** | 2GB RAM | 4GB+ RAM |
| **Disk** | 10GB | 20GB+ SSD |
| **CPU** | 1 core | 2+ cores |

### Required Software

#### Traditional Deployment
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y \
    php8.1 \
    php8.1-cli \
    php8.1-mysql \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-curl \
    php8.1-zip \
    php8.1-gd \
    php8.1-intl \
    php8.1-redis \
    mysql-server \
    apache2 \
    libapache2-mod-php8.1 \
    redis-server \
    git
```

#### Docker Deployment
```bash
# Docker & Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Docker Compose
sudo apt install docker-compose
```

---

## Quick Start

### Method 1: Docker Deployment (Recommended)

```bash
# 1. Clone repository
git clone <repository-url>
cd qrArt

# 2. Configure environment
cp .env.docker.example .env.docker
nano .env.docker  # Edit with your settings

# 3. Deploy
chmod +x scripts/docker-deploy.sh
./scripts/docker-deploy.sh production

# 4. Access application
open http://localhost:8080
```

### Method 2: Traditional Deployment

```bash
# 1. Clone repository
git clone <repository-url>
cd qrArt

# 2. Run setup
chmod +x scripts/setup.sh
./scripts/setup.sh production

# 3. Configure web server (see Apache/Nginx section)

# 4. Deploy
chmod +x scripts/deploy.sh
./scripts/deploy.sh production
```

---

## Deployment Methods

### Docker Deployment (Detailed)

#### 1. Initial Setup

```bash
# Create environment file
cp .env.docker.example .env.docker
```

Edit `.env.docker`:

```ini
# Database
DB_DATABASE=qrart
DB_USERNAME=qrart_user
DB_PASSWORD=your_secure_password
DB_ROOT_PASSWORD=your_root_password

# Redis
REDIS_PASSWORD=your_redis_password

# Application
APP_PORT=8080
CI_ENVIRONMENT=production
```

#### 2. First Deployment

```bash
# Build and start services
./scripts/docker-deploy.sh production --build

# Check status
docker-compose ps

# View logs
docker-compose logs -f app
```

#### 3. Subsequent Deployments

```bash
# Pull latest code
git pull origin main

# Deploy
./scripts/docker-deploy.sh production
```

#### Docker Commands Reference

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# Restart service
docker-compose restart app

# View logs
docker-compose logs -f app

# Shell access
docker-compose exec app bash

# Database shell
docker-compose exec db mysql -u root -p

# Clear cache
docker-compose exec app rm -rf backend/qrartApp/writable/cache/*

# Run migrations
docker-compose exec app php backend/qrartApp/spark migrate

# Backup database
docker-compose exec db mysqldump -u root -p qrart > backup.sql
```

---

### Traditional Deployment (Detailed)

#### 1. Initial Setup

```bash
# Clone and setup
git clone <repository-url>
cd qrArt
chmod +x scripts/*.sh

# Run setup script
./scripts/setup.sh production
```

The setup script will:
- ✅ Check system requirements
- ✅ Create directories and set permissions
- ✅ Configure environment (.env)
- ✅ Setup database
- ✅ Run migrations
- ✅ Configure cache

#### 2. Web Server Configuration

**Apache (Recommended)**

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/qrArt/backend/qrartApp/public

    <Directory /path/to/qrArt/backend/qrartApp/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Enable rewrite module
    RewriteEngine On

    # Maintenance mode
    RewriteCond %{DOCUMENT_ROOT}/.maintenance -f
    RewriteRule ^(.*)$ /maintenance.html [R=503,L]

    ErrorLog ${APACHE_LOG_DIR}/qrart-error.log
    CustomLog ${APACHE_LOG_DIR}/qrart-access.log combined
</VirtualHost>
```

```bash
# Enable site
sudo a2enmod rewrite
sudo a2ensite qrart.conf
sudo systemctl reload apache2
```

**Nginx**

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/qrArt/backend/qrartApp/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Maintenance mode
    if (-f $document_root/.maintenance) {
        return 503;
    }

    error_page 503 /maintenance.html;
    location = /maintenance.html {
        root /path/to/qrArt/backend/qrartApp/public;
    }
}
```

#### 3. Deployment

```bash
# Deploy application
./scripts/deploy.sh production

# The script will:
# ✅ Validate pre-deploy checks
# ✅ Backup database
# ✅ Enable maintenance mode (production)
# ✅ Pull latest code
# ✅ Run migrations
# ✅ Clear cache
# ✅ Set permissions
# ✅ Health check
# ✅ Disable maintenance mode
```

---

## Environment Configuration

### Backend Configuration (.env)

```ini
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------
CI_ENVIRONMENT = production

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------
app.baseURL = 'https://yourdomain.com/'
app.indexPage = ''

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------
database.default.hostname = localhost
database.default.database = qrart
database.default.username = qrart_user
database.default.password = YOUR_SECURE_PASSWORD
database.default.DBDriver = MySQLi
database.default.port = 3306

#--------------------------------------------------------------------
# CACHE
#--------------------------------------------------------------------
cache.handler = redis
cache.redis.host = 127.0.0.1
cache.redis.port = 6379
cache.redis.password = YOUR_REDIS_PASSWORD
cache.redis.database = 0

#--------------------------------------------------------------------
# SECURITY
#--------------------------------------------------------------------
# encryption.key = YOUR_ENCRYPTION_KEY
```

### Environment-Specific Settings

**Development**
```ini
CI_ENVIRONMENT = development
database.default.DBDebug = true
cache.handler = file
```

**Staging**
```ini
CI_ENVIRONMENT = staging
database.default.DBDebug = false
cache.handler = redis
```

**Production**
```ini
CI_ENVIRONMENT = production
database.default.DBDebug = false
cache.handler = redis
app.forceGlobalSecureRequests = true
```

---

## Troubleshooting

### Common Issues

#### Issue: Database Connection Failed

**Symptoms**: "Cannot connect to database" error

**Solutions**:
```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -h localhost -u qrart_user -p qrart

# Check credentials in .env
cat backend/qrartApp/.env | grep database

# Check MySQL logs
sudo tail -f /var/log/mysql/error.log
```

#### Issue: Permission Denied Errors

**Symptoms**: Cannot write to cache/logs/uploads

**Solutions**:
```bash
# Fix permissions
sudo chown -R www-data:www-data backend/qrartApp/writable
sudo chown -R www-data:www-data backend/qrartApp/public/media
sudo chmod -R 755 backend/qrartApp/writable
sudo chmod -R 755 backend/qrartApp/public/media

# Verify
ls -la backend/qrartApp/writable
```

#### Issue: 500 Internal Server Error

**Symptoms**: White screen or 500 error

**Solutions**:
```bash
# Check PHP error logs
sudo tail -f /var/log/apache2/error.log
# or
sudo tail -f /var/log/nginx/error.log

# Check application logs
tail -f backend/qrartApp/writable/logs/*.php

# Enable display_errors temporarily (dev only!)
# In .env: CI_ENVIRONMENT = development
```

#### Issue: Cache Not Working

**Symptoms**: Slow performance, cache miss logs

**Solutions**:
```bash
# Check Redis is running
sudo systemctl status redis

# Test Redis connection
redis-cli ping

# Check Redis config in .env
cat backend/qrartApp/.env | grep cache

# Clear cache manually
rm -rf backend/qrartApp/writable/cache/*
redis-cli FLUSHDB
```

#### Issue: Docker Services Won't Start

**Symptoms**: docker-compose up fails

**Solutions**:
```bash
# Check Docker daemon
sudo systemctl status docker

# View container logs
docker-compose logs app
docker-compose logs db

# Rebuild images
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Check port conflicts
sudo netstat -tlnp | grep :8080
```

---

## Monitoring & Maintenance

### Health Checks

```bash
# Basic health check
curl http://localhost:8080/api/health

# Detailed health check (includes DB, cache, filesystem)
curl http://localhost:8080/api/health/detailed | jq

# Kubernetes-style probes
curl http://localhost:8080/api/health/live   # Liveness
curl http://localhost:8080/api/health/ready  # Readiness
```

### Monitoring Endpoints

| Endpoint | Purpose | Response |
|----------|---------|----------|
| `/api/health` | Basic health | `{"status":"healthy"}` |
| `/api/health/detailed` | Full diagnostics | Database, cache, disk info |
| `/api/health/live` | Liveness probe | Process alive |
| `/api/health/ready` | Readiness probe | Ready for traffic |

### Log Monitoring

```bash
# Application logs
tail -f backend/qrartApp/writable/logs/*.php

# Web server logs (Apache)
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/apache2/access.log

# Web server logs (Nginx)
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# Docker logs
docker-compose logs -f app
docker-compose logs -f db
```

### Performance Monitoring

```bash
# Database performance
mysql -u root -p -e "SHOW PROCESSLIST;"
mysql -u root -p -e "SHOW ENGINE INNODB STATUS\G"

# Cache hit ratio (Redis)
redis-cli INFO stats | grep keyspace_hits
redis-cli INFO stats | grep keyspace_misses

# System resources
top
htop
df -h
free -h
```

### Automated Monitoring (Optional)

**Setup Cron Job for Health Checks**

```bash
# Add to crontab
*/5 * * * * curl -sf http://localhost:8080/api/health || echo "Health check failed!" | mail -s "QrArt Alert" admin@yourdomain.com
```

---

## Rollback Procedures

### Quick Rollback

```bash
# Rollback to previous database backup
./scripts/rollback.sh production

# The script will:
# ✅ Show available backups
# ✅ Enable maintenance mode
# ✅ Backup current state
# ✅ Restore selected backup
# ✅ Clear cache
# ✅ Disable maintenance mode
```

### Manual Rollback

#### 1. Code Rollback (Git)

```bash
# View recent commits
git log --oneline -10

# Rollback to specific commit
git reset --hard <commit-hash>

# Or revert last deployment
git revert HEAD
```

#### 2. Database Rollback

```bash
# List backups
ls -lh backups/

# Restore backup
gunzip -c backups/db-production-YYYYMMDD-HHMMSS.sql.gz | mysql -u root -p qrart
```

#### 3. Clear Cache After Rollback

```bash
# File cache
rm -rf backend/qrartApp/writable/cache/*

# Redis cache
redis-cli FLUSHDB

# Or use helper
docker-compose exec app php backend/qrartApp/spark cache:clear
```

---

## Best Practices

### Security

1. ✅ **Always** use HTTPS in production
2. ✅ **Never** commit `.env` files
3. ✅ Use strong passwords (20+ characters)
4. ✅ Keep software updated regularly
5. ✅ Restrict database access by IP
6. ✅ Enable firewall (UFW/iptables)
7. ✅ Regular security audits

### Performance

1. ✅ Use Redis for caching (not file cache)
2. ✅ Enable OPcache in production
3. ✅ Use persistent database connections
4. ✅ Enable MySQL query cache
5. ✅ Optimize images before upload
6. ✅ Use CDN for static assets
7. ✅ Monitor slow queries

### Reliability

1. ✅ Automated daily backups
2. ✅ Test rollback procedures regularly
3. ✅ Monitor health endpoints
4. ✅ Set up log aggregation
5. ✅ Use load balancer for high availability
6. ✅ Database replication for redundancy
7. ✅ Document incident response procedures

---

## Backup & Recovery

### Automated Backups

**Setup Daily Backups (Cron)**

```bash
# Create backup script
cat > /usr/local/bin/qrart-backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/path/to/qrArt/backups"
DATE=$(date +%Y%m%d-%H%M%S)
mysqldump -u qrart_user -p'password' qrart | gzip > "$BACKUP_DIR/db-auto-$DATE.sql.gz"
# Keep last 30 days
find "$BACKUP_DIR" -name "db-auto-*.sql.gz" -mtime +30 -delete
EOF

chmod +x /usr/local/bin/qrart-backup.sh

# Add to crontab (daily at 2 AM)
0 2 * * * /usr/local/bin/qrart-backup.sh
```

### Backup Verification

```bash
# Test restore in separate database
gunzip -c backup.sql.gz | mysql -u root -p test_qrart

# Verify data
mysql -u root -p test_qrart -e "SELECT COUNT(*) FROM content;"
```

---

## Scaling & High Availability

### Horizontal Scaling

```
                  ┌─────────────┐
                  │ Load Balancer│
                  └──────┬───────┘
                         │
         ┌───────────────┼───────────────┐
         │               │               │
    ┌────▼────┐     ┌────▼────┐     ┌────▼────┐
    │ App #1  │     │ App #2  │     │ App #3  │
    └────┬────┘     └────┬────┘     └────┬────┘
         │               │               │
         └───────────────┼───────────────┘
                         │
              ┌──────────┼──────────┐
              │          │          │
         ┌────▼────┐ ┌───▼───┐ ┌───▼────┐
         │ MySQL   │ │ Redis │ │  NFS   │
         │ Master  │ │Cluster│ │ Share  │
         └─────────┘ └───────┘ └────────┘
```

### Load Balancer Config (Nginx)

```nginx
upstream qrart_backend {
    server app1.local:8080 weight=3;
    server app2.local:8080 weight=2;
    server app3.local:8080 weight=1;
}

server {
    listen 80;
    server_name qrart.com;

    location / {
        proxy_pass http://qrart_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

---

## Support & Resources

### Documentation
- Project Documentation: `/docs`
- API Documentation: `/api/docs`
- Sprint 1 Optimizations: `SPRINT1_OPTIMIZATIONS.md`

### Useful Links
- CodeIgniter 4 Docs: https://codeigniter.com/user_guide/
- Docker Docs: https://docs.docker.com/
- PHP Best Practices: https://www.php-fig.org/

### Getting Help

1. Check logs: `backend/qrartApp/writable/logs/`
2. Run health check: `curl http://localhost/api/health/detailed`
3. Review this documentation
4. Check GitHub Issues

---

**Last Updated**: 2025-11-05
**Version**: 2.0
**Author**: QrArt Development Team
