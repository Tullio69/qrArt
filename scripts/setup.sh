#!/bin/bash

################################################################################
# QrArt - Setup Script
#
# Configura automaticamente l'ambiente per il deploy del progetto QrArt.
# Esegue controlli preliminari, installa dipendenze e configura l'applicazione.
#
# Uso: ./scripts/setup.sh [environment]
#   environment: dev, staging, production (default: dev)
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default environment
ENVIRONMENT="${1:-dev}"

# Project paths
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_DIR="$PROJECT_ROOT/backend/qrartApp"
FRONTEND_DIR="$PROJECT_ROOT/frontend"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}   QrArt Setup Script${NC}"
echo -e "${BLUE}   Environment: $ENVIRONMENT${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

################################################################################
# Step 1: System Requirements Check
################################################################################
echo -e "${YELLOW}[1/8] Checking system requirements...${NC}"

check_command() {
    if ! command -v "$1" &> /dev/null; then
        echo -e "${RED}✗ $1 is not installed${NC}"
        return 1
    else
        echo -e "${GREEN}✓ $1 is installed${NC}"
        return 0
    fi
}

REQUIREMENTS_MET=true

# PHP check
if check_command php; then
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    echo -e "  ${BLUE}PHP version: $PHP_VERSION${NC}"

    # Check if PHP >= 8.1
    if php -r "exit(version_compare(PHP_VERSION, '8.1.0', '<') ? 1 : 0);"; then
        echo -e "${RED}✗ PHP 8.1+ is required${NC}"
        REQUIREMENTS_MET=false
    fi
else
    REQUIREMENTS_MET=false
fi

# MySQL check
if check_command mysql; then
    MYSQL_VERSION=$(mysql --version | awk '{print $5}' | sed 's/,//')
    echo -e "  ${BLUE}MySQL version: $MYSQL_VERSION${NC}"
else
    REQUIREMENTS_MET=false
fi

# Git check
check_command git || REQUIREMENTS_MET=false

# Optional: Composer
if check_command composer; then
    COMPOSER_VERSION=$(composer --version | awk '{print $3}')
    echo -e "  ${BLUE}Composer version: $COMPOSER_VERSION${NC}"
fi

if [ "$REQUIREMENTS_MET" = false ]; then
    echo -e "${RED}Some requirements are not met. Please install missing dependencies.${NC}"
    exit 1
fi

echo ""

################################################################################
# Step 2: Directory Permissions
################################################################################
echo -e "${YELLOW}[2/8] Setting up directory permissions...${NC}"

# Backend writable directories
WRITABLE_DIRS=(
    "$BACKEND_DIR/writable/cache"
    "$BACKEND_DIR/writable/logs"
    "$BACKEND_DIR/writable/session"
    "$BACKEND_DIR/writable/uploads"
    "$BACKEND_DIR/public/media"
)

for DIR in "${WRITABLE_DIRS[@]}"; do
    if [ ! -d "$DIR" ]; then
        mkdir -p "$DIR"
        echo -e "${GREEN}✓ Created: $DIR${NC}"
    fi

    chmod -R 755 "$DIR"
    echo -e "${GREEN}✓ Set permissions: $DIR${NC}"
done

echo ""

################################################################################
# Step 3: Environment Configuration
################################################################################
echo -e "${YELLOW}[3/8] Configuring environment files...${NC}"

# Backend .env
if [ ! -f "$BACKEND_DIR/.env" ]; then
    if [ -f "$BACKEND_DIR/.env.example" ]; then
        cp "$BACKEND_DIR/.env.example" "$BACKEND_DIR/.env"
        echo -e "${GREEN}✓ Created .env from .env.example${NC}"

        # Set environment
        sed -i "s/CI_ENVIRONMENT = development/CI_ENVIRONMENT = $ENVIRONMENT/" "$BACKEND_DIR/.env"
        echo -e "${BLUE}  Set CI_ENVIRONMENT to: $ENVIRONMENT${NC}"

        echo -e "${YELLOW}  ⚠ Please edit $BACKEND_DIR/.env and configure:${NC}"
        echo -e "${YELLOW}    - database.default.password${NC}"
        echo -e "${YELLOW}    - cache settings (if using Redis)${NC}"
        echo ""
        read -p "Press Enter after configuring .env file..."
    else
        echo -e "${RED}✗ .env.example not found${NC}"
        exit 1
    fi
else
    echo -e "${BLUE}✓ .env already exists${NC}"
fi

echo ""

################################################################################
# Step 4: Database Setup
################################################################################
echo -e "${YELLOW}[4/8] Setting up database...${NC}"

# Load database credentials from .env
DB_HOST=$(grep "database.default.hostname" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)
DB_NAME=$(grep "database.default.database" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)
DB_USER=$(grep "database.default.username" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)
DB_PASS=$(grep "database.default.password" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)

echo -e "${BLUE}Database: $DB_NAME @ $DB_HOST${NC}"

# Test database connection
if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME" 2>/dev/null; then
    echo -e "${GREEN}✓ Database connection successful${NC}"
