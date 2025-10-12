# Sistema ACS (Auto Configuration Server)

## Overview
The ACS (Auto Configuration Server) project is a carrier-grade system built with Laravel 11, designed to manage over 100,000 CPE (Customer Premises Equipment) devices. It supports TR-069 (CWMP) and TR-181 protocols, with future plans for TR-369 (USP). The system aims to provide robust device auto-registration, zero-touch provisioning, firmware management, and a professional web interface for comprehensive control and monitoring of CPE devices. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments.

## User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

## System Architecture

### UI/UX Decisions
The web interface utilizes the Soft UI Dashboard Laravel template, offering a modern, responsive design. Navigation is organized via a sidebar, and key statistics are displayed using real-time cards with FontAwesome icons. The dashboard features 12 statistics cards (8 original, 4 new TR-369 protocol cards) with differentiated icons and color-coded badges, and 4 interactive Chart.js visualizations (Devices Status Doughnut, Tasks Bar Chart, Diagnostics Polar Area, Firmware Line Chart). Device management pages include protocol/MTP/status filters with pagination, displaying protocol type and MTP transport badges. Modal forms are used for CRUD operations. The dashboard auto-refreshes every 30 seconds.

### Technical Implementations
- **TR-069 (CWMP) Server**: Dedicated `/tr069` SOAP endpoint handling `Inform` requests and other CWMP operations with stateful, cookie-based sessions.
- **TR-369 (USP) Support**: Implements next-generation protocol with Protocol Buffers encoding and MQTT, WebSocket, and HTTP as MTPs (Message Transfer Protocols). Includes full USP message operations (Get, Set, Add, Delete, Operate) and auto-registration of TR-369 devices. The `/usp` HTTP endpoint receives USP Records. MQTT transport uses `UspMqttService` for broker-based communication, while WebSocket transport uses `UspWebSocketService` for direct connections via a native PHP RFC 6455 server with Redis queue routing. Devices support dual-protocol operation.
- **Database**: PostgreSQL for high-performance data storage, optimized with indexes for over 100K devices. Key tables include `cpe_devices`, `configuration_profiles`, `firmware_versions`, `device_parameters`, `provisioning_tasks`, and `usp_subscriptions`.
- **Asynchronous Queue System**: Laravel Horizon with Redis queues handles tasks like provisioning, firmware deployment, and TR-069 requests with retry logic.
- **API Security**: All API v1 endpoints are protected using API Key authentication via custom middleware.
- **RESTful API (v1)**: Provides authenticated endpoints for device management (CRUD), provisioning (get/set parameters, reboot), firmware (upload, deploy), and remote diagnostics (ping, traceroute, download/upload speed tests). Includes specific API for TR-369 USP operations.
- **Web Interface**: Accessible via `/acs/*`, offering a comprehensive dashboard, device management with filtering, provisioning tools, firmware management, and configuration profile CRUD.
- **Eloquent Models**: Core models include `CpeDevice`, `ConfigurationProfile`, `FirmwareVersion`, `DeviceParameter`, `ProvisioningTask`, `FirmwareDeployment`, `DiagnosticTest`, and `UspSubscription`.
- **Services**: `TR069Service` for TR-069 SOAP requests, `UspMessageService` for TR-369 protobuf encoding/decoding and response creation, `UspMqttService` for MQTT pub/sub, and `UspWebSocketService` for WebSocket connections and message routing.
- **Controllers**: `TR069Controller`, `UspController`, `Api` controllers, and `AcsController`.
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

## External Dependencies
- **PostgreSQL**: Primary database.
- **Redis**: Queue driver for Laravel Horizon and WebSocket message routing.
- **Laravel Horizon**: Manages and monitors Redis queues.
- **Guzzle**: HTTP client for TR-069 Connection Requests.
- **Google Protocol Buffers**: v4.32.1 for TR-369 USP message encoding/decoding.
- **PHP-MQTT Client**: v1.6.1 for USP broker-based transport.
- **Soft UI Dashboard**: Laravel template for the web interface.
- **Chart.js**: JavaScript charting library for dashboard visualizations.
- **FontAwesome**: Icon library for UI elements.