# QrArt Deployment Scripts

This directory contains automated scripts for deploying and managing QrArt.

## Available Scripts

| Script | Purpose | Usage |
|--------|---------|-------|
| **setup.sh** | Initial environment setup | `./setup.sh [environment]` |
| **deploy.sh** | Deploy application (traditional) | `./deploy.sh [environment] [options]` |
| **docker-deploy.sh** | Deploy with Docker | `./docker-deploy.sh [environment] [options]` |
| **rollback.sh** | Rollback to previous version | `./rollback.sh [environment] [backup_file]` |

## Quick Start

### First Time Setup

```bash
# Traditional deployment
./scripts/setup.sh production

# Docker deployment
cp .env.docker.example .env.docker
./scripts/docker-deploy.sh production --build
```

### Regular Deployment

```bash
# Traditional
./scripts/deploy.sh production

# Docker
./scripts/docker-deploy.sh production
```

### Rollback

```bash
./scripts/rollback.sh production
```

## Environment Options

- **dev** / **development** - Development environment
- **staging** - Staging environment
- **production** - Production environment

## Script Options

### setup.sh
```bash
./setup.sh [environment]

Examples:
  ./setup.sh dev
  ./setup.sh production
```

### deploy.sh
```bash
./deploy.sh [environment] [options]

Options:
  --skip-backup    Skip database backup
  --skip-cache     Skip cache clearing
  --force          Skip confirmations

Examples:
  ./deploy.sh production
  ./deploy.sh staging --skip-backup
  ./deploy.sh dev --force
```

### docker-deploy.sh
```bash
./docker-deploy.sh [environment] [options]

Options:
  --build         Force rebuild images
  --no-cache      Build without cache
  --pull          Pull latest base images

Examples:
  ./docker-deploy.sh production --build
  ./docker-deploy.sh dev --no-cache --pull
```

### rollback.sh
```bash
./rollback.sh [environment] [backup_file]

Examples:
  ./rollback.sh production
  ./rollback.sh staging backups/db-staging-20250101-120000.sql.gz
```

## Features

### Pre-Deploy Validation
- ✅ System requirements check
- ✅ Database connectivity test
- ✅ Disk space verification
- ✅ PHP version validation
- ✅ Writable directories check

### Deployment Process
- ✅ Automatic database backup
- ✅ Maintenance mode (production)
- ✅ Git pull latest changes
- ✅ Database migrations
- ✅ Cache clearing
- ✅ Permission setting
- ✅ Health checks
- ✅ Rollback capability

### Safety Features
- ✅ Production confirmation prompt
- ✅ Automatic backups before changes
- ✅ Transaction-based deployments
- ✅ Detailed logging
- ✅ Error handling with exit codes

## Logs

All deployments are logged to:
```
logs/deploy-YYYYMMDD-HHMMSS.log
```

View logs:
```bash
tail -f logs/deploy-*.log
```

## Backups

Backups are stored in:
```
backups/db-[environment]-YYYYMMDD-HHMMSS.sql.gz
```

The system automatically keeps the last 10 backups per environment.

## Troubleshooting

### Script Permission Denied
```bash
chmod +x scripts/*.sh
```

### Database Connection Failed
```bash
# Check .env configuration
cat backend/qrartApp/.env | grep database

# Test MySQL connection
mysql -h localhost -u qrart_user -p qrart
```

### Docker Issues
```bash
# Check Docker status
docker --version
docker-compose --version

# View logs
docker-compose logs -f app
```

## Documentation

For detailed deployment instructions, see:
- **DEPLOYMENT.md** - Complete deployment guide
- **SPRINT1_OPTIMIZATIONS.md** - Performance optimizations

## Support

If you encounter issues:
1. Check script logs in `/logs`
2. Review application logs in `backend/qrartApp/writable/logs/`
3. Run health check: `curl http://localhost/api/health/detailed`
4. See DEPLOYMENT.md troubleshooting section