else
    echo -e "${RED}✗ Cannot connect to database${NC}"
    echo -e "${YELLOW}  Trying to create database...${NC}"

    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null; then
        echo -e "${GREEN}✓ Database created successfully${NC}"
    else
        echo -e "${RED}✗ Failed to create database. Please create it manually:${NC}"
        echo -e "${RED}  CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;${NC}"
        exit 1
    fi
fi

echo ""

################################################################################
# Step 5: Run Migrations
################################################################################
echo -e "${YELLOW}[5/8] Running database migrations...${NC}"

# Check if spark exists
if [ -f "$BACKEND_DIR/spark" ]; then
    cd "$BACKEND_DIR"
    php spark migrate --all
    echo -e "${GREEN}✓ Migrations completed${NC}"
else
    echo -e "${YELLOW}⚠ Spark CLI not found. You may need to run migrations manually.${NC}"
fi

echo ""

################################################################################
# Step 6: Cache Setup
################################################################################
echo -e "${YELLOW}[6/8] Setting up cache...${NC}"

CACHE_HANDLER=$(grep "cache.handler" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)

if [ -z "$CACHE_HANDLER" ]; then
    CACHE_HANDLER="file"
fi

echo -e "${BLUE}Cache handler: $CACHE_HANDLER${NC}"

if [ "$CACHE_HANDLER" = "redis" ]; then
    if check_command redis-cli; then
        REDIS_HOST=$(grep "cache.redis.host" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)
        REDIS_PORT=$(grep "cache.redis.port" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)

        if [ -z "$REDIS_HOST" ]; then REDIS_HOST="127.0.0.1"; fi
        if [ -z "$REDIS_PORT" ]; then REDIS_PORT="6379"; fi

        if redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" ping > /dev/null 2>&1; then
            echo -e "${GREEN}✓ Redis connection successful${NC}"
        else
            echo -e "${RED}✗ Cannot connect to Redis${NC}"
            echo -e "${YELLOW}  Falling back to file cache...${NC}"
            sed -i "s/cache.handler = redis/cache.handler = file/" "$BACKEND_DIR/.env"
        fi
    else
        echo -e "${YELLOW}⚠ Redis CLI not found. Using file cache instead.${NC}"
        sed -i "s/cache.handler = redis/cache.handler = file/" "$BACKEND_DIR/.env"
    fi
else
    echo -e "${GREEN}✓ Using file-based cache${NC}"
fi

# Clear existing cache
rm -rf "$BACKEND_DIR/writable/cache/*" 2>/dev/null || true
echo -e "${GREEN}✓ Cache cleared${NC}"

echo ""

################################################################################
# Step 7: Frontend Setup
################################################################################
echo -e "${YELLOW}[7/8] Setting up frontend...${NC}"

# Check if frontend directory exists
if [ -d "$FRONTEND_DIR" ]; then
    echo -e "${GREEN}✓ Frontend directory found${NC}"

    # Note: AngularJS doesn't need build for this project
    echo -e "${BLUE}  AngularJS app - no build step required${NC}"
else
    echo -e "${YELLOW}⚠ Frontend directory not found${NC}"
fi

echo ""

################################################################################
# Step 8: Final Validation
################################################################################
echo -e "${YELLOW}[8/8] Running final validation...${NC}"

# Check writable directories
VALIDATION_PASSED=true

for DIR in "${WRITABLE_DIRS[@]}"; do
    if [ -w "$DIR" ]; then
        echo -e "${GREEN}✓ Writable: $DIR${NC}"
    else
        echo -e "${RED}✗ Not writable: $DIR${NC}"
        VALIDATION_PASSED=false
    fi
done

# Check .env exists and has required values
REQUIRED_ENV_VARS=(
    "database.default.hostname"
    "database.default.database"
    "database.default.username"
    "database.default.password"
)

for VAR in "${REQUIRED_ENV_VARS[@]}"; do
    if grep -q "$VAR" "$BACKEND_DIR/.env" && [ -n "$(grep "$VAR" "$BACKEND_DIR/.env" | cut -d '=' -f2 | xargs)" ]; then
        echo -e "${GREEN}✓ Config: $VAR${NC}"
    else
        echo -e "${RED}✗ Missing config: $VAR${NC}"
        VALIDATION_PASSED=false
    fi
done

echo ""

################################################################################
# Summary
################################################################################
if [ "$VALIDATION_PASSED" = true ]; then
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}   Setup completed successfully! ✓${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo -e "${BLUE}Next steps:${NC}"
    echo -e "  1. Review configuration in: $BACKEND_DIR/.env"
    echo -e "  2. Start your web server"
    echo -e "  3. Access the application"
    echo ""
    echo -e "${BLUE}For deployment:${NC}"
    echo -e "  ./scripts/deploy.sh $ENVIRONMENT"
    echo ""
else
    echo -e "${RED}========================================${NC}"
    echo -e "${RED}   Setup completed with errors${NC}"
    echo -e "${RED}========================================${NC}"
    echo -e "${YELLOW}Please fix the errors above and run setup again.${NC}"
    exit 1
fi
