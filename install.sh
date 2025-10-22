#!/bin/bash

###############################################################################
# ACS (Auto Configuration Server) - Installazione Sistema Linux
# Carrier-grade TR-069/TR-369 CPE Management Platform
# 
# Requisiti:
# - Ubuntu 22.04+ / Debian 11+ / CentOS 8+
# - Accesso root o sudo
# - Connessione internet
###############################################################################

set -e

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variabili di configurazione
APP_NAME="ACS Server"
APP_DIR="/opt/acs"
APP_USER="acs"
PHP_VERSION="8.3"
POSTGRES_VERSION="16"
REDIS_VERSION="7.0"
DOMAIN="${DOMAIN:-localhost}"
APP_PORT="${APP_PORT:-5000}"
DB_NAME="${DB_NAME:-acs_production}"
DB_USER="${DB_USER:-acs_user}"

# Funzioni helper
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "Questo script deve essere eseguito come root o con sudo"
        exit 1
    fi
}

detect_os() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$ID
        OS_VERSION=$VERSION_ID
        print_info "OS rilevato: $NAME $VERSION"
    else
        print_error "Impossibile rilevare il sistema operativo"
        exit 1
    fi
}

install_dependencies_ubuntu() {
    print_info "Installazione dipendenze per Ubuntu/Debian..."
    
    # Update package lists
    apt-get update -y
    
    # Installazione pacchetti di sistema
    apt-get install -y \
        software-properties-common \
        apt-transport-https \
        ca-certificates \
        curl \
        wget \
        git \
        unzip \
        supervisor \
        nginx \
        gnupg \
        lsb-release
    
    # Repository PHP
    add-apt-repository ppa:ondrej/php -y
    apt-get update -y
    
    # Installazione PHP
    print_info "Installazione PHP ${PHP_VERSION}..."
    apt-get install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-json \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-intl
    
    # PostgreSQL
    print_info "Installazione PostgreSQL ${POSTGRES_VERSION}..."
    sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
    wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -
    apt-get update -y
    apt-get install -y postgresql-${POSTGRES_VERSION} postgresql-client-${POSTGRES_VERSION}
    
    # Redis
    print_info "Installazione Redis..."
    apt-get install -y redis-server
    
    # Prosody XMPP Server
    print_info "Installazione Prosody XMPP Server..."
    apt-get install -y prosody lua-dbi-postgresql
    
    # Composer
    install_composer
    
    print_success "Dipendenze installate con successo"
}

install_dependencies_centos() {
    print_info "Installazione dipendenze per CentOS/RHEL..."
    
    # EPEL repository
    dnf install -y epel-release
    dnf update -y
    
    # Remi repository per PHP
    dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm
    dnf module reset php -y
    dnf module enable php:remi-${PHP_VERSION} -y
    
    # Installazione pacchetti
    dnf install -y \
        git \
        curl \
        wget \
        unzip \
        nginx \
        supervisor \
        php \
        php-fpm \
        php-cli \
        php-pgsql \
        php-json \
        php-mbstring \
        php-xml \
        php-gd \
        php-curl \
        php-zip \
        php-bcmath \
        php-redis \
        php-intl
    
    # PostgreSQL
    dnf install -y postgresql${POSTGRES_VERSION}-server postgresql${POSTGRES_VERSION}
    /usr/pgsql-${POSTGRES_VERSION}/bin/postgresql-${POSTGRES_VERSION}-setup initdb
    systemctl enable postgresql-${POSTGRES_VERSION}
    systemctl start postgresql-${POSTGRES_VERSION}
    
    # Redis
    dnf install -y redis
    systemctl enable redis
    systemctl start redis
    
    # Prosody
    dnf install -y prosody
    
    install_composer
    
    print_success "Dipendenze installate con successo"
}

install_composer() {
    print_info "Installazione Composer..."
    EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        print_error "Checksum Composer non valido"
        rm composer-setup.php
        exit 1
    fi
    
    php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
    print_success "Composer installato"
}

