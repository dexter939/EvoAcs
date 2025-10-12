# Environment Configuration Guide

## Quick Setup

### 1. Copy Environment Template
```bash
cp .env.example .env
php artisan key:generate
```

### 2. Required Environment Variables

#### ⚠️ Critical Variables to Configure

Add these to your `.env` file:

```bash
# ACS API Security
ACS_API_KEY=acs-secret-key-change-in-production

# TR-369 USP WebSocket Server
USP_WEBSOCKET_ENABLED=true
USP_WEBSOCKET_HOST=0.0.0.0
USP_WEBSOCKET_PORT=9000
USP_WEBSOCKET_PING_INTERVAL=30

# Database (PostgreSQL Recommended)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=acs_database
DB_USERNAME=acs_user
DB_PASSWORD=your_secure_password

# Redis (Required for Horizon & WebSocket)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
QUEUE_CONNECTION=redis
```

---

## Environment Profiles

### Development Environment

```bash
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug

DB_CONNECTION=sqlite
# OR use local PostgreSQL

MQTT_HOST=test.mosquitto.org
MQTT_PORT=1883

QUEUE_CONNECTION=database
# OR use Redis for testing Horizon
```

### Staging/Production Environment

```bash
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning

# PostgreSQL (Required for 100K+ devices)
DB_CONNECTION=pgsql
DB_HOST=your-db-host.com
DB_PORT=5432
DB_DATABASE=acs_production
DB_USERNAME=acs_prod_user
DB_PASSWORD=strong_random_password

# Dedicated MQTT Broker
MQTT_HOST=mqtt.yourcompany.com
MQTT_PORT=8883  # TLS/SSL
MQTT_USERNAME=acs_controller
MQTT_PASSWORD=broker_password

# Redis (Required)
REDIS_HOST=redis.yourcompany.com
REDIS_PORT=6379
QUEUE_CONNECTION=redis

# WebSocket (Production)
USP_WEBSOCKET_PORT=9000
USP_WEBSOCKET_SSL=true
USP_WEBSOCKET_CERT_PATH=/etc/ssl/certs/websocket.crt
USP_WEBSOCKET_KEY_PATH=/etc/ssl/private/websocket.key

# Performance
HORIZON_WORKERS=10
HORIZON_MAX_PROCESSES=50
DB_POOL_MAX=50
```

---

## Variable Reference

### Application Settings

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `APP_NAME` | Yes | `ACS System` | Application display name |
| `APP_ENV` | Yes | `local` | Environment: local, staging, production |
| `APP_KEY` | Yes | (auto) | Laravel encryption key |
| `APP_DEBUG` | Yes | `true` | Debug mode (false in prod) |
| `APP_URL` | Yes | `http://localhost` | Application URL |

### Database Settings

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `DB_CONNECTION` | Yes | `pgsql` | Database driver (pgsql recommended) |
| `DB_HOST` | Yes | `127.0.0.1` | Database server host |
| `DB_PORT` | Yes | `5432` | Database port |
| `DB_DATABASE` | Yes | - | Database name |
| `DB_USERNAME` | Yes | - | Database username |
| `DB_PASSWORD` | Yes | - | Database password |

### ACS API Settings

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `ACS_API_KEY` | **Yes** | - | API authentication key for `/api/v1/*` |

### TR-069 CWMP Settings

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `TR069_ENABLED` | No | `true` | Enable TR-069 protocol |
| `TR069_SESSION_TIMEOUT` | No | `300` | Session timeout (seconds) |
| `TR069_CONNECTION_USERNAME` | No | `admin` | Connection request username |
| `TR069_CONNECTION_PASSWORD` | No | `admin123` | Connection request password |

### TR-369 USP Settings

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `USP_CONTROLLER_ENDPOINT_ID` | **Yes** | `proto::acs-controller` | Controller endpoint ID (BBF spec) |
| `USP_DEFAULT_OUI` | No | `000000` | Default OUI for auto-registration |
| `USP_DEFAULT_PRODUCT_CLASS` | No | `USP Device` | Default product class |

### MQTT Transport (MTP)

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `MQTT_HOST` | **Yes** | - | MQTT broker hostname |
| `MQTT_PORT` | **Yes** | `1883` | MQTT broker port (1883 or 8883 SSL) |
| `MQTT_CLIENT_ID` | No | `acs-controller` | MQTT client identifier |
| `MQTT_USERNAME` | No | - | MQTT authentication username |
| `MQTT_PASSWORD` | No | - | MQTT authentication password |
| `MQTT_CLEAN_SESSION` | No | `true` | Clean session flag |
| `MQTT_KEEP_ALIVE_INTERVAL` | No | `10` | Keep-alive interval (seconds) |

### WebSocket Transport (MTP)

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `USP_WEBSOCKET_ENABLED` | No | `true` | Enable WebSocket server |
| `USP_WEBSOCKET_HOST` | **Yes** | `0.0.0.0` | WebSocket bind address |
| `USP_WEBSOCKET_PORT` | **Yes** | `9000` | WebSocket server port |
| `USP_WEBSOCKET_PING_INTERVAL` | No | `30` | Heartbeat interval (seconds) |
| `USP_WEBSOCKET_SSL` | No | `false` | Enable SSL/TLS (production) |

