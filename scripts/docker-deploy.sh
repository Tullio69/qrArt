#!/bin/bash

################################################################################
# QrArt - Docker Deploy Script
#
# Deploy con Docker Compose
#
# Uso: ./scripts/docker-deploy.sh [environment] [options]
#   environment: dev, staging, production (default: production)
#   options:
#     --build        Force rebuild images
#     --no-cache     Build without cache
#     --pull         Pull latest base images
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Parse arguments
ENVIRONMENT="${1:-production}"
BUILD_FLAG=""
NO_CACHE_FLAG=""
PULL_FLAG=""

shift || true
while [[ $# -gt 0 ]]; do
    case $1 in
        --build) BUILD_FLAG="--build"; shift ;;
        --no-cache) NO_CACHE_FLAG="--no-cache"; shift ;;
        --pull) PULL_FLAG="--pull"; shift ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

# Project paths
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

log() {
    echo -e "$1"
}

log "${BLUE}========================================${NC}"
log "${BLUE}   QrArt Docker Deployment${NC}"
log "${BLUE}   Environment: $ENVIRONMENT${NC}"
log "${BLUE}========================================${NC}"
log ""

################################################################################
# Check Docker Installation
################################################################################
log "${YELLOW}[1/8] Checking Docker installation...${NC}"

if ! command -v docker &> /dev/null; then
    log "${RED}✗ Docker is not installed${NC}"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    log "${RED}✗ Docker Compose is not installed${NC}"
    exit 1
fi

DOCKER_VERSION=$(docker --version | awk '{print $3}' | sed 's/,//')
COMPOSE_VERSION=$(docker-compose --version | awk '{print $4}' | sed 's/,//')

log "${GREEN}✓ Docker: $DOCKER_VERSION${NC}"
log "${GREEN}✓ Docker Compose: $COMPOSE_VERSION${NC}"
log ""

################################################################################
# Environment File Check
################################################################################
log "${YELLOW}[2/8] Checking environment configuration...${NC}"

if [ ! -f ".env.docker" ]; then
    if [ -f ".env.docker.example" ]; then
        log "${YELLOW}⚠ .env.docker not found, creating from example...${NC}"
        cp .env.docker.example .env.docker
        log "${YELLOW}  Please edit .env.docker with your configuration${NC}"
        log "${YELLOW}  Then run this script again${NC}"
        exit 1
    else
        log "${RED}✗ .env.docker.example not found${NC}"
        exit 1
    fi
fi

# Load environment variables
export $(grep -v '^#' .env.docker | xargs)

# Set build target based on environment
if [ "$ENVIRONMENT" = "dev" ] || [ "$ENVIRONMENT" = "development" ]; then
    export BUILD_TARGET="development"
    export CI_ENVIRONMENT="development"
    COMPOSE_PROFILE="--profile development"
else
    export BUILD_TARGET="production"
    export CI_ENVIRONMENT="production"
    COMPOSE_PROFILE=""
fi

log "${GREEN}✓ Environment configured${NC}"
log "${BLUE}  Build target: $BUILD_TARGET${NC}"
log "${BLUE}  CI Environment: $CI_ENVIRONMENT${NC}"
log ""

################################################################################
# Confirmation for Production
################################################################################
if [ "$ENVIRONMENT" = "production" ]; then
    log "${YELLOW}⚠ WARNING: Deploying to PRODUCTION${NC}"
    read -p "Are you sure? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy]es$ ]]; then
        log "${YELLOW}Deployment cancelled${NC}"
        exit 0
    fi
fi

################################################################################
# Stop Running Containers
################################################################################
log "${YELLOW}[3/8] Stopping running containers...${NC}"

if [ "$(docker-compose ps -q)" ]; then
    docker-compose down
    log "${GREEN}✓ Containers stopped${NC}"
else
    log "${BLUE}  No running containers${NC}"
fi
log ""

################################################################################
# Build Images
################################################################################
log "${YELLOW}[4/8] Building Docker images...${NC}"

