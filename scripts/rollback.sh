#!/bin/bash

################################################################################
# QrArt - Rollback Script
#
# Ripristina l'applicazione a una versione precedente.
#
# Uso: ./scripts/rollback.sh [environment] [backup_file]
#   environment: dev, staging, production
#   backup_file: (optional) specific backup file to restore
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Arguments
ENVIRONMENT="${1:-dev}"
BACKUP_FILE="$2"

# Project paths
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_DIR="$PROJECT_ROOT/backend/qrartApp"
BACKUP_DIR="$PROJECT_ROOT/backups"

echo -e "${RED}========================================${NC}"
echo -e "${RED}   QrArt Rollback${NC}"
echo -e "${RED}   Environment: $ENVIRONMENT${NC}"
echo -e "${RED}========================================${NC}"
echo ""

################################################################################
# Confirmation
################################################################################
echo -e "${YELLOW}⚠ WARNING: This will rollback your application${NC}"
echo -e "${YELLOW}  Environment: $ENVIRONMENT${NC}"
read -p "Are you sure? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy]es$ ]]; then
    echo -e "${YELLOW}Rollback cancelled${NC}"
    exit 0
fi

################################################################################
# Enable Maintenance Mode
################################################################################
if [ "$ENVIRONMENT" = "production" ]; then
    echo -e "${YELLOW}[1/4] Enabling maintenance mode...${NC}"
    touch "$BACKEND_DIR/public/.maintenance"
    echo -e "${GREEN}✓ Maintenance mode enabled${NC}"
    echo ""
fi

################################################################################
# Database Restore
################################################################################
echo -e "${YELLOW}[2/4] Restoring database...${NC}"

# If no backup file specified, show list
if [ -z "$BACKUP_FILE" ]; then
    echo -e "${BLUE}Available backups for $ENVIRONMENT:${NC}"
    echo ""

    BACKUPS=($(ls -t "$BACKUP_DIR"/db-$ENVIRONMENT-*.sql.gz 2>/dev/null || true))

    if [ ${#BACKUPS[@]} -eq 0 ]; then
        echo -e "${RED}✗ No backups found for $ENVIRONMENT${NC}"
        exit 1
    fi

    for i in "${!BACKUPS[@]}"; do
        BACKUP="${BACKUPS[$i]}"
        SIZE=$(du -h "$BACKUP" | cut -f1)
        TIMESTAMP=$(basename "$BACKUP" | sed 's/db-'$ENVIRONMENT'-//;s/.sql.gz//')
        echo -e "  ${BLUE}[$i]${NC} $TIMESTAMP ($SIZE)"
    done

    echo ""
    read -p "Select backup number [0]: " -r
    BACKUP_INDEX="${REPLY:-0}"

    if [ "$BACKUP_INDEX" -ge ${#BACKUPS[@]} ]; then
        echo -e "${RED}Invalid selection${NC}"
        exit 1
    fi

    BACKUP_FILE="${BACKUPS[$BACKUP_INDEX]}"
fi

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}✗ Backup file not found: $BACKUP_FILE${NC}"
    exit 1
fi

echo -e "${BLUE}  Using backup: $(basename "$BACKUP_FILE")${NC}"

# Load database credentials
DB_HOST=$(grep "database.default.hostname" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)
DB_NAME=$(grep "database.default.database" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)
DB_USER=$(grep "database.default.username" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)
DB_PASS=$(grep "database.default.password" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)

# Create backup of current state before rollback
CURRENT_BACKUP="$BACKUP_DIR/db-$ENVIRONMENT-before-rollback-$(date +%Y%m%d-%H%M%S).sql.gz"
mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$CURRENT_BACKUP"
echo -e "${BLUE}  Current state backed up to: $(basename "$CURRENT_BACKUP")${NC}"

# Restore database
gunzip -c "$BACKUP_FILE" | mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database restored successfully${NC}"
else
    echo -e "${RED}✗ Database restore failed${NC}"
    exit 1
fi

echo ""

################################################################################
# Clear Cache
################################################################################
echo -e "${YELLOW}[3/4] Clearing cache...${NC}"

rm -rf "$BACKEND_DIR/writable/cache/"* 2>/dev/null || true
echo -e "${GREEN}✓ Cache cleared${NC}"

echo ""

################################################################################
# Disable Maintenance Mode
################################################################################
if [ "$ENVIRONMENT" = "production" ]; then
    echo -e "${YELLOW}[4/4] Disabling maintenance mode...${NC}"
    rm -f "$BACKEND_DIR/public/.maintenance"
    echo -e "${GREEN}✓ Maintenance mode disabled${NC}"
fi

echo ""

################################################################################
# Summary
################################################################################
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   Rollback completed!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Rollback Information:${NC}"
echo "  Restored from: $(basename "$BACKUP_FILE")"
echo "  Current state saved to: $(basename "$CURRENT_BACKUP")"
echo ""
echo -e "${YELLOW}⚠ IMPORTANT:${NC}"
echo "  - Test the application thoroughly"
echo "  - Check application logs"
echo "  - Consider rolling back code if needed: git reset --hard <commit>"
echo ""
