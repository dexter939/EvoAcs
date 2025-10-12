# ACS - Auto Configuration Server

**Carrier-grade TR-069 (CWMP) and TR-369 (USP) device management system built with Laravel 11**

[![PHP](https://img.shields.io/badge/PHP-8.3-blue.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-11-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 🚀 Features

### Multi-Protocol Support
- ✅ **TR-069 (CWMP)** - Legacy device management protocol
- ✅ **TR-369 (USP)** - Next-generation User Services Platform
- ✅ **Dual Protocol** - Devices can operate with both protocols

### TR-369 USP Transport Layers (MTP)
- 🔌 **MQTT** - Broker-based pub/sub communication
- 🌐 **WebSocket** - Direct device connections (RFC 6455 compliant)
- 📡 **HTTP** - Polling-based for restricted networks

### Core Capabilities
- 📊 **100,000+ Device Management** - Scalable architecture
- ⚡ **Zero-Touch Provisioning** - Automated device configuration
- 🔄 **Firmware Management** - Upload, version, and deploy firmware
- 📈 **Real-time Monitoring** - Dashboard with metrics and charts
- 🔔 **Subscribe/Notify** - Event-based device notifications
- 🧪 **Remote Diagnostics** - Ping, traceroute, speed tests (TR-143)
- 🔐 **API Security** - Key-based authentication
- 📡 **Queue Processing** - Laravel Horizon for async tasks

---

## 📋 Quick Start

### Prerequisites
- PHP 8.3+
- PostgreSQL 16+ (or SQLite for development)
- Redis (for queue processing)
- Composer
- MQTT Broker (optional, test.mosquitto.org available)

### Installation

#### 1. Clone & Install Dependencies
```bash
git clone https://github.com/your-org/acs-system.git
cd acs-system
composer install
```

#### 2. Environment Setup (Automated)
```bash
# Run interactive setup wizard
./scripts/setup-env.sh
```

**Or Manual Setup:**
```bash
cp .env.example .env
php artisan key:generate

# Edit .env with your configuration
nano .env
```

See [Environment Setup Guide](docs/ENVIRONMENT_SETUP.md) for detailed configuration.

#### 3. Database Migration
```bash
# Run database migrations
php artisan migrate

# Optional: Seed sample data
php artisan db:seed
```

#### 4. Start Services

**Development Server:**
```bash
php artisan serve --host=0.0.0.0 --port=5000
```

**Background Services (Optional):**
```bash
# Terminal 1: MQTT Subscriber (for MQTT transport)
php artisan usp:mqtt-subscribe

# Terminal 2: WebSocket Server (for WebSocket transport)
php artisan usp:websocket-server

# Terminal 3: Queue Worker (for async tasks)
php artisan horizon
```

#### 5. Access Dashboard
```
http://localhost:5000/acs/dashboard
```

---

## 🔧 Configuration

### Essential Environment Variables

```bash
# Application
APP_KEY=                          # Auto-generated

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=acs_database
DB_USERNAME=acs_user
DB_PASSWORD=your_password

# ACS API Key (REQUIRED)
ACS_API_KEY=your-secret-api-key

# TR-369 USP
USP_CONTROLLER_ENDPOINT_ID=proto::acs-controller
USP_WEBSOCKET_PORT=9000

# MQTT Broker
MQTT_HOST=test.mosquitto.org
MQTT_PORT=1883

# Redis (for queues & WebSocket)
REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
```

📖 **Complete Reference:** [Environment Variables Documentation](docs/ENVIRONMENT_VARIABLES.md)

---

## 📡 Protocol Endpoints

### TR-069 (CWMP)
```
POST /tr069
```
SOAP-based endpoint for TR-069 device communication.

### TR-369 (USP)
```
POST /usp
```
Binary protobuf endpoint for USP Records (HTTP MTP).

### RESTful API
```
POST /api/v1/usp/devices/{device}/get-params
POST /api/v1/usp/devices/{device}/set-params
POST /api/v1/usp/devices/{device}/operate
POST /api/v1/usp/devices/{device}/add-object
DELETE /api/v1/usp/devices/{device}/delete-object
POST /api/v1/usp/devices/{device}/reboot
POST /api/v1/usp/devices/{device}/subscribe
DELETE /api/v1/usp/devices/{device}/subscriptions/{id}
```

**Authentication:** API Key required in `X-API-Key` header.

📖 **Complete API Docs:** [USP API Documentation](docs/USP_API_Documentation.md)

---

## 🏗️ Architecture

### System Components

```
┌─────────────────────────────────────────────────────┐
│                   ACS Controller                    │
│                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────┐ │
│  │  TR-069 SOAP │  │  TR-369 USP  │  │  RESTful  │ │
│  │   Endpoint   │  │   Endpoint   │  │    API    │ │
│  └──────────────┘  └──────────────┘  └───────────┘ │
│                                                     │
│  ┌──────────────────────────────────────────────┐  │
│  │         Transport Layer (MTP)                │  │
│  │  ┌──────┐  ┌──────────┐  ┌────────────────┐ │  │
│  │  │ MQTT │  │ WebSocket│  │  HTTP Polling  │ │  │
│  │  └──────┘  └──────────┘  └────────────────┘ │  │
│  └──────────────────────────────────────────────┘  │
│                                                     │
│  ┌──────────────────────────────────────────────┐  │
│  │         Queue System (Laravel Horizon)       │  │
│  │         Database (PostgreSQL)                │  │
│  │         Cache & Session (Redis)              │  │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
                         ↕
┌─────────────────────────────────────────────────────┐
│                CPE Devices (100K+)                  │
│   TR-069 CWMP Devices  │  TR-369 USP Devices       │
└─────────────────────────────────────────────────────┘
```

### Key Technologies
- **Backend:** Laravel 11, PHP 8.3
- **Database:** PostgreSQL 16 (optimized for 100K+ devices)
- **Queue:** Redis + Laravel Horizon
- **Protocols:** Protocol Buffers (TR-369), SOAP (TR-069)
- **Transport:** MQTT (php-mqtt/laravel-client), WebSocket (native PHP), HTTP
- **Frontend:** Soft UI Dashboard, Chart.js
- **Icons:** FontAwesome 6

---

## 🧪 Testing

### Manual Testing Scripts
```bash
# Test USP API endpoints
./tests/test_usp_api.sh

# Test Subscribe/Notify pattern
./tests/test_subscribe_notify.sh
```

### USP Protocol Testing
```bash
# Test message encoding/decoding
php artisan usp:test-message

# Test MQTT communication
php artisan usp:test-mqtt

# Test controller functionality
php artisan usp:test-controller
```

---

## 📊 Dashboard Features

### Statistics Cards (12 total)
- **Legacy Metrics:** Devices Online, Tasks Pending, Firmware Deploys, Tests, etc.
- **TR-069 Metrics:** CWMP device count
- **TR-369 Metrics:** USP device count, MQTT/HTTP transport statistics

### Charts (4 interactive)
- Device Status (Doughnut Chart)
- Provisioning Tasks (Bar Chart)
- Diagnostics (Polar Area Chart)
- Firmware Deployments (Line Chart)

### Device Management
- Protocol filtering (TR-069 / TR-369)
- MTP transport filtering (MQTT / WebSocket / HTTP)
- Status badges and icons
- Real-time auto-refresh (30s)

---

## 📖 Documentation

- [Environment Setup Guide](docs/ENVIRONMENT_SETUP.md) - Detailed configuration
- [Environment Variables](docs/ENVIRONMENT_VARIABLES.md) - Complete variable reference
- [USP API Documentation](docs/USP_API_Documentation.md) - RESTful API guide
- [Postman Collection](docs/USP_API_Collection.postman.json) - API testing

---

## 🔐 Security

### Production Checklist
- [ ] Change `ACS_API_KEY` to strong random value (32+ chars)
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Enable SSL/TLS for MQTT (port 8883)
- [ ] Enable WebSocket SSL (`USP_WEBSOCKET_SSL=true`)
- [ ] Configure trusted proxies for load balancer
- [ ] Implement multi-tenant authentication (roadmap)
- [ ] Enable rate limiting
- [ ] Configure monitoring (Sentry, metrics)

### API Authentication
All `/api/v1/*` endpoints require API key:
```bash
curl -H "X-API-Key: your-api-key" \
  http://localhost:5000/api/v1/usp/devices
```

---

## 🚀 Production Deployment

### Background Services (Required)

Use a process manager (systemd, supervisor, or PM2):

```ini
# /etc/systemd/system/acs-mqtt.service
[Unit]
Description=ACS MQTT Subscriber
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/acs
ExecStart=/usr/bin/php artisan usp:mqtt-subscribe
Restart=always

[Install]
WantedBy=multi-user.target
```

Repeat for:
- `acs-websocket.service` → `php artisan usp:websocket-server`
- `acs-horizon.service` → `php artisan horizon`

### Performance Optimization
```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

---

## 🛠️ Troubleshooting

### Common Issues

**1. WebSocket connection failed**
```bash
# Check if port is configured
grep USP_WEBSOCKET_PORT .env

# Start WebSocket server
php artisan usp:websocket-server
```

**2. MQTT connection failed**
```bash
# Test MQTT connection
php artisan usp:test-mqtt

# Check broker settings
grep MQTT_ .env
```

**3. Queue not processing**
```bash
# Check queue configuration
grep QUEUE_CONNECTION .env

# Start Horizon
php artisan horizon
```

**4. API authentication failed**
```bash
# Verify API key is set
grep ACS_API_KEY .env

# Use in request
curl -H "X-API-Key: your-key" http://localhost:5000/api/v1/...
```

---

## 🗺️ Roadmap

### Current Version (v1.0)
- ✅ TR-069 CWMP support
- ✅ TR-369 USP support
- ✅ MQTT/WebSocket/HTTP transport
- ✅ Subscribe/Notify pattern
- ✅ RESTful API
- ✅ Web dashboard

### Future Enhancements
- [ ] Multi-tenant authentication system
- [ ] Bulk device operations
- [ ] Device grouping and tagging
- [ ] Scheduled provisioning (cron-based)
- [ ] Advanced analytics and reporting
- [ ] Prometheus metrics export
- [ ] High-availability clustering
- [ ] Rate limiting and throttling

---

## 📄 License

This project is licensed under the MIT License.

---

## 🙏 Acknowledgments

- [Broadband Forum](https://www.broadband-forum.org/) - TR-069 and TR-369 specifications
- [Laravel](https://laravel.com/) - PHP framework
- [Soft UI Dashboard](https://www.creative-tim.com/product/soft-ui-dashboard-laravel) - Admin template
- [Chart.js](https://www.chartjs.org/) - Charting library

---

## 📞 Support

For issues and questions:
- Open an issue on GitHub
- Check [Documentation](docs/)
- Review [Troubleshooting Guide](#-troubleshooting)

---

**Built with ❤️ for telecom operators managing large-scale CPE deployments**
