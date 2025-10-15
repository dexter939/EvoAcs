#!/bin/bash
################################################################################
# ACS Sync Script - Development (Replit) → Production (Linux Server)
# Sincronizza codice e database tra ambienti
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
PRODUCTION_HOST="${1:-your-server.com}"
PRODUCTION_USER="root"
PRODUCTION_PATH="/opt/acs/app"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/id_rsa}"

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║    ACS Sync: Replit → Production Server                   ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check if production host is provided
if [ "$PRODUCTION_HOST" == "your-server.com" ]; then
    echo -e "${RED}❌ Usage: $0 <production-server-ip-or-domain>${NC}"
    echo ""
    echo "Example:"
    echo "  $0 192.168.1.100"
    echo "  $0 acs.mycompany.com"
    exit 1
fi

# Check SSH key
if [ ! -f "$SSH_KEY" ]; then
    echo -e "${RED}❌ SSH key not found: $SSH_KEY${NC}"
    echo ""
    echo "Generate SSH key:"
    echo "  ssh-keygen -t rsa -b 4096"
    echo ""
    echo "Copy to production server:"
    echo "  ssh-copy-id $PRODUCTION_USER@$PRODUCTION_HOST"
    exit 1
fi

echo -e "${BLUE}📋 Sync Configuration:${NC}"
echo "  Source: Replit (current directory)"
echo "  Target: $PRODUCTION_USER@$PRODUCTION_HOST:$PRODUCTION_PATH"
echo ""

read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Sync cancelled"
    exit 0
fi

################################################################################
# Step 1: Test SSH Connection
################################################################################
echo -e "\n${BLUE}[1/6] Testing SSH connection...${NC}"

if ssh -i "$SSH_KEY" -o ConnectTimeout=5 "$PRODUCTION_USER@$PRODUCTION_HOST" "echo 'SSH OK'" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ SSH connection successful${NC}"
else
    echo -e "${RED}❌ Cannot connect to $PRODUCTION_HOST${NC}"
    echo ""
    echo "Setup SSH access:"
    echo "  ssh-copy-id -i $SSH_KEY $PRODUCTION_USER@$PRODUCTION_HOST"
    exit 1
fi

################################################################################
# Step 2: Backup Production Database
################################################################################
echo -e "\n${BLUE}[2/6] Backing up production database...${NC}"

ssh -i "$SSH_KEY" "$PRODUCTION_USER@$PRODUCTION_HOST" << 'BACKUP_EOF'
    cd /opt/acs
    mkdir -p backups
    sudo -u postgres pg_dump acs_production | gzip > backups/acs_backup_$(date +%Y%m%d_%H%M%S).sql.gz
    echo "✅ Database backup created"
BACKUP_EOF

################################################################################
# Step 3: Sync Code Files
################################################################################
echo -e "\n${BLUE}[3/6] Syncing code files...${NC}"

# Exclude files
EXCLUDE_FILES=(
    ".git"
    ".env"
    "node_modules"
    "vendor"
    "storage/logs/*"
    "storage/framework/cache/*"
    "storage/framework/sessions/*"
    "storage/framework/views/*"
    "prosody.log"
    "prosody.pid"
    "data/*"
)

# Build exclude string
RSYNC_EXCLUDE=""
for item in "${EXCLUDE_FILES[@]}"; do
    RSYNC_EXCLUDE="$RSYNC_EXCLUDE --exclude=$item"
done

# Sync with rsync
rsync -avz --delete \
    $RSYNC_EXCLUDE \
    -e "ssh -i $SSH_KEY" \
    ./ "$PRODUCTION_USER@$PRODUCTION_HOST:$PRODUCTION_PATH/"

echo -e "${GREEN}✅ Code files synced${NC}"

################################################################################
# Step 4: Install Dependencies
################################################################################
echo -e "\n${BLUE}[4/6] Installing dependencies on production...${NC}"

ssh -i "$SSH_KEY" "$PRODUCTION_USER@$PRODUCTION_HOST" << 'DEPS_EOF'
    cd /opt/acs/app
    
    # Composer dependencies
    sudo -u acs composer install --no-dev --optimize-autoloader
    
    echo "✅ Dependencies installed"
DEPS_EOF

################################################################################
# Step 5: Run Migrations
################################################################################
echo -e "\n${BLUE}[5/6] Running database migrations...${NC}"

ssh -i "$SSH_KEY" "$PRODUCTION_USER@$PRODUCTION_HOST" << 'MIGRATE_EOF'
    cd /opt/acs/app
    
    # Run migrations
    sudo -u acs php artisan migrate --force
    
    # Clear caches
    sudo -u acs php artisan config:cache
    sudo -u acs php artisan route:cache
    sudo -u acs php artisan view:cache
    
    echo "✅ Migrations completed"
MIGRATE_EOF

################################################################################
# Step 6: Restart Services
################################################################################
echo -e "\n${BLUE}[6/6] Restarting production services...${NC}"

ssh -i "$SSH_KEY" "$PRODUCTION_USER@$PRODUCTION_HOST" << 'RESTART_EOF'
    # Restart HTTP server
    systemctl restart acs-http
    
    # Restart queue workers
    supervisorctl restart acs-worker:*
    
    # Reload Nginx (graceful)
    systemctl reload nginx
    
    echo "✅ Services restarted"
RESTART_EOF

################################################################################
# Summary
################################################################################
echo -e "\n${GREEN}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║              ✅ Sync Completed Successfully!               ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo -e "${BLUE}📊 Production Status:${NC}"
ssh -i "$SSH_KEY" "$PRODUCTION_USER@$PRODUCTION_HOST" << 'STATUS_EOF'
    echo ""
    echo "Services:"
    systemctl status acs-http --no-pager -l | head -3
    supervisorctl status | head -5
    echo ""
    echo "Last commit:"
    cd /opt/acs/app
    git log -1 --oneline 2>/dev/null || echo "Not a git repository"
    echo ""
    echo "Disk usage:"
    df -h /opt/acs | tail -1
    echo ""
STATUS_EOF

echo -e "${BLUE}🌐 Access production:${NC}"
echo "  Dashboard: https://$PRODUCTION_HOST/acs/dashboard"
echo "  Logs: ssh -i $SSH_KEY $PRODUCTION_USER@$PRODUCTION_HOST 'tail -f /opt/acs/app/storage/logs/laravel.log'"
echo ""

echo -e "${YELLOW}💡 Tip: Create alias for quick sync${NC}"
echo "  echo \"alias acs-sync='./deploy/sync-to-production.sh $PRODUCTION_HOST'\" >> ~/.bashrc"
echo ""
