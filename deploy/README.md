# ACS Production Deployment

## Quick Start (Ubuntu/Debian)

```bash
# 1. Download install script
wget https://raw.githubusercontent.com/YOUR_USERNAME/acs-system/main/deploy/install.sh

# 2. Make executable
chmod +x install.sh

# 3. Run as root
sudo ./install.sh
```

## Quick Start (CentOS/RHEL)

```bash
# 1. Download install script
curl -O https://raw.githubusercontent.com/YOUR_USERNAME/acs-system/main/deploy/install.sh

# 2. Make executable
chmod +x install.sh

# 3. Run as root
sudo ./install.sh
```

---

## What Gets Installed

- ✅ **PostgreSQL 16** - Database primario
- ✅ **Redis 7** - Queue driver + cache
- ✅ **Prosody XMPP Server** (porta 6000) - TR-369 USP transport
- ✅ **Nginx** - Web server + reverse proxy
- ✅ **PHP 8.2** + FPM - Runtime Laravel
- ✅ **Composer** - PHP dependency manager
- ✅ **Supervisor** - Process manager per queue workers
- ✅ **Systemd services** - ACS HTTP server
- ✅ **Certbot** - SSL/TLS certificates (Let's Encrypt)

---

## Post-Installation

### 1. Configure Environment

```bash
sudo nano /opt/acs/app/.env
```

**IMPORTANT**: Cambia queste variabili:

```env
# Database (usa password generata durante install)
DB_PASSWORD=CHANGE_THIS_PASSWORD

# XMPP (genera password sicura)
XMPP_PASSWORD=<secure-random-password>

# OpenAI API (se usi AI features)
OPENAI_API_KEY=sk-...
```

### 2. Restart Services

```bash
sudo systemctl restart acs-http
sudo supervisorctl restart acs-worker:*
```

### 3. Access Dashboard

```
http://YOUR_SERVER_IP/acs/dashboard
```

---

## Updating ACS

```bash
sudo /opt/acs/update.sh
```

Questo comando:
- Scarica ultimi aggiornamenti da Git
- Installa dipendenze Composer
- Esegue migrations database
- Riavvia servizi

---

## Service Management

### Check Status

```bash
# Tutti i servizi
sudo systemctl status nginx
sudo systemctl status postgresql
sudo systemctl status redis
sudo systemctl status prosody
sudo systemctl status acs-http

# Queue workers
sudo supervisorctl status
```

### Restart Services

```bash
# ACS HTTP
sudo systemctl restart acs-http

# Queue workers
sudo supervisorctl restart acs-worker:*

# Nginx
sudo systemctl restart nginx

# Prosody XMPP
sudo systemctl restart prosody
```

### View Logs

```bash
# Application logs
sudo tail -f /opt/acs/app/storage/logs/laravel.log

# Queue worker logs
sudo tail -f /opt/acs/app/storage/logs/worker.log

# Nginx access
sudo tail -f /var/log/nginx/access.log

# Nginx errors
sudo tail -f /var/log/nginx/error.log

# Prosody XMPP
sudo tail -f /var/log/prosody/prosody.log
```

---

## SSL/TLS Configuration (Production)

### Option 1: Let's Encrypt (Free)

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d your-domain.com

# Auto-renewal (già configurato)
sudo certbot renew --dry-run
```

### Option 2: Self-Signed (Development)

```bash
# Generate certificate
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/acs-selfsigned.key \
    -out /etc/ssl/certs/acs-selfsigned.crt

# Update Nginx config
sudo nano /etc/nginx/sites-available/acs
# Add SSL configuration

sudo systemctl restart nginx
```

---

## Firewall Configuration

### UFW (Ubuntu/Debian)

```bash
sudo ufw allow 80/tcp       # HTTP
sudo ufw allow 443/tcp      # HTTPS
sudo ufw allow 6000/tcp     # XMPP (se esposto pubblicamente)
sudo ufw allow 22/tcp       # SSH
sudo ufw enable
```

### Firewalld (CentOS/RHEL)

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-port=6000/tcp
sudo firewall-cmd --reload
```

---

## Database Backup

### Manual Backup

```bash
# Backup completo
sudo -u postgres pg_dump acs_production > acs_backup_$(date +%Y%m%d).sql

# Backup compresso
sudo -u postgres pg_dump acs_production | gzip > acs_backup_$(date +%Y%m%d).sql.gz
```

### Automatic Backup (Cron)

```bash
# Edit crontab
sudo crontab -e

# Add daily backup at 2 AM
0 2 * * * sudo -u postgres pg_dump acs_production | gzip > /opt/acs/backups/acs_backup_$(date +\%Y\%m\%d).sql.gz

# Keep only last 7 days
0 3 * * * find /opt/acs/backups -name "acs_backup_*.sql.gz" -mtime +7 -delete
```

### Restore Backup

```bash
# Stop services
sudo systemctl stop acs-http
sudo supervisorctl stop acs-worker:*

# Restore database
sudo -u postgres psql acs_production < acs_backup_20250115.sql

# Start services
sudo systemctl start acs-http
sudo supervisorctl start acs-worker:*
```

---

## Troubleshooting

### ACS HTTP won't start

```bash
# Check logs
sudo journalctl -u acs-http -n 50

# Check permissions
sudo chown -R acs:acs /opt/acs/app
sudo chmod -R 755 /opt/acs/app/storage

# Clear cache
cd /opt/acs/app
sudo -u acs php artisan config:clear
sudo -u acs php artisan cache:clear
```

### Database connection errors

```bash
# Check PostgreSQL is running
sudo systemctl status postgresql

# Test connection
sudo -u postgres psql -d acs_production -c "SELECT 1;"

# Check credentials in .env
sudo nano /opt/acs/app/.env
```

### Queue workers not processing

```bash
# Check Supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart acs-worker:*

# Check logs
sudo tail -f /opt/acs/app/storage/logs/worker.log
```

### XMPP connection issues

```bash
# Check Prosody is running
sudo systemctl status prosody

# Test XMPP port
telnet localhost 6000

# Check Prosody logs
sudo tail -f /var/log/prosody/prosody.log
```

---

## Performance Tuning

### PHP-FPM

```bash
# Edit PHP-FPM pool
sudo nano /etc/php/8.2/fpm/pool.d/www.conf

# Adjust workers
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20

# Restart
sudo systemctl restart php8.2-fpm
```

### PostgreSQL

```bash
# Edit PostgreSQL config
sudo nano /etc/postgresql/16/main/postgresql.conf

# For servers with 8GB+ RAM
shared_buffers = 2GB
effective_cache_size = 6GB
maintenance_work_mem = 512MB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 1.1
work_mem = 10MB
max_worker_processes = 4
max_parallel_workers_per_gather = 2

# Restart
sudo systemctl restart postgresql
```

### Redis

```bash
# Edit Redis config
sudo nano /etc/redis/redis.conf

# Set max memory
maxmemory 2gb
maxmemory-policy allkeys-lru

# Restart
sudo systemctl restart redis
```

---

## System Requirements

### Minimum (Testing)

- CPU: 2 cores
- RAM: 4 GB
- Disk: 20 GB SSD
- OS: Ubuntu 20.04+ / CentOS 8+ / Debian 11+

### Recommended (Production <10,000 devices)

- CPU: 4 cores
- RAM: 8 GB
- Disk: 50 GB SSD
- OS: Ubuntu 22.04 LTS / CentOS Stream 9

### Carrier-Grade (100,000+ devices)

- CPU: 16+ cores
- RAM: 32+ GB
- Disk: 200+ GB NVMe SSD
- OS: Ubuntu 22.04 LTS / RHEL 9
- PostgreSQL: Dedicated server or cluster
- Redis: Dedicated server or cluster
- Load balancer: Nginx / HAProxy

---

## Support

- Documentation: `/docs`
- GitHub Issues: https://github.com/YOUR_USERNAME/acs-system/issues
- Email: support@your-domain.com
