#!/bin/bash
################################################################################
# Setup Automatic Git Push → Production Deploy
# Ogni volta che fai push su GitHub, il server si aggiorna automaticamente
################################################################################

set -e

PRODUCTION_HOST="${1:-your-server.com}"
PRODUCTION_USER="root"
GITHUB_REPO="${2:-https://github.com/dexter939/EvoAcs.git}"

echo "╔════════════════════════════════════════════════════════════╗"
echo "║    Setup Automatic Deployment via Git Webhooks            ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

if [ "$PRODUCTION_HOST" == "your-server.com" ]; then
    echo "❌ Usage: $0 <production-server> <github-repo-url>"
    exit 1
fi

echo "📋 Configuration:"
echo "  Production: $PRODUCTION_HOST"
echo "  Repository: $GITHUB_REPO"
echo ""

################################################################################
# Step 1: Initialize Git Repository
################################################################################
echo "[1/4] Initializing Git repository on production..."

ssh "$PRODUCTION_USER@$PRODUCTION_HOST" << EOF
    cd /opt/acs/app
    
    # Initialize git if not already
    if [ ! -d .git ]; then
        sudo -u acs git init
        sudo -u acs git remote add origin $GITHUB_REPO
    fi
    
    # Create deploy key
    if [ ! -f /opt/acs/.ssh/id_rsa ]; then
        sudo -u acs mkdir -p /opt/acs/.ssh
        sudo -u acs ssh-keygen -t rsa -b 4096 -f /opt/acs/.ssh/id_rsa -N ""
        
        echo ""
        echo "🔑 Add this deploy key to GitHub:"
        echo "   Repository Settings → Deploy keys → Add deploy key"
        echo ""
        cat /opt/acs/.ssh/id_rsa.pub
        echo ""
        read -p "Press Enter after adding the deploy key to GitHub..."
    fi
EOF

################################################################################
# Step 2: Create Deployment Script on Production
################################################################################
echo "[2/4] Creating auto-deploy script..."

ssh "$PRODUCTION_USER@$PRODUCTION_HOST" << 'DEPLOY_SCRIPT_EOF'
    cat > /opt/acs/deploy-webhook.sh << 'WEBHOOK_EOF'
#!/bin/bash
set -e

echo "🚀 Starting auto-deployment..."

cd /opt/acs/app

# Pull latest changes
sudo -u acs git fetch origin main
sudo -u acs git reset --hard origin/main

# Install dependencies
sudo -u acs composer install --no-dev --optimize-autoloader

# Run migrations
sudo -u acs php artisan migrate --force

# Clear caches
sudo -u acs php artisan config:cache
sudo -u acs php artisan route:cache
sudo -u acs php artisan view:cache

# Restart services
systemctl restart acs-http
supervisorctl restart acs-worker:*

echo "✅ Deployment completed at $(date)"
WEBHOOK_EOF

    chmod +x /opt/acs/deploy-webhook.sh
    echo "✅ Deploy script created"
DEPLOY_SCRIPT_EOF

################################################################################
# Step 3: Setup GitHub Actions (Alternative to Webhook)
################################################################################
echo "[3/4] Creating GitHub Actions workflow..."

mkdir -p .github/workflows

cat > .github/workflows/deploy-production.yml << 'GITHUB_ACTIONS_EOF'
name: Deploy to Production

on:
  push:
    branches:
      - main
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
      
      - name: Deploy to production server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.PRODUCTION_HOST }}
          username: ${{ secrets.PRODUCTION_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            /opt/acs/deploy-webhook.sh
GITHUB_ACTIONS_EOF

echo "✅ GitHub Actions workflow created"
echo ""
echo "📝 Add these secrets to GitHub:"
echo "   Repository Settings → Secrets → Actions → New repository secret"
echo ""
echo "   PRODUCTION_HOST = $PRODUCTION_HOST"
echo "   PRODUCTION_USER = $PRODUCTION_USER"
echo "   SSH_PRIVATE_KEY = (your SSH private key)"
echo ""

################################################################################
# Step 4: Setup Manual Deploy Command
################################################################################
echo "[4/4] Creating manual deploy alias..."

cat >> ~/.bashrc << ALIAS_EOF

# ACS Production Deployment
alias acs-deploy="ssh $PRODUCTION_USER@$PRODUCTION_HOST '/opt/acs/deploy-webhook.sh'"
ALIAS_EOF

echo "✅ Alias created: acs-deploy"
echo ""

################################################################################
# Summary
################################################################################
echo "╔════════════════════════════════════════════════════════════╗"
echo "║              ✅ Setup Completed!                           ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "📋 How to Deploy:"
echo ""
echo "1. Automatic (GitHub Actions):"
echo "   git push origin main"
echo "   → Production auto-updates in ~2 minutes"
echo ""
echo "2. Manual via SSH:"
echo "   acs-deploy"
echo ""
echo "3. Manual via script:"
echo "   ssh $PRODUCTION_USER@$PRODUCTION_HOST '/opt/acs/deploy-webhook.sh'"
echo ""
echo "📊 Monitor deployment:"
echo "   ssh $PRODUCTION_USER@$PRODUCTION_HOST 'tail -f /opt/acs/app/storage/logs/laravel.log'"
echo ""
