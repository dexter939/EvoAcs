# 📦 Guida Installazione ACS Server

Sistema carrier-grade per gestione CPE con supporto TR-069 e TR-369 USP.

## 📋 Requisiti di Sistema

### Sistema Operativo
- **Ubuntu** 22.04 LTS o superiore
- **Debian** 11 o superiore
- **CentOS/RHEL** 8 o superiore
- **Rocky Linux/AlmaLinux** 8 o superiore

### Hardware Minimo
- **CPU**: 2 core (4+ consigliato per produzione)
- **RAM**: 4 GB (8+ GB consigliato per produzione)
- **Storage**: 20 GB (SSD consigliato)
- **Network**: Connessione internet stabile

### Software Richiesto (installato automaticamente)
- PHP 8.3+
- PostgreSQL 16+
- Redis 7.0+
- Nginx
- Supervisor
- Prosody XMPP Server
- Composer

## 🚀 Installazione Rapida

### 1. Preparazione

```bash
# Aggiorna il sistema
sudo apt update && sudo apt upgrade -y

# Installa git (se non presente)
sudo apt install -y git

# Imposta l'URL del repository (opzionale, usa default se omesso)
export REPO_URL='https://github.com/dexter939/EvoAcs.git'

# (Opzionale) Imposta dominio personalizzato
export DOMAIN='acs.yourdomain.com'

# (Opzionale) Imposta password database
export DB_PASSWORD='your-secure-password'
```

### 2. Download e Esecuzione Script

```bash
# Download dello script di installazione
wget https://raw.githubusercontent.com/dexter939/EvoAcs/main/install.sh

# Rendi eseguibile lo script
chmod +x install.sh

# Esegui installazione
sudo ./install.sh
```

### 3. Accesso al Sistema

Dopo l'installazione, accedi a:
- **URL**: `http://your-domain` o `http://your-server-ip`
- **Email**: `admin@acs.local`
- **Password**: `password`

⚠️ **IMPORTANTE**: Cambia la password al primo accesso!

## 🔧 Installazione Manuale (Step-by-Step)

### Passo 1: Installazione Dipendenze

#### Ubuntu/Debian
```bash
# Aggiungi repository PHP
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Installa PHP e estensioni
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-pgsql \
    php8.3-redis php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl

# Installa PostgreSQL 16
sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -
sudo apt update
sudo apt install -y postgresql-16

# Installa Redis
sudo apt install -y redis-server

# Installa Nginx e Supervisor
sudo apt install -y nginx supervisor

# Installa Prosody XMPP
sudo apt install -y prosody
```

#### CentOS/RHEL
```bash
# Abilita repository EPEL e Remi
sudo dnf install -y epel-release
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Abilita PHP 8.3
sudo dnf module reset php -y
sudo dnf module enable php:remi-8.3 -y

# Installa pacchetti
sudo dnf install -y php php-fpm php-pgsql php-redis php-mbstring \
    php-xml php-curl php-zip php-bcmath php-gd php-intl \
    postgresql16-server nginx supervisor redis prosody
```

### Passo 2: Configurazione Database

```bash
# Inizializza PostgreSQL (se necessario)
sudo postgresql-setup --initdb

# Avvia servizi
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Crea database
sudo -u postgres psql <<EOF
CREATE USER acs_user WITH PASSWORD 'your-secure-password';
CREATE DATABASE acs_production OWNER acs_user;
GRANT ALL PRIVILEGES ON DATABASE acs_production TO acs_user;
\c acs_production
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
EOF
```

### Passo 3: Clone Repository

```bash
# Crea utente applicazione
sudo useradd -r -m -s /bin/bash acs

# Clone repository
sudo git clone https://github.com/dexter939/EvoAcs.git /opt/acs
sudo chown -R acs:acs /opt/acs
```

### Passo 4: Installazione Dipendenze PHP

```bash
cd /opt/acs
sudo -u acs composer install --no-dev --optimize-autoloader
```

### Passo 5: Configurazione Ambiente

```bash
# Copia e modifica .env
sudo -u acs cp .env.example .env
sudo -u acs php artisan key:generate

# Modifica .env con i tuoi parametri
sudo nano /opt/acs/.env
```

File `.env` esempio:
```env
APP_NAME="ACS Server"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=acs_production
DB_USERNAME=acs_user
DB_PASSWORD=your-secure-password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
```

### Passo 6: Migrazioni e Seeding

```bash
cd /opt/acs
sudo -u acs php artisan migrate --force
sudo -u acs php artisan db:seed --force
```

### Passo 7: Ottimizzazione Laravel

