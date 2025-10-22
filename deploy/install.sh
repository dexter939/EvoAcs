#!/bin/bash
################################################################################
# ACS (Auto Configuration Server) - Production Deployment Script
# Sistema carrier-grade per gestione 100,000+ CPE devices
# Supporto TR-069, TR-369 USP, XMPP, MQTT
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
ACS_USER="acs"
ACS_HOME="/opt/acs"
ACS_REPO="https://github.com/dexter939/EvoAcs.git"
PROSODY_PORT=6000
PHP_VERSION="8.2"

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║    ACS Production Deployment Script                       ║"
echo "║    Carrier-Grade CPE Management System                    ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}❌ Please run as root (sudo)${NC}"
    exit 1
fi

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    OS_VERSION=$VERSION_ID
else
    echo -e "${RED}❌ Cannot detect OS${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Detected OS: $OS $OS_VERSION${NC}"

################################################################################
# Step 1: Install System Dependencies
################################################################################
echo -e "\n${BLUE}[1/8] Installing system dependencies...${NC}"

if [ "$OS" == "ubuntu" ] || [ "$OS" == "debian" ]; then
    apt-get update
    apt-get install -y \
        curl wget git unzip \
        nginx \
        postgresql-16 postgresql-contrib \
        redis-server \
        prosody \
        supervisor \
        certbot python3-certbot-nginx \
        software-properties-common
    
    # Add PHP repository
    add-apt-repository -y ppa:ondrej/php
    apt-get update
    
    # Install PHP 8.2
    apt-get install -y \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-soap
    
elif [ "$OS" == "centos" ] || [ "$OS" == "rhel" ]; then
    yum install -y epel-release
    yum install -y \
        curl wget git unzip \
        nginx \
        postgresql16-server postgresql16-contrib \
        redis \
        prosody \
        supervisor \
        certbot python3-certbot-nginx
    
    # Install PHP 8.2
    yum install -y \
        php82 php82-fpm php82-pgsql php82-redis \
        php82-mbstring php82-xml php82-curl php82-zip
else
    echo -e "${RED}❌ Unsupported OS: $OS${NC}"
    exit 1
fi

echo -e "${GREEN}✅ System dependencies installed${NC}"

################################################################################
# Step 2: Create ACS User
################################################################################
echo -e "\n${BLUE}[2/8] Creating ACS user...${NC}"

if ! id -u $ACS_USER > /dev/null 2>&1; then
    useradd -r -m -d $ACS_HOME -s /bin/bash $ACS_USER
    echo -e "${GREEN}✅ User $ACS_USER created${NC}"
else
    echo -e "${YELLOW}⚠️  User $ACS_USER already exists${NC}"
fi

################################################################################
# Step 3: Install Composer
################################################################################
echo -e "\n${BLUE}[3/8] Installing Composer...${NC}"

if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    echo -e "${GREEN}✅ Composer installed${NC}"
else
    echo -e "${YELLOW}⚠️  Composer already installed${NC}"
fi

################################################################################
# Step 4: Clone ACS Repository
################################################################################
echo -e "\n${BLUE}[4/8] Cloning ACS repository...${NC}"

if [ ! -d "$ACS_HOME/app" ]; then
    sudo -u $ACS_USER git clone $ACS_REPO $ACS_HOME/app
    echo -e "${GREEN}✅ Repository cloned${NC}"
else
    echo -e "${YELLOW}⚠️  Repository already exists, pulling latest...${NC}"
    cd $ACS_HOME/app
    sudo -u $ACS_USER git pull
fi

cd $ACS_HOME/app

################################################################################
# Step 5: Configure PostgreSQL
################################################################################
echo -e "\n${BLUE}[5/8] Configuring PostgreSQL...${NC}"

systemctl start postgresql
systemctl enable postgresql

# Create database and user
sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname = 'acs_production'" | grep -q 1 || \
    sudo -u postgres psql -c "CREATE DATABASE acs_production;"

sudo -u postgres psql -tc "SELECT 1 FROM pg_user WHERE usename = 'acs_user'" | grep -q 1 || \
    sudo -u postgres psql -c "CREATE USER acs_user WITH ENCRYPTED PASSWORD 'CHANGE_THIS_PASSWORD';"

sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE acs_production TO acs_user;"
sudo -u postgres psql -d acs_production -c "GRANT ALL ON SCHEMA public TO acs_user;"

echo -e "${GREEN}✅ PostgreSQL configured${NC}"

################################################################################
# Step 6: Configure Application
################################################################################
echo -e "\n${BLUE}[6/8] Configuring ACS application...${NC}"

# Install Composer dependencies
sudo -u $ACS_USER composer install --no-dev --optimize-autoloader

