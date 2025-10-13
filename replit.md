# Sistema ACS (Auto Configuration Server)

## Overview
The ACS (Auto Configuration Server) project is a carrier-grade system built with Laravel 11, designed to manage over 100,000 CPE (Customer Premises Equipment) devices. It supports a comprehensive suite of protocols including TR-069 (CWMP), TR-369 (USP), TR-104 (VoIP), TR-140 (Storage), TR-143 (Diagnostics), TR-111 (Device Modeling), TR-64 (LAN-Side Configuration), TR-181 (IoT Extension), TR-196 (Femtocell), and TR-135 (STB/IPTV). Its core functionalities include device auto-registration, zero-touch provisioning, firmware management, and advanced remote diagnostics. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments.

## User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

## System Architecture

### UI/UX Decisions
The web interface uses the Soft UI Dashboard Laravel template, providing a modern, responsive design with navigation and real-time statistics. It includes 12 statistics cards and 4 interactive Chart.js visualizations (Devices Status, Tasks, Diagnostics, Firmware). Device management features filtering, pagination, and modal forms for CRUD operations. The dashboard auto-refreshes every 30 seconds.

### Technical Implementations
- **TR-069 (CWMP) Server**: Dedicated `/tr069` SOAP endpoint with stateful, cookie-based sessions. Uses DOMDocument for robust XML parsing and carrier-grade namespace support.
- **TR-369 (USP) Support**: Implements next-generation protocol with Protocol Buffers, supporting MQTT, WebSocket, and HTTP as MTPs, including full USP message operations and auto-registration.
- **TR-104 (VoIP) Support**: Full VoIP service provisioning supporting SIP/MGCP/H.323 protocols, including SIP proxy/registrar configuration, codec management, and supplementary services.
- **TR-140 (Storage) Support**: Complete NAS/storage service management with multi-protocol file server support, RAID configuration, filesystem management, and quota management.
- **TR-143 (Diagnostics) Support**: Comprehensive remote diagnostics suite including IPPing, TraceRoute, DownloadDiagnostics, UploadDiagnostics, and UDPEcho tests with multi-threaded throughput testing and microsecond-precision timestamps.
- **TR-111 (Device Modeling) Support**: Complete device capability discovery with GetParameterNames parsing, vendor-specific detection, and hierarchical parameter tree building.
- **TR-64 (LAN-Side Configuration) Support**: UPnP/SSDP-based LAN device discovery, SOAP service invocation, and automatic device management.
- **TR-181 (IoT Extension) Support**: Smart home device management supporting ZigBee, Z-Wave, WiFi, BLE, Matter, and Thread protocols, including IoT service automation.
- **TR-196 (Femtocell) Support**: Full femtocell RF management with GPS location tracking, UARFCN/EARFCN configuration, TxPower control, and Radio Environment Map (REM) scanning.
- **TR-135 (STB/IPTV) Support**: Set-Top Box provisioning with support for various frontends and streaming protocols (RTSP/RTP/IGMP/HLS/DASH), including QoS monitoring.
- **Database**: PostgreSQL with optimized indexes.
- **Asynchronous Queue System**: Laravel Horizon with Redis queues for provisioning, firmware deployment, and TR-069 requests.
- **API Security**: All API v1 endpoints are protected using API Key authentication.
- **RESTful API (v1)**: Provides authenticated endpoints for device management, provisioning, firmware, remote diagnostics, USP operations, VoIP services, and storage services.
- **Web Interface**: Accessible via `/acs/*` for dashboard, device management, and configuration.
- **Scalability**: Achieved through database optimizations and a high-throughput queue system.
- **Configuration**: Uses Laravel environment variables for all settings.

### Feature Specifications
- **Auto-registration**: Devices automatically identified via Serial Number, OUI, and Product Class.
- **Zero-touch Provisioning**: Automated device setup using configuration profiles.
- **Firmware Management**: Uploading, versioning, and deploying firmware.
- **TR-181 Data Model**: Parameters stored with type, path, writable/readonly status, and last updates.
- **Connection Request**: System-initiated connection requests to devices using HTTP Digest/Basic Auth.
- **TR-369 Subscription/Notification**: Full event subscription system for device notifications via API and Web UI.
- **TR-104 VoIP Provisioning**: Complete SIP/MGCP/H.323 service configuration with voice service instances, SIP profiles, and codec selection.
- **TR-140 Storage Service**: Full NAS storage management with logical volume configuration, RAID support, and filesystem management.
- **TR-111 Device Capabilities**: Automated device capability discovery through GetParameterNames parsing and vendor-specific detection.
- **TR-64 LAN Device Management**: UPnP/SSDP-based discovery processes and SOAP service invocation for controlling UPnP devices.
- **TR-181 IoT Device Provisioning**: Smart home device management for various protocols, including lighting, sensors, and security.
- **TR-196 Femtocell RF Management**: GPS-based location tracking, frequency configuration, TxPower control, and Radio Environment Map (REM) scanning.
- **TR-135 STB/IPTV Services**: Set-Top Box provisioning with frontend and streaming protocol support, and real-time QoS monitoring.

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