# Sistema ACS (Auto Configuration Server)

## Overview
The ACS (Auto Configuration Server) project is a carrier-grade system built with Laravel 11, designed to manage over 100,000 CPE (Customer Premises Equipment) devices. It supports TR-069 (CWMP) and TR-181 protocols, with future plans for TR-369 (USP). The system aims to provide robust device auto-registration, zero-touch provisioning, firmware management, and a professional web interface for comprehensive control and monitoring of CPE devices. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments.

## User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

## Recent Developments (October 2025)
### TR-369 (USP) Protocol Support - IN PROGRESS
The system now supports next-generation TR-369 User Services Platform alongside TR-069 for modern device management:

**Completed Features:**
- ✅ **Protocol Buffers Integration**: Google protobuf v4.32.1 with 44 generated USP message classes
- ✅ **MQTT Transport Layer**: php-mqtt/laravel-client v1.6.1 for broker-based communication
- ✅ **Dual-Protocol Database**: Added protocol_type, usp_endpoint_id, mqtt_client_id, mtp_type fields to cpe_devices
- ✅ **USP Message Service**: Complete encoding/decoding for GET, SET, ADD, DELETE, OPERATE operations with correct response types (GET_RESP, SET_RESP, OPERATE_RESP, ADD_RESP, DELETE_RESP, ERROR)
- ✅ **Binary Serialization**: Protobuf message and Record wrapping for MTP transport
- ✅ **Device Scopes**: Query scopes for tr069(), tr369(), uspMqtt() filtering
- ✅ **USP Controller**: HTTP endpoint at /usp for receiving USP Records, auto-registration of TR-369 devices, request handlers for GET/SET/OPERATE/ADD/DELETE/NOTIFY operations
- ✅ **MQTT Transport Layer**: UspMqttService for broker-based pub/sub, UspMqttSubscriber daemon for receiving device messages, topic structure (controller: usp/controller/{id}/+, agent: usp/agent/{id}/request), msg_id tracking for request/response correlation

- ✅ **Web Interface TR-369 Support**: Dashboard statistics cards (TR-069 CWMP count, TR-369 USP count, USP via MQTT, USP via HTTP), device management page with protocol/MTP/status filters, protocol column with differentiated badges (TR-369 green, TR-069 blue), MTP type badges (MQTT orange, HTTP cyan), device icons (satellite-dish for USP, router for CWMP)

- ✅ **RESTful API for USP Operations**: Complete API v1 implementation at `/api/v1/usp/devices/{device}/*` with 6 authenticated endpoints (get-params, set-params, operate, add-object, delete-object, reboot). Dual MTP support: MQTT for immediate device communication, HTTP for polling-based devices. HTTP MTP uses `usp_pending_requests` table for request storage with 1-hour expiration, devices poll via GET /usp endpoint with auto-delivery tracking. Parameter format conversion (flat API -> grouped USP). Full Postman collection and markdown documentation included.

- ✅ **Subscribe/Notify Event Pattern**: Complete TR-369 event subscription system for device notifications. Database tracking via `usp_subscriptions` table with UspSubscription model. API endpoints for subscription CRUD (create at POST /api/v1/usp/devices/{device}/subscribe, list at GET /subscriptions, delete at DELETE /subscriptions/{id}). UspController processes 6 notification types (ValueChange, Event, OperationComplete, ObjectCreation, ObjectDeletion, OnBoardRequest) with SendResp compliance. Web UI for subscription management integrated in device detail page. UspMqttService includes sendSubscriptionRequest() and sendDeleteRequest() methods. End-to-end test script validates full lifecycle (create/list/verify/delete) with notification_retry flag support.

## System Architecture

### UI/UX Decisions
The web interface utilizes the Soft UI Dashboard Laravel template, offering a modern, responsive design. Navigation is organized via a sidebar, and key statistics are displayed using real-time cards with FontAwesome icons. The dashboard features **12 statistics cards**: 8 original cards (Devices Online, Task Pending, Firmware Deploy, Task Completati, Test Diagnostici, Profili Attivi, Versioni Firmware, Parametri TR-181) plus **4 new TR-369 protocol cards** (Dispositivi TR-069 CWMP, Dispositivi TR-369 USP, USP via MQTT broker, USP via HTTP diretto) with differentiated icons (server, satellite-dish, exchange-alt, globe) and color-coded badges. The dashboard includes **4 interactive Chart.js visualizations** (Devices Status Doughnut, Tasks Bar Chart, Diagnostics Polar Area, Firmware Line Chart) for real-time monitoring. Device management page includes **protocol/MTP/status filters** with pagination preservation, displays protocol type column with TR-069 (blue) vs TR-369 (green) badges, and shows MTP transport badges (MQTT orange, HTTP cyan) for USP devices. Tables include pagination and status badges for clarity. Modal forms are used for CRUD operations and device actions like provisioning and rebooting. The dashboard auto-refreshes every 30 seconds. Assets use relative paths for compatibility across various environments.

