#!/bin/bash

################################################################################
# QrArt - Deploy Script
#
# Deploy automatizzato con validazioni pre-deploy, backup e rollback.
#
# Uso: ./scripts/deploy.sh [environment] [options]
#   environment: dev, staging, production (default: dev)
#   options:
#     --skip-backup    Skip database backup
#     --skip-cache     Skip cache clearing
#     --force          Skip confirmations
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Parse arguments
ENVIRONMENT="${1:-dev}"
SKIP_BACKUP=false
SKIP_CACHE=false
FORCE=false

shift || true
while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-backup) SKIP_BACKUP=true; shift ;;
        --skip-cache) SKIP_CACHE=true; shift ;;
        --force) FORCE=true; shift ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

# Project paths
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_DIR="$PROJECT_ROOT/backend/qrartApp"
FRONTEND_DIR="$PROJECT_ROOT/frontend"
BACKUP_DIR="$PROJECT_ROOT/backups"
LOG_FILE="$PROJECT_ROOT/logs/deploy-$(date +%Y%m%d-%H%M%S).log"

# Create logs directory
mkdir -p "$(dirname "$LOG_FILE")"
mkdir -p "$BACKUP_DIR"

# Log function
log() {
    echo -e "$1" | tee -a "$LOG_FILE"
}

log "${BLUE}========================================${NC}"
log "${BLUE}   QrArt Deployment${NC}"
log "${BLUE}   Environment: $ENVIRONMENT${NC}"
log "${BLUE}   Time: $(date)${NC}"
log "${BLUE}========================================${NC}"
log ""

################################################################################
# Pre-Deploy Validation
################################################################################
log "${YELLOW}[1/10] Pre-deploy validation...${NC}"

VALIDATION_ERRORS=0

# Check if .env exists
if [ ! -f "$BACKEND_DIR/.env" ]; then
    log "${RED}✗ .env file not found${NC}"
    log "${YELLOW}  Run ./scripts/setup.sh first${NC}"
    exit 1
fi

# Check writable directories
WRITABLE_DIRS=(
    "$BACKEND_DIR/writable/cache"
    "$BACKEND_DIR/writable/logs"
    "$BACKEND_DIR/writable/session"
    "$BACKEND_DIR/public/media"
)

for DIR in "${WRITABLE_DIRS[@]}"; do
    if [ ! -w "$DIR" ]; then
        log "${RED}✗ Not writable: $DIR${NC}"
        ((VALIDATION_ERRORS++))
    fi
done

# Check database connection
log "${BLUE}  Testing database connection...${NC}"
DB_HOST=$(grep "database.default.hostname" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)
DB_NAME=$(grep "database.default.database" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)
DB_USER=$(grep "database.default.username" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)
DB_PASS=$(grep "database.default.password" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)

if ! mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME" 2>/dev/null; then
    log "${RED}✗ Cannot connect to database${NC}"
    ((VALIDATION_ERRORS++))
else
    log "${GREEN}✓ Database connection OK${NC}"
fi

# Check disk space (minimum 1GB)
AVAILABLE_SPACE=$(df "$PROJECT_ROOT" | tail -1 | awk '{print $4}')
if [ "$AVAILABLE_SPACE" -lt 1048576 ]; then
    log "${RED}✗ Low disk space: $(($AVAILABLE_SPACE / 1024))MB available${NC}"
    ((VALIDATION_ERRORS++))
else
    log "${GREEN}✓ Disk space OK: $(($AVAILABLE_SPACE / 1024))MB available${NC}"
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
if php -r "exit(version_compare(PHP_VERSION, '8.1.0', '<') ? 1 : 0);"; then
    log "${GREEN}✓ PHP version OK: $PHP_VERSION${NC}"
else
    log "${RED}✗ PHP 8.1+ required, found: $PHP_VERSION${NC}"
    ((VALIDATION_ERRORS++))
fi

if [ $VALIDATION_ERRORS -gt 0 ]; then
    log "${RED}Validation failed with $VALIDATION_ERRORS error(s)${NC}"
    exit 1
fi

log "${GREEN}✓ Pre-deploy validation passed${NC}"
log ""