```bash
sudo -u acs php artisan config:cache
sudo -u acs php artisan route:cache
sudo -u acs php artisan view:cache
sudo -u acs php artisan event:cache
```

### Passo 8: Configurazione Nginx

```bash
sudo nano /etc/nginx/sites-available/acs
```

Contenuto:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /opt/acs/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Abilita sito
sudo ln -s /etc/nginx/sites-available/acs /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
```

### Passo 9: Configurazione Supervisor

```bash
sudo nano /etc/supervisor/conf.d/acs.conf
```

Contenuto:
```ini
[program:acs-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /opt/acs/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=acs
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/supervisor/acs-queue.log

[program:acs-horizon]
command=php /opt/acs/artisan horizon
autostart=true
autorestart=true
user=acs
redirect_stderr=true
stdout_logfile=/var/log/supervisor/acs-horizon.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

### Passo 10: Permessi

```bash
cd /opt/acs
sudo chown -R acs:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 storage bootstrap/cache
```

## 🔒 Configurazione SSL (Opzionale ma Consigliato)

### Con Let's Encrypt (Certbot)

```bash
# Installa Certbot
sudo apt install -y certbot python3-certbot-nginx

# Ottieni certificato
sudo certbot --nginx -d your-domain.com

# Auto-rinnovo
sudo systemctl enable certbot.timer
```

## 🔧 Configurazioni Post-Installazione

### Configurazione Firewall

```bash
# UFW (Ubuntu/Debian)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 7547/tcp  # TR-069 Connection Request
sudo ufw allow 5222/tcp  # XMPP
sudo ufw enable

# Firewalld (CentOS/RHEL)
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-port=7547/tcp
sudo firewall-cmd --permanent --add-port=5222/tcp
sudo firewall-cmd --reload
```

### Configurazione Cron

```bash
# Aggiungi scheduler Laravel
sudo crontab -u acs -e
```

Aggiungi:
```
* * * * * cd /opt/acs && php artisan schedule:run >> /dev/null 2>&1
```

## 📊 Verifica Installazione

### Controllo Servizi

```bash
# Verifica Nginx
sudo systemctl status nginx

# Verifica PostgreSQL
sudo systemctl status postgresql

# Verifica Redis
sudo systemctl status redis

# Verifica Queue Workers
sudo supervisorctl status

# Verifica Prosody
sudo systemctl status prosody
```

### Test Applicazione

```bash
# Test connessione database
cd /opt/acs
sudo -u acs php artisan tinker
>>> \DB::connection()->getPdo();

# Test queue
sudo -u acs php artisan queue:work --once

# Visualizza log
tail -f /opt/acs/storage/logs/laravel.log
```

## 🛠️ Risoluzione Problemi

### Problema: Permessi storage/cache

```bash
cd /opt/acs
sudo chown -R acs:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Problema: 502 Bad Gateway

```bash
# Verifica PHP-FPM
sudo systemctl status php8.3-fpm
sudo systemctl restart php8.3-fpm

# Verifica socket
ls -la /var/run/php/
```

### Problema: Queue non funzionanti

```bash
# Restart Supervisor
sudo supervisorctl restart all

# Verifica log
sudo tail -f /var/log/supervisor/acs-queue.log
```

### Problema: Database connection failed

```bash
# Verifica PostgreSQL
sudo systemctl status postgresql

# Test connessione
psql -h localhost -U acs_user -d acs_production

# Verifica .env
cat /opt/acs/.env | grep DB_
```

## 📚 Comandi Utili

```bash
# Clear cache
cd /opt/acs
sudo -u acs php artisan cache:clear
sudo -u acs php artisan config:clear
sudo -u acs php artisan route:clear
sudo -u acs php artisan view:clear

# Restart servizi
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
sudo supervisorctl restart all

# Visualizza log in tempo reale
tail -f /opt/acs/storage/logs/laravel.log

# Backup database
pg_dump -U acs_user acs_production > backup_$(date +%Y%m%d).sql

# Restore database
psql -U acs_user acs_production < backup_20231021.sql
```

## 🔄 Aggiornamenti

```bash
cd /opt/acs
sudo -u acs git pull
sudo -u acs composer install --no-dev
sudo -u acs php artisan migrate --force
sudo -u acs php artisan config:cache
sudo -u acs php artisan route:cache
sudo -u acs php artisan view:cache
sudo supervisorctl restart all
```

## 📞 Supporto

Per problemi o domande:
- Repository: https://github.com/dexter939/EvoAcs
- Issues: https://github.com/dexter939/EvoAcs/issues

## 📄 Licenza

Questo progetto è distribuito sotto licenza [inserire licenza].
