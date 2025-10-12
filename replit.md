# Sistema ACS (Auto Configuration Server)

## Overview
The ACS (Auto Configuration Server) project is a carrier-grade system built with Laravel 11, designed to manage over 100,000 CPE (Customer Premises Equipment) devices. It supports TR-069 (CWMP), TR-369 (USP), TR-104 (VoIP provisioning), and TR-140 (Storage service) protocols for robust device auto-registration, zero-touch provisioning, firmware management, VoIP service configuration, NAS storage management, and a professional web interface. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments.

## User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

## System Architecture

### UI/UX Decisions
The web interface utilizes the Soft UI Dashboard Laravel template, offering a modern, responsive design. Navigation is organized via a sidebar, and key statistics are displayed using real-time cards with FontAwesome icons. The dashboard features 12 statistics cards (8 original, 4 new TR-369 protocol cards) with differentiated icons and color-coded badges, and 4 interactive Chart.js visualizations (Devices Status Doughnut, Tasks Bar Chart, Diagnostics Polar Area, Firmware Line Chart). Device management pages include protocol/MTP/status filters with pagination, displaying protocol type and MTP transport badges. Modal forms are used for CRUD operations. The dashboard auto-refreshes every 30 seconds.

### Technical Implementations
- **TR-069 (CWMP) Server**: Dedicated `/tr069` SOAP endpoint handling `Inform` requests and other CWMP operations with stateful, cookie-based sessions.
- **TR-369 (USP) Support**: Implements next-generation protocol with Protocol Buffers encoding and MQTT, WebSocket, and HTTP as MTPs (Message Transfer Protocols). Includes full USP message operations (Get, Set, Add, Delete, Operate) and auto-registration of TR-369 devices. The `/usp` HTTP endpoint receives USP Records. MQTT transport uses `UspMqttService` for broker-based communication, while WebSocket transport uses `UspWebSocketService` for direct connections via a native PHP RFC 6455 server with Redis queue routing. Devices support dual-protocol operation.
- **TR-104 (VoIP) Support**: Full VoIP service provisioning with SIP/MGCP/H.323 protocol support. Database schema includes `voice_services`, `sip_profiles`, and `voip_lines` tables. Features SIP proxy/registrar configuration, codec management (G.711, G.729, G.722, Opus), RTP/DSCP settings, STUN support, call statistics tracking, and supplementary services (call waiting, forwarding, DND, caller ID). RESTful API for CRUD operations on voice services, SIP profiles, and VoIP lines.
- **TR-140 (Storage) Support**: Complete NAS/storage service management with multi-protocol file server support. Database schema includes `storage_services`, `logical_volumes`, and `file_servers` tables. Features RAID configuration (0/1/5/6/10), filesystem management (ext4/xfs/btrfs/ntfs), quota management, encryption support, and file server protocols (FTP/SFTP/HTTP/HTTPS/SAMBA/NFS). SMART health monitoring, capacity tracking, and server access control with IP whitelisting. RESTful API for storage provisioning and file server management.
- **Database**: PostgreSQL for high-performance data storage, optimized with indexes for over 100K devices. Key tables include `cpe_devices`, `configuration_profiles`, `firmware_versions`, `device_parameters`, `provisioning_tasks`, `usp_subscriptions`, `voice_services`, `sip_profiles`, `voip_lines`, `storage_services`, `logical_volumes`, and `file_servers`.
- **Asynchronous Queue System**: Laravel Horizon with Redis queues handles tasks like provisioning, firmware deployment, and TR-069 requests with retry logic.
- **API Security**: All API v1 endpoints are protected using API Key authentication via custom middleware.
- **RESTful API (v1)**: Provides authenticated endpoints for device management (CRUD), provisioning (get/set parameters, reboot), firmware (upload, deploy), remote diagnostics (ping, traceroute, download/upload speed tests), TR-369 USP operations, TR-104 VoIP service management (voice services, SIP profiles, VoIP lines), and TR-140 storage service management (volumes, file servers).
- **Web Interface**: Accessible via `/acs/*`, offering a comprehensive dashboard, device management with filtering, provisioning tools, firmware management, and configuration profile CRUD.
- **Eloquent Models**: Core models include `CpeDevice`, `ConfigurationProfile`, `FirmwareVersion`, `DeviceParameter`, `ProvisioningTask`, `FirmwareDeployment`, `DiagnosticTest`, `UspSubscription`, `VoiceService`, `SipProfile`, `VoipLine`, `StorageService`, `LogicalVolume`, and `FileServer`.
- **Services**: `TR069Service` for TR-069 SOAP requests, `UspMessageService` for TR-369 protobuf encoding/decoding and response creation, `UspMqttService` for MQTT pub/sub, and `UspWebSocketService` for WebSocket connections and message routing.
- **Controllers**: `TR069Controller`, `UspController`, `VoiceServiceController`, `StorageServiceController`, `Api` controllers, and `AcsController`.
- **Scalability**: Database optimizations and a high-throughput queue system.
- **Configuration**: Uses Laravel environment variables for database, API key, MQTT, and USP settings. Background daemons (`usp:mqtt-subscribe`, `usp:websocket-server`) are essential for production.