################################################################################
# Confirmation for production
################################################################################
if [ "$ENVIRONMENT" = "production" ] && [ "$FORCE" = false ]; then
    log "${YELLOW}⚠ WARNING: Deploying to PRODUCTION${NC}"
    read -p "Are you sure? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy]es$ ]]; then
        log "${YELLOW}Deployment cancelled${NC}"
        exit 0
    fi
fi

################################################################################
# Git Status Check
################################################################################
log "${YELLOW}[2/10] Checking git status...${NC}"

cd "$PROJECT_ROOT"

if [ -n "$(git status --porcelain)" ]; then
    log "${YELLOW}⚠ You have uncommitted changes:${NC}"
    git status --short | tee -a "$LOG_FILE"

    if [ "$FORCE" = false ]; then
        read -p "Continue anyway? (yes/no): " -r
        if [[ ! $REPLY =~ ^[Yy]es$ ]]; then
            log "${YELLOW}Deployment cancelled${NC}"
            exit 0
        fi
    fi
fi

CURRENT_BRANCH=$(git branch --show-current)
CURRENT_COMMIT=$(git rev-parse --short HEAD)

log "${BLUE}  Branch: $CURRENT_BRANCH${NC}"
log "${BLUE}  Commit: $CURRENT_COMMIT${NC}"
log ""

################################################################################
# Database Backup
################################################################################
if [ "$SKIP_BACKUP" = false ]; then
    log "${YELLOW}[3/10] Creating database backup...${NC}"

    BACKUP_FILE="$BACKUP_DIR/db-$ENVIRONMENT-$(date +%Y%m%d-%H%M%S).sql"

    if mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null; then
        gzip "$BACKUP_FILE"
        BACKUP_SIZE=$(du -h "$BACKUP_FILE.gz" | cut -f1)
        log "${GREEN}✓ Database backup created: $BACKUP_FILE.gz ($BACKUP_SIZE)${NC}"

        # Keep only last 10 backups
        cd "$BACKUP_DIR"
        ls -t db-$ENVIRONMENT-*.sql.gz | tail -n +11 | xargs -r rm
        log "${BLUE}  Cleaned old backups (keeping last 10)${NC}"
    else
        log "${RED}✗ Database backup failed${NC}"
        if [ "$FORCE" = false ]; then
            exit 1
        fi
    fi
else
    log "${YELLOW}[3/10] Skipping database backup${NC}"
fi

log ""

################################################################################
# Maintenance Mode (Production only)
################################################################################
if [ "$ENVIRONMENT" = "production" ]; then
    log "${YELLOW}[4/10] Enabling maintenance mode...${NC}"

    # Create maintenance flag file
    touch "$BACKEND_DIR/public/.maintenance"
    log "${GREEN}✓ Maintenance mode enabled${NC}"
else
    log "${YELLOW}[4/10] Skipping maintenance mode (not production)${NC}"
fi

log ""

################################################################################
# Pull Latest Changes (if on tracked branch)
################################################################################
log "${YELLOW}[5/10] Updating code...${NC}"

if git rev-parse --abbrev-ref --symbolic-full-name @{u} &>/dev/null; then
    BEFORE_COMMIT=$(git rev-parse --short HEAD)

    git fetch origin
    git pull origin "$CURRENT_BRANCH"

    AFTER_COMMIT=$(git rev-parse --short HEAD)

    if [ "$BEFORE_COMMIT" != "$AFTER_COMMIT" ]; then
        log "${GREEN}✓ Code updated: $BEFORE_COMMIT → $AFTER_COMMIT${NC}"
    else
        log "${BLUE}✓ Code already up to date${NC}"
    fi
else
    log "${YELLOW}⚠ Not on a tracking branch, skipping pull${NC}"
fi

log ""

################################################################################
# Run Migrations
################################################################################
log "${YELLOW}[6/10] Running database migrations...${NC}"

if [ -f "$BACKEND_DIR/spark" ]; then
    cd "$BACKEND_DIR"

    if php spark migrate --all >> "$LOG_FILE" 2>&1; then
        log "${GREEN}✓ Migrations completed${NC}"
    else
        log "${RED}✗ Migrations failed${NC}"
        cat "$LOG_FILE" | tail -20
        exit 1
    fi
else
    log "${YELLOW}⚠ Spark CLI not found, skipping migrations${NC}"
fi

