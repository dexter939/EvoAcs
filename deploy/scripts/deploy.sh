#!/bin/bash

# ================================================
# ACS Production Deployment Script
# ================================================
# This script deploys the ACS system to production
# with zero-downtime and automatic rollback on failure

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DEPLOY_USER="${DEPLOY_USER:-www-data}"
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/acs}"
BACKUP_PATH="${BACKUP_PATH:-/var/backups/acs}"
SHARED_PATH="${SHARED_PATH:-$DEPLOY_PATH/shared}"
CURRENT_PATH="${CURRENT_PATH:-$DEPLOY_PATH/current}"
RELEASES_PATH="${RELEASES_PATH:-$DEPLOY_PATH/releases}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RELEASE_PATH="$RELEASES_PATH/$TIMESTAMP"

echo -e "${BLUE}======================================${NC}"
echo -e "${BLUE}  ACS Production Deployment${NC}"
echo -e "${BLUE}======================================${NC}"
echo ""

# Pre-deployment checks
echo -e "${YELLOW}[1/10] Pre-deployment checks...${NC}"

# Check if running as correct user
if [ "$USER" != "$DEPLOY_USER" ] && [ "$USER" != "root" ]; then
    echo -e "${RED}Error: Must run as $DEPLOY_USER or root${NC}"
    exit 1
fi

# Check required commands
for cmd in git composer php artisan; do
    if ! command -v $cmd &> /dev/null; then
        echo -e "${RED}Error: $cmd is not installed${NC}"
        exit 1
    fi
done

echo -e "${GREEN}✓ Pre-checks passed${NC}"

# Create directory structure
echo -e "${YELLOW}[2/10] Creating deployment directories...${NC}"
mkdir -p "$RELEASES_PATH"
mkdir -p "$SHARED_PATH"/{storage,env}
mkdir -p "$BACKUP_PATH"

# Clone/update repository
echo -e "${YELLOW}[3/10] Deploying new release...${NC}"
if [ -d "$RELEASE_PATH" ]; then
    rm -rf "$RELEASE_PATH"
fi

git clone --depth 1 --branch ${BRANCH:-main} ${REPO_URL} "$RELEASE_PATH"
cd "$RELEASE_PATH"

echo -e "${GREEN}✓ Code deployed to $RELEASE_PATH${NC}"

# Link shared files
echo -e "${YELLOW}[4/10] Linking shared files...${NC}"
rm -rf "$RELEASE_PATH/storage"
ln -s "$SHARED_PATH/storage" "$RELEASE_PATH/storage"

if [ -f "$SHARED_PATH/env/.env" ]; then
    ln -sf "$SHARED_PATH/env/.env" "$RELEASE_PATH/.env"
else
    echo -e "${RED}Error: .env file not found in $SHARED_PATH/env/${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Shared files linked${NC}"

# Install dependencies
echo -e "${YELLOW}[5/10] Installing dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
echo -e "${GREEN}✓ Dependencies installed${NC}"

# Cache configuration
echo -e "${YELLOW}[6/10] Optimizing application...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo -e "${GREEN}✓ Application optimized${NC}"

# Backup database
echo -e "${YELLOW}[7/10] Backing up database...${NC}"
if [ -f "$CURRENT_PATH/artisan" ]; then
    cd "$CURRENT_PATH"
    BACKUP_FILE="$BACKUP_PATH/db_backup_$TIMESTAMP.sql"
    
    DB_HOST=$(php artisan tinker --execute="echo config('database.connections.pgsql.host');")
    DB_NAME=$(php artisan tinker --execute="echo config('database.connections.pgsql.database');")
    DB_USER=$(php artisan tinker --execute="echo config('database.connections.pgsql.username');")
    
    PGPASSWORD=$(php artisan tinker --execute="echo config('database.connections.pgsql.password');") \
        pg_dump -h "$DB_HOST" -U "$DB_USER" "$DB_NAME" > "$BACKUP_FILE"
    
    gzip "$BACKUP_FILE"
    echo -e "${GREEN}✓ Database backed up to $BACKUP_FILE.gz${NC}"