create_app_user() {
    print_info "Creazione utente applicazione..."
    
    if id "$APP_USER" &>/dev/null; then
        print_warning "Utente $APP_USER già esistente"
    else
        useradd -r -m -s /bin/bash $APP_USER
        print_success "Utente $APP_USER creato"
    fi
}

setup_database() {
    print_info "Configurazione database PostgreSQL..."
    
    # Genera password casuale se non fornita
    if [ -z "$DB_PASSWORD" ]; then
        DB_PASSWORD=$(openssl rand -base64 32)
        print_info "Password DB generata automaticamente"
    fi
    
    # Crea database e utente
    sudo -u postgres psql <<EOF
-- Crea utente
CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';

-- Crea database
CREATE DATABASE $DB_NAME OWNER $DB_USER;

-- Grant privilegi
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;

-- Abilita estensioni
\c $DB_NAME
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

\q
EOF
    
    print_success "Database configurato: $DB_NAME"
    
    # Salva credenziali
    cat > /root/.acs_db_credentials <<EOF
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASSWORD=$DB_PASSWORD
EOF
    chmod 600 /root/.acs_db_credentials
    print_info "Credenziali salvate in /root/.acs_db_credentials"
}

clone_repository() {
    print_info "Clonazione repository applicazione..."
    
    if [ -z "$REPO_URL" ]; then
        print_error "Variabile REPO_URL non impostata"
        echo "Esegui: export REPO_URL='https://github.com/username/acs-repo.git'"
        exit 1
    fi
    
    # Rimuovi directory esistente se presente
    if [ -d "$APP_DIR" ]; then
        print_warning "Directory $APP_DIR già esistente, rimozione..."
        rm -rf $APP_DIR
    fi
    
    git clone $REPO_URL $APP_DIR
    chown -R $APP_USER:$APP_USER $APP_DIR
    
    print_success "Repository clonato in $APP_DIR"
}

install_php_dependencies() {
    print_info "Installazione dipendenze PHP..."
    
    cd $APP_DIR
    sudo -u $APP_USER composer install --no-dev --optimize-autoloader
    
    print_success "Dipendenze PHP installate"
}

configure_environment() {
    print_info "Configurazione file .env..."
    
    cd $APP_DIR
    
    # Carica credenziali DB
    . /root/.acs_db_credentials
    
    # Genera APP_KEY
    APP_KEY=$(php artisan key:generate --show)
    
    # Genera SESSION_SECRET
    SESSION_SECRET=$(openssl rand -base64 32)
    
    cat > .env <<EOF
APP_NAME="ACS Server"
APP_ENV=production
APP_KEY=$APP_KEY
APP_DEBUG=false
APP_URL=http://$DOMAIN

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASSWORD

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECRET=$SESSION_SECRET

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@acs.local"
MAIL_FROM_NAME="${APP_NAME}"

# TR-069 Configuration
TR069_ACS_URL=http://$DOMAIN/acs
TR069_CONNECTION_REQUEST_PORT=7547

# TR-369 USP Configuration
USP_ENDPOINT_ID=acs::controller
USP_XMPP_SERVER=localhost
USP_XMPP_PORT=5222
EOF
    
    chown $APP_USER:$APP_USER .env
    chmod 600 .env
    
    print_success "File .env configurato"
}

run_migrations() {
    print_info "Esecuzione migrazioni database..."
    
    cd $APP_DIR
    sudo -u $APP_USER php artisan migrate --force
    
    print_success "Migrazioni completate"
}

seed_database() {
    print_info "Popolamento database iniziale..."
    
    cd $APP_DIR
    sudo -u $APP_USER php artisan db:seed --force
    
    print_success "Database popolato"
}

optimize_laravel() {
    print_info "Ottimizzazione Laravel..."
    
    cd $APP_DIR
    sudo -u $APP_USER php artisan config:cache
    sudo -u $APP_USER php artisan route:cache
    sudo -u $APP_USER php artisan view:cache
    sudo -u $APP_USER php artisan event:cache
    
    print_success "Laravel ottimizzato"
}