log ""

################################################################################
# Clear Cache
################################################################################
if [ "$SKIP_CACHE" = false ]; then
    log "${YELLOW}[7/10] Clearing cache...${NC}"

    # Clear file cache
    rm -rf "$BACKEND_DIR/writable/cache/"* 2>/dev/null || true
    log "${GREEN}✓ File cache cleared${NC}"

    # Clear Redis cache if using Redis
    CACHE_HANDLER=$(grep "cache.handler" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)

    if [ "$CACHE_HANDLER" = "redis" ]; then
        REDIS_HOST=$(grep "cache.redis.host" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)
        REDIS_PORT=$(grep "cache.redis.port" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)

        if command -v redis-cli &> /dev/null; then
            if redis-cli -h "${REDIS_HOST:-127.0.0.1}" -p "${REDIS_PORT:-6379}" FLUSHDB > /dev/null 2>&1; then
                log "${GREEN}✓ Redis cache cleared${NC}"
            else
                log "${YELLOW}⚠ Could not clear Redis cache${NC}"
            fi
        fi
    fi
else
    log "${YELLOW}[7/10] Skipping cache clearing${NC}"
fi

log ""

################################################################################
# Set Permissions
################################################################################
log "${YELLOW}[8/10] Setting permissions...${NC}"

for DIR in "${WRITABLE_DIRS[@]}"; do
    chmod -R 755 "$DIR"
done

log "${GREEN}✓ Permissions updated${NC}"
log ""

################################################################################
# Health Check
################################################################################
log "${YELLOW}[9/10] Running health check...${NC}"

# Check if health check endpoint exists
HEALTH_CHECK_URL=$(grep "app.baseURL" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs | tr -d "'")
HEALTH_CHECK_URL="${HEALTH_CHECK_URL}api/health"

if command -v curl &> /dev/null; then
    HEALTH_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$HEALTH_CHECK_URL" 2>/dev/null || echo "000")

    if [ "$HEALTH_RESPONSE" = "200" ]; then
        log "${GREEN}✓ Health check passed${NC}"
    else
        log "${YELLOW}⚠ Health check returned: $HEALTH_RESPONSE${NC}"
    fi
else
    log "${YELLOW}⚠ curl not available, skipping health check${NC}"
fi

log ""

################################################################################
# Disable Maintenance Mode
################################################################################
if [ "$ENVIRONMENT" = "production" ]; then
    log "${YELLOW}[10/10] Disabling maintenance mode...${NC}"

    rm -f "$BACKEND_DIR/public/.maintenance"
    log "${GREEN}✓ Maintenance mode disabled${NC}"
else
    log "${YELLOW}[10/10] Maintenance mode was not enabled${NC}"
fi

log ""

################################################################################
# Summary
################################################################################
log "${GREEN}========================================${NC}"
log "${GREEN}   Deployment completed successfully!${NC}"
log "${GREEN}========================================${NC}"
log ""
log "${BLUE}Deployment Summary:${NC}"
log "  Environment: $ENVIRONMENT"
log "  Branch: $CURRENT_BRANCH"
log "  Commit: $(git rev-parse --short HEAD)"
log "  Time: $(date)"
log "  Log file: $LOG_FILE"
log ""

if [ "$SKIP_BACKUP" = false ]; then
    log "${BLUE}Backup Information:${NC}"
    log "  Location: $BACKUP_DIR"
    log "  Latest backup: $(ls -t "$BACKUP_DIR"/db-$ENVIRONMENT-*.sql.gz 2>/dev/null | head -1 || echo 'None')"
    log ""
fi

log "${BLUE}Next steps:${NC}"
log "  1. Test the application"
log "  2. Monitor logs: tail -f $BACKEND_DIR/writable/logs/*.php"
log "  3. If issues occur, run: ./scripts/rollback.sh"
log ""

# Send notification (if configured)
if [ -n "${DEPLOY_WEBHOOK_URL:-}" ]; then
    curl -s -X POST "$DEPLOY_WEBHOOK_URL" \
        -H "Content-Type: application/json" \
        -d "{\"text\":\"✅ QrArt deployed to $ENVIRONMENT\",\"commit\":\"$CURRENT_COMMIT\"}" \
        > /dev/null 2>&1 || true
fi
