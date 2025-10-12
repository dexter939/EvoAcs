# Sistema ACS (Auto Configuration Server)

## Overview
The ACS (Auto Configuration Server) project is a carrier-grade system built with Laravel 11, designed to manage over 100,000 CPE (Customer Premises Equipment) devices. It supports TR-069 (CWMP), TR-369 (USP), TR-104 (VoIP provisioning), and TR-140 (Storage service) protocols for robust device auto-registration, zero-touch provisioning, firmware management, VoIP service configuration, NAS storage management, and a professional web interface. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments.

## User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

## System Architecture

### UI/UX Decisions
The web interface utilizes the Soft UI Dashboard Laravel template, offering a modern, responsive design with a sidebar for navigation and real-time statistics cards. The dashboard includes 12 statistics cards and 4 interactive Chart.js visualizations (Devices Status, Tasks, Diagnostics, Firmware). Device management pages feature filtering, pagination, and modal forms for CRUD operations. The dashboard auto-refreshes every 30 seconds.

### Technical Implementations
- **TR-069 (CWMP) Server**: Dedicated `/tr069` SOAP endpoint with stateful, cookie-based sessions.
- **TR-369 (USP) Support**: Implements next-generation protocol with Protocol Buffers, supporting MQTT, WebSocket, and HTTP as MTPs. Includes full USP message operations (Get, Set, Add, Delete, Operate) and auto-registration. Devices support dual-protocol operation.
- **TR-104 (VoIP) Support**: Full VoIP service provisioning supporting SIP/MGCP/H.323 protocols, including SIP proxy/registrar configuration, codec management, RTP/DSCP settings, STUN support, call statistics, and supplementary services.
- **TR-140 (Storage) Support**: Complete NAS/storage service management with multi-protocol file server support, including RAID configuration, filesystem management, quota management, encryption, and SMART health monitoring.
- **TR-143 (Diagnostics) Support**: Comprehensive remote diagnostics suite including IPPing, TraceRoute, DownloadDiagnostics, UploadDiagnostics, and UDPEcho tests with multi-threaded throughput testing and microsecond-precision timestamps.
- **Database**: PostgreSQL with optimized indexes for high-performance data storage.
- **Asynchronous Queue System**: Laravel Horizon with Redis queues handles provisioning, firmware deployment, and TR-069 requests with retry logic.
- **API Security**: All API v1 endpoints are protected using API Key authentication.
- **RESTful API (v1)**: Provides authenticated endpoints for device management, provisioning, firmware, remote diagnostics, TR-369 USP operations, TR-104 VoIP services, and TR-140 storage services.
- **Web Interface**: Accessible via `/acs/*` for dashboard, device management, provisioning, firmware, and configuration profile management.
- **Scalability**: Achieved through database optimizations and a high-throughput queue system.
- **Configuration**: Uses Laravel environment variables for all settings, with background daemons for USP services.

### Feature Specifications
- **Auto-registration**: Devices automatically identified via Serial Number, OUI, and Product Class.
- **Zero-touch Provisioning**: Automated device setup using configuration profiles.
- **Firmware Management**: Uploading, versioning, and deploying firmware.
- **TR-181 Data Model**: Parameters stored with type, path, writable/readonly status, and last updates.
- **Connection Request**: System-initiated connection requests to devices using HTTP Digest/Basic Auth.
- **Remote Diagnostics (TR-143)**: IPPing, TraceRoute, DownloadDiagnostics, UploadDiagnostics, and UDPEcho tests executed asynchronously. Multi-threaded throughput testing with NumberOfConnections parameter (1-8 TCP connections). Microsecond-precision timestamps for TCP handshake timing and data transfer analysis. Per-connection statistics tracking with detailed speed calculations.
- **TR-369 Subscription/Notification**: Full event subscription system for device notifications via API and Web UI, supporting various notification types.
- **TR-104 VoIP Provisioning**: Complete SIP/MGCP/H.323 service configuration with voice service instances, SIP profiles, VoIP lines, codec selection, RTP settings, STUN configuration, and supplementary services.
- **TR-140 Storage Service**: Full NAS storage management with logical volume configuration, RAID support, filesystem management, quota control, encryption, and multi-protocol file servers.

## External Dependencies
- **PostgreSQL 16+**: Primary database
- **Redis 7+**: Queue driver for Laravel Horizon and WebSocket message routing
- **Laravel Horizon**: Manages and monitors Redis queues
- **Guzzle**: HTTP client for TR-069 Connection Requests
- **Google Protocol Buffers**: v4.32.1 for TR-369 USP message encoding/decoding
- **PHP-MQTT Client**: v1.6.1 for USP broker-based transport
- **Soft UI Dashboard**: Laravel template for the web interface
- **Chart.js**: JavaScript charting library
- **FontAwesome**: Icon library
- **Nginx**: Reverse proxy and web server (production)
- **Supervisor/Systemd**: Process management (production)
- **Docker & Docker Compose**: Containerization (optional)