configure_nginx() {
    print_info "Configurazione Nginx..."
    
    cat > /etc/nginx/sites-available/acs <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;
    
    root $APP_DIR/public;
    index index.php index.html;
    
    client_max_body_size 100M;
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # TR-069 ACS endpoint
    location /acs {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    # Logs
    access_log /var/log/nginx/acs-access.log;
    error_log /var/log/nginx/acs-error.log;
}
EOF
    
    # Abilita site
    ln -sf /etc/nginx/sites-available/acs /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # Test configurazione
    nginx -t
    
    # Restart Nginx
    systemctl restart nginx
    systemctl enable nginx
    
    print_success "Nginx configurato"
}

configure_supervisor() {
    print_info "Configurazione Supervisor..."
    
    cat > /etc/supervisor/conf.d/acs.conf <<EOF
[program:acs-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_DIR/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=$APP_USER
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/supervisor/acs-queue.log
stopwaitsecs=3600

[program:acs-horizon]
process_name=%(program_name)s
command=php $APP_DIR/artisan horizon
autostart=true
autorestart=true
user=$APP_USER
redirect_stderr=true
stdout_logfile=/var/log/supervisor/acs-horizon.log
stopwaitsecs=3600
EOF
    
    # Crea directory log
    mkdir -p /var/log/supervisor
    
    # Ricarica configurazione
    supervisorctl reread
    supervisorctl update
    supervisorctl start all
    
    print_success "Supervisor configurato"
}

configure_prosody() {
    print_info "Configurazione Prosody XMPP Server..."
    
    cat > /etc/prosody/prosody.cfg.lua <<EOF
-- Prosody XMPP Server Configuration for ACS TR-369 USP

admins = { }

modules_enabled = {
    "roster";
    "saslauth";
    "tls";
    "dialback";
    "disco";
    "carbons";
    "pep";
    "private";
    "blocklist";
    "vcard4";
    "vcard_legacy";
    "version";
    "uptime";
    "time";
    "ping";
    "register";
    "admin_adhoc";
}

modules_disabled = {
}

allow_registration = true

c2s_require_encryption = false
s2s_require_encryption = false

authentication = "internal_plain"

log = {
    info = "/var/log/prosody/prosody.log";
    error = "/var/log/prosody/prosody.err";
}

certificates = "certs"

VirtualHost "localhost"

Component "conference.localhost" "muc"
EOF
    
    # Restart Prosody
    systemctl restart prosody
    systemctl enable prosody
    
    print_success "Prosody configurato"
}

setup_systemd_service() {
    print_info "Creazione servizio systemd per ACS..."
    
    cat > /etc/systemd/system/acs-server.service <<EOF
[Unit]
Description=ACS Laravel Application Server
After=network.target postgresql.service redis.service

[Service]
Type=simple
User=$APP_USER
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/php $APP_DIR/artisan serve --host=0.0.0.0 --port=$APP_PORT
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF
    
    systemctl daemon-reload
    systemctl enable acs-server
    systemctl start acs-server
    
    print_success "Servizio systemd creato e avviato"
}

setup_firewall() {
    print_info "Configurazione firewall..."
    
    if command -v ufw &> /dev/null; then
        ufw allow 80/tcp
        ufw allow 443/tcp
        ufw allow 7547/tcp  # TR-069 Connection Request
        ufw allow 5222/tcp  # XMPP
        print_success "Firewall UFW configurato"
    elif command -v firewall-cmd &> /dev/null; then
        firewall-cmd --permanent --add-service=http
        firewall-cmd --permanent --add-service=https
        firewall-cmd --permanent --add-port=7547/tcp
        firewall-cmd --permanent --add-port=5222/tcp
        firewall-cmd --reload
        print_success "Firewall firewalld configurato"
    else
        print_warning "Nessun firewall rilevato, configura manualmente le porte"
    fi
}

create_admin_user() {
    print_info "Creazione utente amministratore..."
    
    cd $APP_DIR
    
    # Crea utente admin di default
    sudo -u $APP_USER php artisan db:seed --class=TestUserSeeder --force
    
    print_success "Utente admin creato: admin@acs.local / password"
    print_warning "IMPORTANTE: Cambia la password al primo login!"
}

setup_cron() {
    print_info "Configurazione cron jobs..."
    
    # Aggiungi cron job per Laravel scheduler
    (crontab -u $APP_USER -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -u $APP_USER -
    
    print_success "Cron jobs configurati"
}

set_permissions() {
    print_info "Impostazione permessi..."
    
    cd $APP_DIR
    
    chown -R $APP_USER:www-data .
    chmod -R 755 .
    chmod -R 775 storage bootstrap/cache
    
    print_success "Permessi impostati"
}

display_summary() {
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    print_success "Installazione completata con successo!"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo -e "${BLUE}Informazioni Sistema:${NC}"
    echo "  • URL applicazione: http://$DOMAIN"
    echo "  • Directory installazione: $APP_DIR"
    echo "  • Utente applicazione: $APP_USER"
    echo ""
    echo -e "${BLUE}Credenziali Default:${NC}"
    echo "  • Email: admin@acs.local"
    echo "  • Password: password"
    echo -e "  ${RED}⚠ CAMBIA LA PASSWORD AL PRIMO LOGIN!${NC}"
    echo ""
    echo -e "${BLUE}Database:${NC}"
    echo "  • Nome: $DB_NAME"
    echo "  • Utente: $DB_USER"
    echo "  • Credenziali salvate in: /root/.acs_db_credentials"
    echo ""
    echo -e "${BLUE}Servizi Attivi:${NC}"
    echo "  • ACS Server: systemctl status acs-server"
    echo "  • Queue Workers: systemctl status supervisor"
    echo "  • Nginx: systemctl status nginx"
    echo "  • PostgreSQL: systemctl status postgresql"
    echo "  • Redis: systemctl status redis"
    echo "  • Prosody XMPP: systemctl status prosody"
    echo ""
    echo -e "${BLUE}Log Files:${NC}"
    echo "  • Laravel: $APP_DIR/storage/logs/laravel.log"
    echo "  • Nginx Access: /var/log/nginx/acs-access.log"
    echo "  • Nginx Error: /var/log/nginx/acs-error.log"
    echo "  • Queue Workers: /var/log/supervisor/acs-queue.log"
    echo ""
    echo -e "${BLUE}Comandi Utili:${NC}"
    echo "  • Restart ACS: systemctl restart acs-server"
    echo "  • Clear cache: cd $APP_DIR && php artisan cache:clear"
    echo "  • View logs: tail -f $APP_DIR/storage/logs/laravel.log"
    echo "  • Restart queues: supervisorctl restart all"
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
}

main() {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo -e "${GREEN}   ACS (Auto Configuration Server) - Installazione${NC}"
    echo -e "${BLUE}   Carrier-grade TR-069/TR-369 CPE Management Platform${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    
    check_root
    detect_os
    
    # Installazione dipendenze OS-specific
    case $OS in
        ubuntu|debian)
            install_dependencies_ubuntu
            ;;
        centos|rhel|rocky|almalinux)
            install_dependencies_centos
            ;;
        *)
            print_error "Sistema operativo non supportato: $OS"
            exit 1
            ;;
    esac
    
    create_app_user
    setup_database
    clone_repository
    install_php_dependencies
    configure_environment
    run_migrations
    seed_database
    optimize_laravel
    set_permissions
    configure_nginx
    configure_supervisor
    configure_prosody
    setup_systemd_service
    setup_firewall
    setup_cron
    create_admin_user
    
    display_summary
}

# Esegui installazione
main "$@"