BUILD_COMMAND="docker-compose build $BUILD_FLAG $NO_CACHE_FLAG $PULL_FLAG"

if $BUILD_COMMAND; then
    log "${GREEN}✓ Images built successfully${NC}"
else
    log "${RED}✗ Build failed${NC}"
    exit 1
fi
log ""

################################################################################
# Start Services
################################################################################
log "${YELLOW}[5/8] Starting services...${NC}"

if docker-compose up -d $COMPOSE_PROFILE; then
    log "${GREEN}✓ Services started${NC}"
else
    log "${RED}✗ Failed to start services${NC}"
    exit 1
fi
log ""

################################################################################
# Wait for Services to be Ready
################################################################################
log "${YELLOW}[6/8] Waiting for services to be ready...${NC}"

log "${BLUE}  Waiting for database...${NC}"
sleep 5

MAX_ATTEMPTS=30
ATTEMPT=0

while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    if docker-compose exec -T db mysqladmin ping -h localhost -u root -p"$DB_ROOT_PASSWORD" --silent 2>/dev/null; then
        log "${GREEN}✓ Database is ready${NC}"
        break
    fi

    ATTEMPT=$((ATTEMPT + 1))
    if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
        log "${RED}✗ Database failed to start${NC}"
        docker-compose logs db
        exit 1
    fi

    sleep 2
done

log "${BLUE}  Waiting for Redis...${NC}"
if docker-compose exec -T redis redis-cli ping > /dev/null 2>&1; then
    log "${GREEN}✓ Redis is ready${NC}"
fi

log "${BLUE}  Waiting for application...${NC}"
sleep 5

ATTEMPT=0
while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    if curl -sf http://localhost:${APP_PORT:-8080}/api/health > /dev/null 2>&1; then
        log "${GREEN}✓ Application is ready${NC}"
        break
    fi

    ATTEMPT=$((ATTEMPT + 1))
    if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
        log "${YELLOW}⚠ Application health check failed, but continuing...${NC}"
        log "${BLUE}  Check logs: docker-compose logs app${NC}"
        break
    fi

    sleep 2
done

log ""

################################################################################
# Run Migrations
################################################################################
log "${YELLOW}[7/8] Running database migrations...${NC}"

if docker-compose exec -T app php backend/qrartApp/spark migrate --all 2>/dev/null; then
    log "${GREEN}✓ Migrations completed${NC}"
else
    log "${YELLOW}⚠ Migrations may have failed or spark not available${NC}"
fi
log ""

################################################################################
# Health Check
################################################################################
log "${YELLOW}[8/8] Performing health check...${NC}"

HEALTH_RESPONSE=$(curl -s http://localhost:${APP_PORT:-8080}/api/health)

if [ $? -eq 0 ]; then
    log "${GREEN}✓ Health check passed${NC}"
    echo "$HEALTH_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$HEALTH_RESPONSE"
else
    log "${YELLOW}⚠ Health check failed${NC}"
fi
log ""

################################################################################
# Summary
################################################################################
log "${GREEN}========================================${NC}"
log "${GREEN}   Deployment completed!${NC}"
log "${GREEN}========================================${NC}"
log ""
log "${BLUE}Service Status:${NC}"
docker-compose ps
log ""
log "${BLUE}Access Points:${NC}"
log "  Application: http://localhost:${APP_PORT:-8080}"
if [ "$ENVIRONMENT" = "dev" ] || [ "$ENVIRONMENT" = "development" ]; then
    log "  PhpMyAdmin:  http://localhost:${PHPMYADMIN_PORT:-8081}"
fi
log ""
log "${BLUE}Useful Commands:${NC}"
log "  View logs:      docker-compose logs -f app"
log "  Stop services:  docker-compose down"
log "  Restart:        docker-compose restart"
log "  Shell access:   docker-compose exec app bash"
log "  DB shell:       docker-compose exec db mysql -u root -p"
log ""