### Technical Implementations
- **TR-069 (CWMP) Server**: Features a dedicated `/tr069` SOAP endpoint for device communication, handling `Inform` requests, and managing stateful TR-069 sessions with cookie-based tracking. It supports `GetParameterValues`, `SetParameterValues`, `Reboot`, and `Download` operations.
- **TR-369 (USP) Support**: Next-generation protocol implementation with Protocol Buffers encoding, MQTT/WebSocket transport, and full USP message operations (Get, Set, Add, Delete, Operate). HTTP endpoint `/usp` receives USP Records via binary POST. MQTT transport layer with UspMqttService enables broker-based communication (subscribe/publish) on standard USP topics. Auto-registration of TR-369 devices (protocol_type=tr369, default OUI=000000, mtp_type=mqtt/http) with correct message types (GET_RESP, SET_RESP, OPERATE_RESP) and msg_id tracking. Devices support dual-protocol operation with protocol_type field for TR-069/TR-369 selection.
- **Database**: PostgreSQL is used for high-performance data storage, optimized with indexes for managing over 100K devices. Key tables include `cpe_devices`, `configuration_profiles`, `firmware_versions`, `device_parameters`, `provisioning_tasks`, and `firmware_deployments`.
- **Asynchronous Queue System**: Laravel Horizon is configured with Redis queues to handle asynchronous tasks such as `ProcessProvisioningTask`, `ProcessFirmwareDeployment`, and `SendTR069Request`. Tasks include retry logic and timeouts for robustness.
- **API Security**: All API v1 endpoints are protected using API Key authentication via a custom middleware, requiring an `X-API-Key` header or `api_key` query parameter.
- **RESTful API (v1)**: Provides authenticated endpoints for managing devices (CRUD), provisioning (get/set parameters, reboot), firmware (upload, deploy), and remote diagnostics (ping, traceroute, download/upload speed tests).
- **Web Interface**: A comprehensive web interface is accessible via `/acs/*`, offering a dashboard with 12 statistics cards (8 legacy + 4 TR-369 protocol cards) and 4 Chart.js graphs (doughnut, bar, polar area, line), device management with protocol/MTP/status filters, provisioning tools, firmware management, and configuration profile CRUD functionalities. All dashboard statistics are calculated in the controller for performance and accuracy, including distinct TR-181 parameter counts and TR-069/TR-369 device counts using model scopes (tr069(), tr369(), uspMqtt()). Device list shows differentiated protocol badges (TR-069 blue, TR-369 green), MTP transport badges (MQTT orange, HTTP cyan), and protocol-specific icons (router for CWMP, satellite-dish for USP).
- **Eloquent Models**: Core models include `CpeDevice`, `ConfigurationProfile`, `FirmwareVersion`, `DeviceParameter`, `ProvisioningTask`, `FirmwareDeployment`, `DiagnosticTest`, and `UspSubscription`.
- **Services**: `TR069Service` handles TR-069 SOAP requests, `UspMessageService` manages TR-369 protobuf encoding/decoding, Record wrapping for MTP transport, and correct response message creation for each USP operation type. `UspMqttService` provides MQTT pub/sub for broker-based USP device communication with methods sendGetRequest(), sendSetRequest(), sendOperateRequest(), sendSubscriptionRequest(), sendDeleteRequest().
- **Controllers**: `TR069Controller` manages the TR-069 protocol, `UspController` handles TR-369 USP protocol with /usp endpoint, `Api` controllers handle RESTful interactions for devices, provisioning, and firmware, and `AcsController` manages the web interface.
- **Scalability**: Database optimizations include composite indexes and soft deletes. The queue system is designed for high throughput and reliability.
- **Configuration**: Uses standard Laravel environment variables for database, API key, MQTT broker (MQTT_HOST, MQTT_PORT, MQTT_CLIENT_ID), and USP settings (USP_CONTROLLER_ENDPOINT_ID). The server listens on `0.0.0.0:5000`. MQTT subscriber daemon available via `php artisan usp:mqtt-subscribe`.

### Feature Specifications
- **Auto-registration**: Devices are automatically identified via Serial Number, OUI, and Product Class.
- **Zero-touch Provisioning**: Configuration profiles enable automated device setup.
- **Firmware Management**: Supports uploading, versioning, and deploying firmware to selected devices.
- **TR-181 Data Model**: Parameters are stored with type and path, tracking writable/readonly status and last updates.
- **Connection Request**: The system can initiate connection requests to devices using HTTP Digest/Basic Auth with retry mechanisms.
- **Remote Diagnostics (TR-143)**: Full implementation of TR-143 diagnostic tests including IPPing (ping test with packet loss/latency metrics), TraceRoute (network path analysis with hop-by-hop timing), DownloadDiagnostics (download speed measurement), and UploadDiagnostics (upload speed measurement). API endpoints use atomic transactions (DB::transaction) to ensure data consistency between DiagnosticTest and ProvisioningTask creation. The `DiagnosticTest` model includes scopes for filtering by type and status, plus a `getResultsSummary()` method for formatted results. All tests are executed asynchronously via queue jobs with results stored in structured JSON format.

## External Dependencies
- **PostgreSQL**: Primary database for data storage.
- **Redis**: Used as the queue driver for Laravel Horizon.
- **Laravel Horizon**: Manages and monitors Redis queues.
- **Guzzle**: Utilized for making HTTP requests, specifically for TR-069 Connection Requests.
- **Google Protocol Buffers**: v4.32.1 for TR-369 USP message encoding/decoding with generated PHP classes.
- **PHP-MQTT Client**: v1.6.1 Laravel-native MQTT client for USP broker-based transport.
- **Soft UI Dashboard**: Laravel integration for the administrative web interface template.
- **Chart.js**: JavaScript charting library for interactive dashboard visualizations (doughnut, bar, polar area, line charts).
- **FontAwesome**: Icon library for dashboard cards and UI elements.