# Copy .env file
if [ ! -f .env ]; then
    cp .env.example .env
    
    # Generate random keys
    APP_KEY=$(openssl rand -base64 32)
    SESSION_SECRET=$(openssl rand -base64 32)
    XMPP_PASSWORD=$(openssl rand -base64 24)
    
    # Update .env
    sed -i "s|APP_KEY=.*|APP_KEY=base64:$APP_KEY|" .env
    sed -i "s|SESSION_SECRET=.*|SESSION_SECRET=$SESSION_SECRET|" .env
    sed -i "s|XMPP_PASSWORD=.*|XMPP_PASSWORD=$XMPP_PASSWORD|" .env
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=acs_production|" .env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=acs_user|" .env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=CHANGE_THIS_PASSWORD|" .env
    
    echo -e "${GREEN}✅ Environment configured${NC}"
    echo -e "${YELLOW}⚠️  IMPORTANT: Edit $ACS_HOME/app/.env and change database password!${NC}"
else
    echo -e "${YELLOW}⚠️  .env already exists, skipping${NC}"
fi

# Set permissions
chown -R $ACS_USER:$ACS_USER $ACS_HOME/app
chmod -R 755 $ACS_HOME/app/storage
chmod -R 755 $ACS_HOME/app/bootstrap/cache

# Run migrations
sudo -u $ACS_USER php artisan migrate --force
sudo -u $ACS_USER php artisan config:cache
sudo -u $ACS_USER php artisan route:cache

echo -e "${GREEN}✅ Application configured${NC}"

################################################################################
# Step 7: Configure Services
################################################################################
echo -e "\n${BLUE}[7/8] Configuring services...${NC}"

# Prosody XMPP
cp prosody.cfg.lua /etc/prosody/prosody.cfg.lua
sed -i "s|c2s_ports = { 6000 }|c2s_ports = { $PROSODY_PORT }|" /etc/prosody/prosody.cfg.lua
systemctl restart prosody
systemctl enable prosody

# Create XMPP user
prosodyctl register acs-server acs.local "$XMPP_PASSWORD"

# Redis
systemctl start redis
systemctl enable redis

# Nginx
cat > /etc/nginx/sites-available/acs << 'NGINX_EOF'
server {
    listen 80;
    server_name _;
    root /opt/acs/app/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX_EOF

ln -sf /etc/nginx/sites-available/acs /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl restart nginx
systemctl enable nginx

# Supervisor for Laravel workers
cat > /etc/supervisor/conf.d/acs-worker.conf << 'SUPERVISOR_EOF'
[program:acs-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /opt/acs/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=acs
numprocs=4
redirect_stderr=true
stdout_logfile=/opt/acs/app/storage/logs/worker.log
stopwaitsecs=3600
SUPERVISOR_EOF

supervisorctl reread
supervisorctl update
supervisorctl start acs-worker:*

# Systemd service for Laravel HTTP server
cat > /etc/systemd/system/acs-http.service << 'SYSTEMD_EOF'
[Unit]
Description=ACS HTTP Server
After=network.target postgresql.service redis.service

[Service]
Type=simple
User=acs
WorkingDirectory=/opt/acs/app
ExecStart=/usr/bin/php artisan serve --host=127.0.0.1 --port=8000
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
SYSTEMD_EOF

systemctl daemon-reload
systemctl enable acs-http
systemctl start acs-http

echo -e "${GREEN}✅ Services configured${NC}"

################################################################################
# Step 8: Final Steps
################################################################################
echo -e "\n${BLUE}[8/8] Final configuration...${NC}"

# Firewall rules (if ufw is installed)
if command -v ufw &> /dev/null; then
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw allow $PROSODY_PORT/tcp
    echo -e "${GREEN}✅ Firewall rules configured${NC}"
fi

# Create update script
cat > $ACS_HOME/update.sh << 'UPDATE_EOF'
#!/bin/bash
set -e
cd /opt/acs/app
sudo -u acs git pull
sudo -u acs composer install --no-dev --optimize-autoloader
sudo -u acs php artisan migrate --force
sudo -u acs php artisan config:cache
sudo -u acs php artisan route:cache
systemctl restart acs-http
supervisorctl restart acs-worker:*
echo "✅ ACS updated successfully"
UPDATE_EOF

chmod +x $ACS_HOME/update.sh

echo -e "\n${GREEN}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║              ✅ Installation Complete!                     ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo -e "${BLUE}📋 Next Steps:${NC}"
echo ""
echo "1. Edit configuration:"
echo "   sudo nano $ACS_HOME/app/.env"
echo ""
echo "2. Access ACS Dashboard:"
echo "   http://YOUR_SERVER_IP/acs/dashboard"
echo ""
echo "3. Configure SSL (optional):"
echo "   sudo certbot --nginx -d your-domain.com"
echo ""
echo "4. Update ACS:"
echo "   sudo $ACS_HOME/update.sh"
echo ""
echo -e "${BLUE}📊 Service Status:${NC}"
systemctl status nginx --no-pager -l | head -3
systemctl status postgresql --no-pager -l | head -3
systemctl status redis --no-pager -l | head -3
systemctl status prosody --no-pager -l | head -3
systemctl status acs-http --no-pager -l | head -3
echo ""
echo -e "${YELLOW}⚠️  IMPORTANT: Change the database password in .env!${NC}"
echo ""
