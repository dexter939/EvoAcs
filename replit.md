# Sistema ACS (Auto Configuration Server)

## Overview
The ACS (Auto Configuration Server) project is a carrier-grade system built with Laravel 11, designed to manage over 100,000 CPE (Customer Premises Equipment) devices. It supports a comprehensive suite of protocols including TR-069 (CWMP), TR-369 (USP), TR-104, TR-140, TR-143, TR-111, TR-64, TR-181, TR-196, and TR-135. Its core functionalities include device auto-registration, zero-touch provisioning, firmware management, and advanced remote diagnostics. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments. This includes AI-powered configuration and diagnostic troubleshooting capabilities.

## User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

## System Architecture

### UI/UX Decisions
The web interface uses the Soft UI Dashboard Laravel template, providing a modern, responsive design with navigation, real-time statistics, and interactive Chart.js visualizations. It includes features like filtering, pagination, and modal forms for CRUD operations on devices. The dashboard auto-refreshes every 30 seconds.

### Technical Implementations
- **Protocol Support**: Implements TR-069 (CWMP) via a dedicated SOAP endpoint, TR-369 (USP) with Protocol Buffers over MQTT/WebSocket/HTTP, and full provisioning/management for TR-104 (VoIP), TR-140 (Storage), TR-143 (Diagnostics), TR-111 (Device Modeling), TR-64 (LAN-Side), TR-181 (IoT), TR-196 (Femtocell), and TR-135 (STB/IPTV).
- **Database**: PostgreSQL with optimized indexing and multi-tenancy support.
- **Asynchronous Processing**: Laravel Horizon with Redis queues for provisioning, firmware deployment, and TR-069 requests.
- **API Security**: API Key authentication for all v1 RESTful API endpoints.
- **Scalability**: Achieved through database optimizations and a high-throughput queue system.
- **Configuration**: Uses Laravel environment variables.

### Feature Specifications
- **Device Management**: Auto-registration (via Serial Number, OUI, Product Class), zero-touch provisioning with configuration profiles, and comprehensive firmware management (upload, versioning, deployment).
- **TR-181 Data Model**: Parameters stored with type, path, access, and update history.
- **Connection Management**: System-initiated connection requests and TR-369 subscription/notification for device events.
- **AI Integration**: OpenAI is used for intelligent generation, validation, and optimization of TR-069/TR-369 configuration templates, and for AI Diagnostic Troubleshooting including issue detection, root cause analysis, and solution recommendations based on TR-143 test results.
- **Multi-Tenant Architecture**: Supports multiple customers and services with a 3-level web hierarchy.
- **Data Model Import**: Automated XML parser for importing vendor-specific TR-069 data models (e.g., TR-098, TR-104, TR-140), including validation rules.
- **Configuration Templates**: Database-driven templates for WiFi, VoIP, Storage with integrated validation rules.
- **Parameter Validation**: Comprehensive validation engine supporting data model schema, template-specific business rules, indexed paths, and strict type checking.
- **Router Manufacturers & Products Database**: Hierarchical view of 21 manufacturers and 40 router models with detailed specifications and filtering capabilities.
- **TR-143 Diagnostics**: UI and workflow for Ping, Traceroute, Download, and Upload tests, supporting NAT traversal via Periodic Inform for un-reachable devices. Results are visualized with summaries and historical patterns.
- **Network Topology Map**: Real-time visualization of connected clients (LAN/WiFi) on device detail page. TR-069 network scan via Periodic Inform collects Device.Hosts.* (LAN) and Device.WiFi.AccessPoint.*.AssociatedDevice.* (WiFi) parameters. Parses MAC/IP/hostname/signal strength and persists to network_clients table. SVG topology with router center, clients in radial circle, differentiated by connection type (solid green=LAN, dashed cyan=WiFi). Displays stats (total/LAN/WiFi counts), client table with hostname/IP/connection badge/signal quality indicator (excellent/good/fair/poor), and last_seen timestamp. Auto-refresh on page load with manual "Scan Network" button.
- **MikroTik Data Model**: Complete TR-069 Data Model imported from official MikroTik documentation (552 parameters). Includes Device.DeviceInfo (25), Device.ManagementServer (16), Device.Hosts (10), Device.WiFi (88), Device.IP (104), Device.Routing (15), Device.DNS (8), Device.DHCPv4 (37), Device.Firewall (65), Device.Cellular (107), Device.Ethernet (25), Device.PPP (29), and Device.X_MIKROTIK_* (16) vendor-specific extensions. Stored in tr069_data_models and tr069_parameters with complete metadata (type, access, description, ROS mapping, version). Artisan command `php artisan tr069:import-mikrotik` for re-import/updates.
- **Broadband Forum Universal Data Models**: Automated XML parser imports official BBF standard data models from GitHub repository (github.com/BroadbandForum/cwmp-data-models), providing universal coverage for 70-80% of all TR-069 CPE devices on the market. Imported models: TR-181 Issue 2 Amendment 19 (Device:2.19 - 8,216 parameters), TR-098 Amendment 8 (InternetGatewayDevice:1.8 - 1,993 parameters), TR-104 Issue 2 (VoiceService:2.0 - 961 parameters), TR-140 Amendment 3 (StorageService:1.3 - 144 parameters), TR-143 Corrigendum 2 (PerformanceDiagnostics - 957 parameters). Total BBF parameters: 12,271. Artisan command `php artisan tr069:import-bbf {model}` supports tr-181-2-19, tr-098, tr-104, tr-140, tr-143 with automatic download, XML parsing, and database insertion. Parser uses direct child traversal to prevent duplication.
- **Total Data Model Coverage**: 13,709 parameters across 9 data models (BBF 12,271 + vendor-specific 1,438) providing comprehensive universal support for all major CPE vendors (MikroTik, Grandstream, AVM Fritz!Box, OpenWrt, TP-Link) through BBF standard compliance plus vendor extensions.

## External Dependencies
- **PostgreSQL 16+**: Primary relational database.
- **Redis 7+**: Used as the queue driver for Laravel Horizon and for WebSocket message routing.
- **Laravel Horizon**: Manages and monitors Redis queues for background processing.
- **Guzzle**: HTTP client for making TR-069 Connection Requests.
- **Google Protocol Buffers v4.32.1**: For TR-369 USP message encoding/decoding.
- **PHP-MQTT Client v1.6.1**: For USP broker-based transport.
- **Soft UI Dashboard**: Laravel template used for the administrative web interface.
- **Chart.js**: JavaScript library for rendering interactive charts in the UI.
- **FontAwesome**: Icon library for the web interface.
- **Nginx**: Production web server and reverse proxy.
- **Supervisor/Systemd**: Process management for production environments.
- **OpenAI**: Integrated for AI-powered configuration management and diagnostic analysis.