### HTTP Transport (MTP)

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `USP_HTTP_ENABLED` | No | `true` | Enable HTTP polling endpoint |
| `USP_HTTP_REQUEST_TIMEOUT` | No | `3600` | Pending request expiration (seconds) |

### Queue & Background Jobs

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `QUEUE_CONNECTION` | **Yes** | `redis` | Queue driver (redis recommended) |
| `REDIS_HOST` | **Yes** | `127.0.0.1` | Redis server host |
| `REDIS_PORT` | **Yes** | `6379` | Redis server port |
| `HORIZON_WORKERS` | No | `5` | Laravel Horizon worker count |
| `HORIZON_MAX_PROCESSES` | No | `10` | Max concurrent processes |

---

## Setup Checklist

### Local Development

- [x] Copy `.env.example` to `.env`
- [x] Generate app key: `php artisan key:generate`
- [x] Configure database (SQLite or PostgreSQL)
- [x] Set `ACS_API_KEY` for API access
- [x] Configure MQTT broker (test.mosquitto.org OK)
- [x] Set `USP_WEBSOCKET_PORT=9000`
- [x] Run migrations: `php artisan migrate`
- [x] Start server: `php artisan serve --host=0.0.0.0 --port=5000`

### Production Deployment

- [ ] Set `APP_ENV=production`
- [ ] Disable debug: `APP_DEBUG=false`
- [ ] **Change** `ACS_API_KEY` to secure random string
- [ ] Configure PostgreSQL database
- [ ] Configure dedicated MQTT broker
- [ ] Configure Redis server
- [ ] Enable WebSocket SSL: `USP_WEBSOCKET_SSL=true`
- [ ] Set trusted proxies if using load balancer
- [ ] Configure rate limiting
- [ ] Set up monitoring (Sentry, metrics)
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Optimize: `php artisan optimize`

---

## Security Best Practices

### 🔒 Secrets Management

1. **Never commit `.env` to git** - Already in `.gitignore`
2. **Rotate API keys regularly**
3. **Use strong database passwords** (16+ chars, random)
4. **Enable SSL/TLS** in production:
   - MQTT: Port 8883 with TLS
   - WebSocket: wss:// protocol
   - HTTPS: Enable app SSL

### 🛡️ Production Hardening

```bash
# Disable debugging
APP_DEBUG=false
LOG_LEVEL=warning

# Secure session
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

# Rate limiting
API_RATE_LIMIT=100
DEVICE_RATE_LIMIT=1000

# Trusted proxies (if using load balancer)
TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12
```

---

## Troubleshooting

### Missing Variables Error

```bash
# Error: "ACS_API_KEY not found in environment"
# Solution: Add to .env
ACS_API_KEY=your-secret-key-here
```

### WebSocket Connection Failed

```bash
# Check if port is set
grep USP_WEBSOCKET_PORT .env

# If missing, add:
USP_WEBSOCKET_PORT=9000
```

### MQTT Connection Failed

```bash
# Verify MQTT settings
grep MQTT_ .env

# Test connection
php artisan usp:test-mqtt
```

### Database Connection Error

```bash
# PostgreSQL: Verify credentials
grep DB_ .env

# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## Environment Migration

### From SQLite to PostgreSQL

1. **Backup SQLite database:**
   ```bash
   cp database/database.sqlite database/backup.sqlite
   ```

2. **Update .env:**
   ```bash
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=acs_database
   DB_USERNAME=acs_user
   DB_PASSWORD=secure_password
   ```

3. **Run migrations:**
   ```bash
   php artisan migrate:fresh
   ```

4. **Import data** (optional):
   ```bash
   # Export from SQLite, import to PostgreSQL
   # Custom migration required
   ```

---

## Quick Reference Card

### Minimal .env for Development

```bash
APP_KEY=base64:xxxxx
DB_CONNECTION=sqlite
ACS_API_KEY=dev-api-key
MQTT_HOST=test.mosquitto.org
MQTT_PORT=1883
USP_CONTROLLER_ENDPOINT_ID=proto::acs-dev
USP_WEBSOCKET_PORT=9000
QUEUE_CONNECTION=database
```

### Minimal .env for Production

```bash
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:xxxxx
DB_CONNECTION=pgsql
DB_HOST=prod-db.example.com
DB_DATABASE=acs_prod
DB_USERNAME=acs_prod_user
DB_PASSWORD=strong_random_password
ACS_API_KEY=prod-secret-key-32-chars-min
MQTT_HOST=mqtt.example.com
MQTT_PORT=8883
MQTT_USERNAME=acs_prod
MQTT_PASSWORD=broker_password
USP_CONTROLLER_ENDPOINT_ID=proto::acs-prod-01
USP_WEBSOCKET_PORT=9000
USP_WEBSOCKET_SSL=true
REDIS_HOST=redis.example.com
QUEUE_CONNECTION=redis
```
