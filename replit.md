# Sistema ACS (Auto Configuration Server)

## Overview
The ACS (Auto Configuration Server) project is a carrier-grade system built with Laravel 11, designed to manage over 100,000 CPE (Customer Premises Equipment) devices. It supports TR-069 (CWMP) and TR-181 protocols, with future plans for TR-369 (USP). The system aims to provide robust device auto-registration, zero-touch provisioning, firmware management, and a professional web interface for comprehensive control and monitoring of CPE devices. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments.

## User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

## System Architecture

### UI/UX Decisions
The web interface utilizes the Soft UI Dashboard Laravel template, offering a modern, responsive design. Navigation is organized via a sidebar, and key statistics are displayed using real-time cards with FontAwesome icons. The dashboard features **8 statistics cards** (Devices Online, Task Pending, Firmware Deploy, Task Completati, Test Diagnostici, Profili Attivi, Versioni Firmware, Parametri TR-181) and **4 interactive Chart.js visualizations** (Devices Status Doughnut, Tasks Bar Chart, Diagnostics Polar Area, Firmware Line Chart) for real-time monitoring. Tables include pagination and status badges for clarity. Modal forms are used for CRUD operations and device actions like provisioning and rebooting. The dashboard auto-refreshes every 30 seconds. Assets use relative paths for compatibility across various environments.

### Technical Implementations
- **TR-069 (CWMP) Server**: Features a dedicated `/tr069` SOAP endpoint for device communication, handling `Inform` requests, and managing stateful TR-069 sessions with cookie-based tracking. It supports `GetParameterValues`, `SetParameterValues`, `Reboot`, and `Download` operations.
- **Database**: PostgreSQL is used for high-performance data storage, optimized with indexes for managing over 100K devices. Key tables include `cpe_devices`, `configuration_profiles`, `firmware_versions`, `device_parameters`, `provisioning_tasks`, and `firmware_deployments`.
- **Asynchronous Queue System**: Laravel Horizon is configured with Redis queues to handle asynchronous tasks such as `ProcessProvisioningTask`, `ProcessFirmwareDeployment`, and `SendTR069Request`. Tasks include retry logic and timeouts for robustness.
- **API Security**: All API v1 endpoints are protected using API Key authentication via a custom middleware, requiring an `X-API-Key` header or `api_key` query parameter.
- **RESTful API (v1)**: Provides authenticated endpoints for managing devices (CRUD), provisioning (get/set parameters, reboot), firmware (upload, deploy), and remote diagnostics (ping, traceroute, download/upload speed tests).
- **Web Interface**: A comprehensive web interface is accessible via `/acs/*`, offering a dashboard with 8 statistics cards and 4 Chart.js graphs (doughnut, bar, polar area, line), device management, provisioning tools, firmware management, and configuration profile CRUD functionalities. All dashboard statistics are calculated in the controller for performance and accuracy, including distinct TR-181 parameter counts.
- **Eloquent Models**: Core models include `CpeDevice`, `ConfigurationProfile`, `FirmwareVersion`, `DeviceParameter`, `ProvisioningTask`, `FirmwareDeployment`, and `DiagnosticTest`.
- **Services**: A `TR069Service` handles the generation of TR-069 SOAP requests.
- **Controllers**: `TR069Controller` manages the TR-069 protocol, `Api` controllers handle RESTful interactions for devices, provisioning, and firmware, and `AcsController` manages the web interface.
- **Scalability**: Database optimizations include composite indexes and soft deletes. The queue system is designed for high throughput and reliability.
- **Configuration**: Uses standard Laravel environment variables for database and API key settings. The server listens on `0.0.0.0:5000`.

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
- **Soft UI Dashboard**: Laravel integration for the administrative web interface template.
- **Chart.js**: JavaScript charting library for interactive dashboard visualizations (doughnut, bar, polar area, line charts).
- **FontAwesome**: Icon library for dashboard cards and UI elements.