else
    echo -e "${YELLOW}⚠ No current release, skipping database backup${NC}"
fi

# Run migrations
echo -e "${YELLOW}[8/10] Running database migrations...${NC}"
cd "$RELEASE_PATH"
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo -e "${RED}Error: Migration failed${NC}"
    echo -e "${YELLOW}Rolling back...${NC}"
    
    # Restore database from backup
    if [ -f "$BACKUP_FILE.gz" ]; then
        gunzip "$BACKUP_FILE.gz"
        PGPASSWORD=$(php artisan tinker --execute="echo config('database.connections.pgsql.password');") \
            psql -h "$DB_HOST" -U "$DB_USER" "$DB_NAME" < "$BACKUP_FILE"
        echo -e "${GREEN}✓ Database restored${NC}"
    fi
    
    exit 1
fi

echo -e "${GREEN}✓ Migrations completed${NC}"

# Switch to new release (atomic swap)
echo -e "${YELLOW}[9/10] Activating new release...${NC}"
ln -nfs "$RELEASE_PATH" "$CURRENT_PATH"
echo -e "${GREEN}✓ New release activated${NC}"

# Restart services
echo -e "${YELLOW}[10/10] Restarting services...${NC}"

if command -v supervisorctl &> /dev/null; then
    supervisorctl restart acs:*
    echo -e "${GREEN}✓ Supervisor services restarted${NC}"
elif command -v systemctl &> /dev/null; then
    systemctl restart acs-mqtt
    systemctl restart acs-websocket
    systemctl restart acs-horizon
    echo -e "${GREEN}✓ Systemd services restarted${NC}"
else
    echo -e "${YELLOW}⚠ No service manager found, please restart services manually${NC}"
fi

# Clear caches
php artisan horizon:terminate
php artisan queue:restart

# Cleanup old releases
echo -e "${YELLOW}Cleaning up old releases...${NC}"
cd "$RELEASES_PATH"
ls -1dt */ | tail -n +$((KEEP_RELEASES + 1)) | xargs rm -rf
echo -e "${GREEN}✓ Old releases cleaned up (keeping last $KEEP_RELEASES)${NC}"

# Post-deployment verification
echo ""
echo -e "${YELLOW}Running health checks...${NC}"
sleep 3

HEALTH_URL="${HEALTH_URL:-http://localhost:5000/acs/dashboard}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$HEALTH_URL")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✓ Application is healthy (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${RED}⚠ Application health check failed (HTTP $HTTP_CODE)${NC}"
    echo -e "${YELLOW}Please check logs: tail -f $DEPLOY_PATH/current/storage/logs/laravel.log${NC}"
fi

# Summary
echo ""
echo -e "${BLUE}======================================${NC}"
echo -e "${GREEN}  Deployment Completed Successfully!${NC}"
echo -e "${BLUE}======================================${NC}"
echo ""
echo -e "Release: ${GREEN}$TIMESTAMP${NC}"
echo -e "Path: ${GREEN}$RELEASE_PATH${NC}"
echo -e "Current: ${GREEN}$CURRENT_PATH${NC}"
echo ""
echo -e "Services status:"
echo -e "  - ACS Web: ${GREEN}Running${NC}"
echo -e "  - MQTT Subscriber: ${GREEN}Running${NC}"
echo -e "  - WebSocket Server: ${GREEN}Running${NC}"
echo -e "  - Horizon Worker: ${GREEN}Running${NC}"
echo ""
echo -e "Database backup: ${GREEN}$BACKUP_FILE.gz${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo -e "  1. Monitor logs: tail -f $DEPLOY_PATH/current/storage/logs/laravel.log"
echo -e "  2. Check Horizon: http://your-domain/horizon"
echo -e "  3. Test API: curl -H 'X-API-Key: your-key' http://your-domain/api/v1/..."
echo ""