### Feature Specifications
- **Auto-registration**: Devices are automatically identified via Serial Number, OUI, and Product Class.
- **Zero-touch Provisioning**: Automated device setup using configuration profiles.
- **Firmware Management**: Uploading, versioning, and deploying firmware.
- **TR-181 Data Model**: Parameters stored with type, path, writable/readonly status, and last updates.
- **Connection Request**: System-initiated connection requests to devices using HTTP Digest/Basic Auth.
- **Remote Diagnostics (TR-143)**: Implementation of IPPing, TraceRoute, DownloadDiagnostics, and UploadDiagnostics, executed asynchronously via queue jobs.
- **TR-369 Subscription/Notification**: Full event subscription system for device notifications via API and Web UI, supporting ValueChange, Event, OperationComplete, ObjectCreation, ObjectDeletion, and OnBoardRequest notifications.
- **TR-104 VoIP Provisioning**: Complete SIP/MGCP/H.323 service configuration with voice service instances, SIP profiles (proxy/registrar/auth), VoIP lines with directory numbers, codec selection, RTP settings, STUN configuration, and supplementary services (call waiting, forwarding, DND). Includes call statistics tracking and line status monitoring.
- **TR-140 Storage Service**: Full NAS storage management with logical volume configuration, RAID support, filesystem management, quota control, encryption, and multi-protocol file servers (FTP/SFTP/HTTP/HTTPS/SAMBA/NFS). SMART health monitoring, capacity tracking, access control with user/IP filtering, and SSL/TLS support for secure transfers.

## Configuration & Deployment

### Environment Configuration
Complete .env.example template with 255 variables documented for production/staging/development environments. Automated setup wizard available via `scripts/setup-env.sh` for interactive configuration. Essential variables include `ACS_API_KEY` (API authentication), `USP_WEBSOCKET_PORT` (WebSocket server), PostgreSQL database settings, MQTT broker configuration, and Redis queue settings. Comprehensive documentation in `docs/ENVIRONMENT_SETUP.md` (363 lines) and `docs/ENVIRONMENT_VARIABLES.md` (342 lines) with variable reference tables, security best practices, and troubleshooting guides.

### Production Deployment
Complete deployment configuration for carrier-grade operations supporting 3 deployment methods:

**1. Systemd (Production-Grade):**
- 3 service files: `acs-mqtt.service`, `acs-websocket.service`, `acs-horizon.service`
- Auto-restart policies, journal logging, dependency management
- Security hardening (NoNewPrivileges, PrivateTmp)
- After network.target, postgresql, redis dependencies

**2. Supervisor (Alternative):**
- Complete `supervisord.conf` with 5 programs: php-fpm, nginx, mqtt-subscriber, websocket-server, horizon
- Process group management for easy control
- Log rotation (10MB max, 10 backups per process)
- Auto-restart on failure with configurable delays

**3. Docker (Containerized):**
- Multi-stage Dockerfile with PHP 8.3-fpm-alpine base
- 5-service docker-compose orchestration: app, PostgreSQL 16, Redis 7, Nginx, MQTT broker (Mosquitto)
- Health checks, volume persistence for data/logs
- Network isolation with bridge networking
- Unix socket communication between PHP-FPM and Nginx (port 9000 exclusive for WebSocket)

**Production Stack Architecture:**
- PHP-FPM (Unix socket `/var/run/php-fpm.sock`, 50 workers, dynamic PM)
- Nginx (reverse proxy, rate limiting, SSL/TLS, load balancing support)
- Background daemons: MQTT subscriber, WebSocket server (port 9000), Horizon queue worker
- **NO development server**: Replaced `php artisan serve` with production-ready PHP-FPM across all deployment methods

