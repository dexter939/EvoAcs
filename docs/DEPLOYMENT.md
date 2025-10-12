# ACS Production Deployment Guide

Complete guide for deploying the ACS (Auto Configuration Server) to production environments.

## 📋 Table of Contents

- [Prerequisites](#prerequisites)
- [Deployment Methods](#deployment-methods)
- [Systemd Services](#systemd-services)
- [Supervisor Configuration](#supervisor-configuration)
- [Docker Deployment](#docker-deployment)
- [Nginx Configuration](#nginx-configuration)
- [Production Checklist](#production-checklist)
- [Zero-Downtime Deployment](#zero-downtime-deployment)
- [Monitoring & Logging](#monitoring--logging)
- [Troubleshooting](#troubleshooting)

---

## Prerequisites

### System Requirements

- **OS:** Ubuntu 22.04 LTS / Debian 11+ / RHEL 8+
- **PHP:** 8.3+
- **PostgreSQL:** 16+
- **Redis:** 7+
- **Nginx:** 1.20+ (or Apache 2.4+)
- **Supervisor:** 4.2+ or Systemd

### Server Specifications (100K+ devices)

**Minimum:**
- CPU: 8 cores
- RAM: 16 GB
- Disk: 500 GB SSD
- Network: 1 Gbps

**Recommended:**
- CPU: 16 cores
- RAM: 32 GB
- Disk: 1 TB NVMe SSD
- Network: 10 Gbps

---

## Deployment Methods

### Method 1: Systemd Services (Recommended for Production)

Best for: Production servers with systemd (Ubuntu 20.04+, Debian 10+, RHEL 8+)

#### 1. Install System Dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.3 and extensions
sudo apt install -y php8.3-fpm php8.3-cli php8.3-pgsql php8.3-redis \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath \
    php8.3-soap php8.3-gd

# Install PostgreSQL 16
sudo apt install -y postgresql-16 postgresql-client-16

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### 2. Deploy Application

```bash
# Create deployment directory
sudo mkdir -p /var/www/acs
sudo chown www-data:www-data /var/www/acs

# Clone repository
cd /var/www/acs
git clone https://github.com/your-org/acs-system.git .

# Install dependencies
composer install --no-dev --optimize-autoloader

# Configure environment
cp .env.example .env
php artisan key:generate
nano .env  # Edit configuration
```

#### 3. Install Systemd Services

```bash
# Copy service files
sudo cp deploy/systemd/acs-*.service /etc/systemd/system/

# Reload systemd
sudo systemctl daemon-reload

# Enable services
sudo systemctl enable acs-mqtt acs-websocket acs-horizon

# Start services
sudo systemctl start acs-mqtt acs-websocket acs-horizon

# Check status
sudo systemctl status acs-mqtt acs-websocket acs-horizon
```

#### 4. Configure Nginx

```bash
# Copy Nginx configuration
sudo cp deploy/nginx/acs.conf /etc/nginx/sites-available/acs
sudo ln -s /etc/nginx/sites-available/acs /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

### Method 2: Supervisor (Alternative)

Best for: Systems without systemd or Docker

#### 1. Install Supervisor

```bash
sudo apt install -y supervisor
```

#### 2. Configure Supervisor

```bash
# Copy configuration
sudo cp supervisord.conf /etc/supervisor/conf.d/acs.conf

# Update paths in configuration
sudo nano /etc/supervisor/conf.d/acs.conf

# Reload Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start services
sudo supervisorctl start acs:*

# Check status
sudo supervisorctl status
```

---

### Method 3: Docker Compose

Best for: Containerized deployments, development parity

#### 1. Install Docker

```bash
# Install Docker Engine
curl -fsSL https://get.docker.com | sh

# Install Docker Compose
sudo apt install -y docker-compose
```

#### 2. Deploy with Docker

```bash
# Navigate to deploy directory
cd deploy/docker

# Configure environment
cp .env.example .env
nano .env  # Edit configuration

# Build and start containers
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f acs-app
```

#### 3. Run Migrations

```bash
docker-compose exec acs-app php artisan migrate --force
```

---

## Systemd Services

### Service Files Overview

**Location:** `/etc/systemd/system/`

| Service | Command | Description |
|---------|---------|-------------|
| `acs-mqtt.service` | `php artisan usp:mqtt-subscribe` | MQTT subscriber for USP devices |
| `acs-websocket.service` | `php artisan usp:websocket-server` | WebSocket server for direct connections |
| `acs-horizon.service` | `php artisan horizon` | Laravel Horizon queue worker |

### Managing Services

```bash
# Start all services
sudo systemctl start acs-mqtt acs-websocket acs-horizon

# Stop all services
sudo systemctl stop acs-mqtt acs-websocket acs-horizon

# Restart all services
sudo systemctl restart acs-mqtt acs-websocket acs-horizon

# Check status
sudo systemctl status acs-mqtt
sudo systemctl status acs-websocket
sudo systemctl status acs-horizon

# View logs
sudo journalctl -u acs-mqtt -f
sudo journalctl -u acs-websocket -f
sudo journalctl -u acs-horizon -f
```

### Auto-Start on Boot

```bash
sudo systemctl enable acs-mqtt
sudo systemctl enable acs-websocket
sudo systemctl enable acs-horizon
```

---

## Supervisor Configuration

### Configuration File

**Location:** `/etc/supervisor/conf.d/acs.conf`

### Managing Processes

```bash
# Start all ACS processes
sudo supervisorctl start acs:*

# Stop all ACS processes
sudo supervisorctl stop acs:*

# Restart all ACS processes
sudo supervisorctl restart acs:*

# Check status
sudo supervisorctl status

# View logs
sudo tail -f /var/log/supervisor/acs-mqtt.log
sudo tail -f /var/log/supervisor/acs-websocket.log
sudo tail -f /var/log/supervisor/acs-horizon.log
```

---

## Docker Deployment

### Docker Compose Services

```yaml
services:
  - acs-app       # Main application
  - acs-db        # PostgreSQL database
  - acs-redis     # Redis cache & queue
  - acs-nginx     # Nginx reverse proxy
  - mqtt-broker   # Mosquitto MQTT broker
```

### Docker Commands

```bash
# Build and start
docker-compose up -d --build

# Stop all containers
docker-compose down

# View logs
docker-compose logs -f

# Execute commands
docker-compose exec acs-app php artisan migrate
docker-compose exec acs-app php artisan tinker

# Scale workers
docker-compose up -d --scale acs-app=3
```

---

## Nginx Configuration

### SSL/TLS Setup

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d acs.example.com

# Auto-renewal
sudo certbot renew --dry-run
```

### Rate Limiting

Configured in `deploy/nginx/acs.conf`:

- API endpoints: 100 requests/minute
- Device endpoints: 1000 requests/minute

### Load Balancing

Edit `deploy/nginx/acs.conf`:

```nginx
upstream acs_backend {
    server 127.0.0.1:5000 weight=1;
    server 127.0.0.1:5001 weight=1;
    server 127.0.0.1:5002 weight=1;
    keepalive 32;
}
```

---

## Production Checklist

### Pre-Deployment

- [ ] Server meets minimum requirements
- [ ] PostgreSQL 16+ installed and configured
- [ ] Redis installed and running
- [ ] PHP 8.3+ with required extensions
- [ ] SSL/TLS certificates obtained
- [ ] Firewall rules configured
- [ ] Backup strategy defined

### Configuration

- [ ] `.env` file configured for production
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `ACS_API_KEY` changed to secure value (32+ chars)
- [ ] Database credentials configured
- [ ] Redis credentials configured
- [ ] MQTT broker configured (SSL/TLS enabled)
- [ ] WebSocket SSL configured

### Security

- [ ] Change all default passwords
- [ ] Enable SSL/TLS for all services
- [ ] Configure firewall (UFW/iptables)
- [ ] Set up fail2ban for brute-force protection
- [ ] Configure trusted proxies
- [ ] Enable rate limiting
- [ ] Set secure session cookies

### Services

- [ ] Systemd/Supervisor services installed
- [ ] All services auto-start on boot
- [ ] Nginx configured and tested
- [ ] MQTT broker running
- [ ] WebSocket server running
- [ ] Horizon queue worker running

### Testing

- [ ] Database migrations successful
- [ ] Web dashboard accessible
- [ ] API endpoints responding
- [ ] TR-069 endpoint tested
- [ ] TR-369 USP endpoint tested
- [ ] MQTT transport tested
- [ ] WebSocket transport tested

### Monitoring

- [ ] Application logs configured
- [ ] System logs configured
- [ ] Error tracking setup (Sentry)
- [ ] Metrics collection (Prometheus)
- [ ] Uptime monitoring
- [ ] Backup automation

---

## Zero-Downtime Deployment

### Using Deployment Script

```bash
# Set environment variables
export DEPLOY_PATH=/var/www/acs
export REPO_URL=https://github.com/your-org/acs-system.git
export BRANCH=main

# Run deployment
./deploy/scripts/deploy.sh
```

### Deployment Flow

1. **Pre-checks** - Verify system requirements
2. **Clone** - Deploy new release to timestamped directory
3. **Link** - Symlink shared files (.env, storage)
4. **Dependencies** - Install composer packages
5. **Optimize** - Cache configuration, routes, views
6. **Backup** - Database backup before migration
7. **Migrate** - Run database migrations
8. **Activate** - Atomic symlink switch to new release
9. **Restart** - Restart background services
10. **Verify** - Health check validation

### Rollback

```bash
# List releases
ls -la /var/www/acs/releases/

# Rollback to previous release
ln -nfs /var/www/acs/releases/20250112_143022 /var/www/acs/current

# Restart services
sudo systemctl restart acs-*
```

---

## Monitoring & Logging

### Application Logs

```bash
# Laravel logs
tail -f /var/www/acs/storage/logs/laravel.log

# Horizon logs
tail -f /var/www/acs/storage/logs/horizon.log
```

### System Logs

```bash
# Systemd services
sudo journalctl -u acs-mqtt -f
sudo journalctl -u acs-websocket -f
sudo journalctl -u acs-horizon -f

# Supervisor
sudo tail -f /var/log/supervisor/acs-*.log

# Nginx
sudo tail -f /var/log/nginx/acs-access.log
sudo tail -f /var/log/nginx/acs-error.log
```

### Horizon Dashboard

Access at: `https://your-domain.com/horizon`

Monitor:
- Queue throughput
- Failed jobs
- Worker metrics
- Job latency

---

## Troubleshooting

### Services Won't Start

```bash
# Check service status
sudo systemctl status acs-mqtt
sudo systemctl status acs-websocket

# View detailed logs
sudo journalctl -xe -u acs-mqtt

# Check file permissions
ls -la /var/www/acs
sudo chown -R www-data:www-data /var/www/acs
```

### Database Connection Failed

```bash
# Test PostgreSQL connection
psql -h localhost -U acs_user -d acs_database

# Check .env database settings
grep DB_ /var/www/acs/.env

# Verify PostgreSQL is running
sudo systemctl status postgresql
```

### MQTT Connection Failed

```bash
# Test MQTT broker
mosquitto_sub -h localhost -t '#' -v

# Check MQTT settings
grep MQTT_ /var/www/acs/.env

# Verify Mosquitto is running
sudo systemctl status mosquitto
```

### WebSocket Connection Failed

```bash
# Check if WebSocket server is running
ps aux | grep websocket

# Test WebSocket port
telnet localhost 9000

# Check firewall
sudo ufw status
sudo ufw allow 9000/tcp
```

### Queue Not Processing

```bash
# Check Horizon status
php artisan horizon:status

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear and restart
php artisan horizon:terminate
php artisan horizon
```

---

## Performance Tuning

### PHP-FPM Optimization

Edit `/etc/php/8.3/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

### PostgreSQL Optimization

Edit `/etc/postgresql/16/main/postgresql.conf`:

```ini
max_connections = 200
shared_buffers = 4GB
effective_cache_size = 12GB
work_mem = 64MB
maintenance_work_mem = 1GB
```

### Redis Optimization

Edit `/etc/redis/redis.conf`:

```ini
maxmemory 2gb
maxmemory-policy allkeys-lru
save ""
```

---

## Backup & Recovery

### Database Backup

```bash
# Manual backup
pg_dump -h localhost -U acs_user acs_database | gzip > acs_backup_$(date +%Y%m%d).sql.gz

# Automated backup (cron)
0 2 * * * /usr/bin/pg_dump -h localhost -U acs_user acs_database | gzip > /var/backups/acs/db_$(date +\%Y\%m\%d).sql.gz
```

### Restore Database

```bash
gunzip < acs_backup_20250112.sql.gz | psql -h localhost -U acs_user acs_database
```

### Application Backup

```bash
# Backup storage and .env
tar -czf acs_app_backup_$(date +%Y%m%d).tar.gz \
    /var/www/acs/storage \
    /var/www/acs/.env
```

---

## Support

For deployment issues:
- Check [Troubleshooting](#troubleshooting) section
- Review application logs
- Contact DevOps team

**Emergency Contact:** devops@example.com
