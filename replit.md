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