**Nginx Configuration** (`deploy/nginx/acs.conf`):
- HTTP to HTTPS redirect with Let's Encrypt support
- SSL/TLS (TLSv1.2, TLSv1.3) with secure ciphers
- Rate limiting: API endpoints (100r/m), device endpoints (1000r/m)
- Load balancer upstream with keepalive (supports multi-server)
- TR-069 SOAP endpoint proxy with SOAPAction header
- TR-369 USP binary endpoint proxy
- WebSocket proxy (separate port 9001 with upgrade headers)
- Security headers (X-Frame-Options, CSP, X-Content-Type-Options)
- Client max body size 100M for firmware uploads

**Zero-Downtime Deployment** (`deploy/scripts/deploy.sh`):
- 10-step automated deployment process
- Timestamped release management (/var/www/acs/releases/YYYYMMDD_HHMMSS)
- Atomic symlink swap for activation
- Database backup before migrations with auto-rollback on failure
- Health check verification post-deployment
- Old release cleanup (keeps last 5 releases)
- Service restart with systemd/supervisor auto-detection
- Pre-deployment checks (git, composer, php, artisan)

**Heroku Support:**
- `Procfile` with heroku-php-nginx buildpack
- Custom Nginx configuration in `deploy/heroku/nginx.conf`
- Environment-aware PORT binding
- Separate process types: web, mqtt, websocket, worker

### Background Daemons
Production requires 3 background services managed via Systemd, Supervisor, or Docker:
- **MQTT Subscriber**: `php artisan usp:mqtt-subscribe` - Listens for TR-369 USP device messages on broker
- **WebSocket Server**: `php artisan usp:websocket-server` - RFC 6455 server on port 9000 for direct device connections
- **Horizon Worker**: `php artisan horizon` - Laravel queue processor for async tasks (provisioning, firmware, diagnostics)

All services configured with auto-restart on failure, proper logging (journald/files), security hardening, and resource limits.

### Security Configuration
**Production Hardening Checklist** (`deploy/PRODUCTION_CHECKLIST.md` - 200+ items):
- Change `ACS_API_KEY` to secure random value (32+ chars via openssl)
- Enable SSL/TLS: MQTT (port 8883), WebSocket (wss://), HTTPS (443)
- Configure firewall: HTTP/HTTPS open, PostgreSQL/Redis localhost-only
- Security headers enforced via Nginx (X-Frame-Options, CSP, etc.)
- PHP-FPM hardening: disabled dangerous functions (exec, system, shell_exec), open_basedir restrictions
- Rate limiting: API (100r/m), devices (1000r/m) via Nginx zones
- Session encryption enabled (`SESSION_ENCRYPT=true`)
- Trusted proxies configured for load balancers
- Fail2ban for SSH/HTTP brute-force protection

### Documentation
- `docs/DEPLOYMENT.md` (13K, 550+ lines): Complete production deployment guide with 3 methods, service management, SSL setup, load balancing, monitoring, performance tuning, backup/recovery, troubleshooting
- `deploy/PRODUCTION_CHECKLIST.md` (9.3K): Comprehensive checklist covering pre-deployment, application setup, security hardening, service config, testing, monitoring, go-live procedures, rollback steps, sign-off section
- Systemd service files with inline documentation
- Nginx configuration with commented examples for load balancing

## External Dependencies
- **PostgreSQL 16+**: Primary database (recommended for 100K+ devices)
- **Redis 7+**: Queue driver for Laravel Horizon and WebSocket message routing
- **Laravel Horizon**: Manages and monitors Redis queues with dashboard
- **Guzzle**: HTTP client for TR-069 Connection Requests
- **Google Protocol Buffers**: v4.32.1 for TR-369 USP message encoding/decoding
- **PHP-MQTT Client**: v1.6.1 for USP broker-based transport
- **Soft UI Dashboard**: Laravel template for the web interface
- **Chart.js**: JavaScript charting library for dashboard visualizations
- **FontAwesome**: Icon library for UI elements
- **Nginx**: Reverse proxy and web server (production)
- **Supervisor/Systemd**: Process management (production)
- **Docker & Docker Compose**: Containerization (optional)
