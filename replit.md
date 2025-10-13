# Sistema ACS (Auto Configuration Server)

## Overview
The ACS (Auto Configuration Server) project is a carrier-grade system built with Laravel 11, designed to manage over 100,000 CPE (Customer Premises Equipment) devices. It supports TR-069 (CWMP), TR-369 (USP), TR-104 (VoIP), TR-140 (Storage), TR-143 (Diagnostics), TR-111 (Device Modeling), TR-64 (LAN-Side Configuration), TR-181 (IoT Extension), TR-196 (Femtocell), and TR-135 (STB/IPTV) protocols for comprehensive device auto-registration, zero-touch provisioning, firmware management, VoIP services, NAS storage, remote diagnostics, IoT device management, femtocell RF optimization, and IPTV/VoD provisioning. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments.

## User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

## System Architecture

### UI/UX Decisions
The web interface utilizes the Soft UI Dashboard Laravel template, offering a modern, responsive design with a sidebar for navigation and real-time statistics cards. The dashboard includes 12 statistics cards and 4 interactive Chart.js visualizations (Devices Status, Tasks, Diagnostics, Firmware). Device management pages feature filtering, pagination, and modal forms for CRUD operations. The dashboard auto-refreshes every 30 seconds.

### Technical Implementations
- **TR-069 (CWMP) Server**: Dedicated `/tr069` SOAP endpoint with stateful, cookie-based sessions. XML parsing migrated from SimpleXML to DOMDocument for carrier-grade namespace support (handles cwmp-prefixed DeviceId/EventCode/Parameters from multi-vendor CPE devices).
- **TR-369 (USP) Support**: Implements next-generation protocol with Protocol Buffers, supporting MQTT, WebSocket, and HTTP as MTPs. Includes full USP message operations (Get, Set, Add, Delete, Operate) and auto-registration. Devices support dual-protocol operation.
- **TR-104 (VoIP) Support**: Full VoIP service provisioning supporting SIP/MGCP/H.323 protocols, including SIP proxy/registrar configuration, codec management, RTP/DSCP settings, STUN support, call statistics, and supplementary services.
- **TR-140 (Storage) Support**: Complete NAS/storage service management with multi-protocol file server support, including RAID configuration, filesystem management, quota management, encryption, and SMART health monitoring.
- **TR-143 (Diagnostics) Support**: Comprehensive remote diagnostics suite including IPPing, TraceRoute, DownloadDiagnostics, UploadDiagnostics, and UDPEcho tests with multi-threaded throughput testing and microsecond-precision timestamps.
- **TR-111 (Device Modeling) Support**: Complete device capability discovery with GetParameterNames parsing, vendor-specific detection, parameter tree building with hierarchical structure, and API endpoints for capability queries.
- **TR-64 (LAN-Side Configuration) Support**: UPnP/SSDP-based LAN device discovery, SOAP service invocation, device description parsing, and automatic device management with status tracking.
- **TR-181 (IoT Extension) Support**: Smart home device management supporting ZigBee, Z-Wave, WiFi, BLE, Matter, and Thread protocols. IoT service automation with lighting control, climate management, security monitoring, and energy optimization.
- **TR-196 (Femtocell) Support**: Full femtocell RF management with GPS location tracking, UARFCN/EARFCN configuration, TxPower control, Radio Environment Map (REM) scanning, and Neighbor Cell List (NCL) management for UMTS/LTE/5G networks.
- **TR-135 (STB/IPTV) Support**: Set-Top Box provisioning with support for IP/DVB-T/DVB-S/DVB-C frontends, RTSP/RTP/IGMP/HLS/DASH streaming protocols, QoS monitoring, and real-time streaming session management.
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
- **TR-111 Device Capabilities**: Automated device capability discovery through GetParameterNames parsing. Vendor-specific detection algorithms identify device types and features. Hierarchical parameter tree structure with trailing dot preservation for TR-069 object paths. RESTful API endpoints for capability queries, statistics, and path-based lookups.
- **TR-64 LAN Device Management**: UPnP/SSDP-based discovery processes SSDP announcements and fetches device descriptions. SOAP service invocation framework for controlling UPnP devices. Automatic device tracking with last-seen timestamps and offline detection. API endpoints for device listing, SSDP processing, and SOAP action execution.
- **TR-181 IoT Device Provisioning**: Smart home device management supporting ZigBee, Z-Wave, WiFi, BLE, Matter, and Thread protocols. Device classes include lighting, sensors, thermostats, security, locks, and cameras. IoT service automation framework with lighting control, climate management, security monitoring, and energy optimization. Real-time state updates and automation rule execution.
- **TR-196 Femtocell RF Management**: GPS-based location tracking with latitude/longitude/altitude. UARFCN/EARFCN frequency configuration for UMTS/LTE/5G networks. TxPower control and Radio Environment Map (REM) scanning with RSSI/RSRP/RSRQ measurements. Neighbor Cell List (NCL) management with intra-frequency, inter-frequency, and inter-RAT neighbor tracking. Blacklist support for neighbor cells.
- **TR-135 STB/IPTV Services**: Set-Top Box provisioning with IP/DVB-T/DVB-S/DVB-C frontend support. Streaming protocol management for RTSP/RTP/IGMP/HLS/DASH. Real-time streaming session tracking with QoS metrics (bitrate, packet loss, jitter). Channel list management and codec configuration. Active session monitoring with start/end timestamps.

## Recent Changes (October 2025)

### API Standardization & Test Coverage (COMPLETED ✅)
- **ApiResponse Trait Enhancement**: Added `successDataResponse()` method to standardize API responses across all controllers with `{success: true, data: {...}}` format
- **DiagnosticsController Fully Standardized**: Complete refactoring to use ApiResponse trait methods (`successDataResponse`, `dataResponse`, `paginatedResponse`, `errorResponse`) for consistent API responses across all 5 diagnostic endpoints
- **Syntax Error Fixes**: Corrected critical syntax errors in 8 controllers (UspController, VoiceServiceController, FemtocellController, IotDeviceController, LanDeviceController, ParameterDiscoveryController, StbServiceController, StorageServiceController) - fixed `use ApiResponse;` declaration pattern
- **Migration Corrections**: Updated `diagnostic_tests` table enum to use TR-069 standard values (`IPPing`, `TraceRoute`, `DownloadDiagnostics`, `UploadDiagnostics`, `UDPEcho`) instead of simplified names
- **Factory Alignment**: DiagnosticTestFactory updated to use TR-069 diagnostic type values matching database constraints
- **Field Mapping**: Implemented database-to-API field mapping pattern (`diagnostic_type` → `test_type` accessor) for backward compatibility
- **Test Infrastructure**: Added Queue::fake() in DiagnosticsTest setUp() to prevent Job execution during tests
- **Task Type Enum Fix**: Fixed critical bug where all diagnostic methods used invalid `task_type` values (`diagnostic_ping`, `diagnostic_traceroute`, etc.) - changed to correct enum value `'diagnostic'` with `diagnostic_type` field added to task_data for proper identification
- **Validation Order Consistency**: Standardized all 5 diagnostic endpoints (ping, traceroute, download, upload, udpEcho) to validate input fields BEFORE checking device online status, ensuring proper 422 validation error responses
- **Test Coverage Achievement**: DiagnosticsController now has **10/10 tests passing (100%)** with 78 assertions - up from initial 5/10 (50%)

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