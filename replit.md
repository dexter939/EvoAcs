# Sistema ACS (Auto Configuration Server)

## Overview
The ACS (Auto Configuration Server) project is a carrier-grade system built with Laravel 11, designed to manage over 100,000 CPE (Customer Premises Equipment) devices. It supports a comprehensive suite of protocols including TR-069 (CWMP), TR-369 (USP), TR-104 (VoIP), TR-140 (Storage), TR-143 (Diagnostics), TR-111 (Device Modeling), TR-64 (LAN-Side Configuration), TR-181 (IoT Extension), TR-196 (Femtocell), and TR-135 (STB/IPTV). Its core functionalities include device auto-registration, zero-touch provisioning, firmware management, and advanced remote diagnostics. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments.

## User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

## System Architecture

### UI/UX Decisions
The web interface uses the Soft UI Dashboard Laravel template, providing a modern, responsive design with navigation and real-time statistics. It includes 12 statistics cards and 4 interactive Chart.js visualizations. Device management features filtering, pagination, and modal forms for CRUD operations. The dashboard auto-refreshes every 30 seconds.

### Technical Implementations
- **TR-069 (CWMP) Server**: Dedicated `/tr069` SOAP endpoint with stateful, cookie-based sessions, using DOMDocument for XML parsing.
- **TR-369 (USP) Support**: Implements Protocol Buffers, supporting MQTT, WebSocket, and HTTP as MTPs for USP message operations and auto-registration.
- **TR-104 (VoIP) Support**: Full VoIP service provisioning.
- **TR-140 (Storage) Support**: Complete NAS/storage service management.
- **TR-143 (Diagnostics) Support**: Comprehensive remote diagnostics suite including IPPing, TraceRoute, Download/Upload Diagnostics, and UDPEcho tests.
- **TR-111 (Device Modeling) Support**: Complete device capability discovery with GetParameterNames parsing.
- **TR-64 (LAN-Side Configuration) Support**: UPnP/SSDP-based LAN device discovery and SOAP service invocation.
- **TR-181 (IoT Extension) Support**: Smart home device management supporting ZigBee, Z-Wave, WiFi, BLE, Matter, and Thread protocols.
- **TR-196 (Femtocell) Support**: Full femtocell RF management with GPS location tracking and frequency configuration.
- **TR-135 (STB/IPTV) Support**: Set-Top Box provisioning.
- **Database**: PostgreSQL with optimized indexes, supporting multi-tenancy.
- **Asynchronous Queue System**: Laravel Horizon with Redis queues for provisioning, firmware deployment, and TR-069 requests.
- **API Security**: All API v1 endpoints are protected using API Key authentication.
- **RESTful API (v1)**: Provides authenticated endpoints for device management, provisioning, firmware, and various TR protocol operations.
- **Web Interface**: Accessible via `/acs/*` for dashboard, device management, and configuration.
- **Scalability**: Achieved through database optimizations and a high-throughput queue system.
- **Configuration**: Uses Laravel environment variables for all settings.

### Feature Specifications
- **Auto-registration**: Devices automatically identified via Serial Number, OUI, and Product Class.
- **Zero-touch Provisioning**: Automated device setup using configuration profiles.
- **Firmware Management**: Uploading, versioning, and deploying firmware.
- **TR-181 Data Model**: Parameters stored with type, path, writable/readonly status, and last updates.
- **Connection Request**: System-initiated connection requests to devices.
- **TR-369 Subscription/Notification**: Full event subscription system for device notifications via API and Web UI.
- **AI-Powered Configuration**: Integration with OpenAI for intelligent generation, validation, and optimization of TR-069/TR-369 configuration templates.
- **AI Diagnostic Troubleshooting**: OpenAI-powered analysis of TR-143 diagnostic test results with automatic issue detection, severity classification (critical/warning/info), root cause analysis, and actionable solution recommendations. Includes historical pattern analysis across multiple tests to identify recurring issues and performance degradation trends.
- **Multi-Tenant Architecture**: Supports multiple customers and services with dedicated database tables and a 3-level web hierarchy (Customers, Customer Detail, Service Detail).
- **Device-to-Service Assignment**: Functionality for assigning single or multiple devices to specific services via the web interface.

### Router Manufacturers & Products Database
- **Router Manufacturers**: Database completo di 21 produttori di router domestici mappati con OUI Prefix (MAC Address), categorie (premium, mainstream, budget, mesh, telco, etc.), supporto protocolli TR-069/369, paese di origine
- **Router Products**: Catalogo di 40+ modelli di router recenti (2023-2025) con specifiche tecniche, standard WiFi (WiFi 7/6E/6), velocità massima, prezzo, caratteristiche chiave, supporto mesh/gaming
- **Search & Filters**: Ricerca avanzata per produttore, categoria, protocollo, anno, caratteristiche (gaming, mesh)
- **Web Interface**: Pagine dedicate per visualizzare produttori, modelli, e prodotti per produttore con filtri e paginazione

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
- **OpenAI**: For AI-powered configuration template generation, validation, and optimization.