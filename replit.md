## Overview
The ACS (Auto Configuration Server) project is a carrier-grade system built with Laravel 11, designed to manage over 100,000 CPE (Customer Premises Equipment) devices. It supports a comprehensive suite of protocols including TR-069 (CWMP), TR-369 (USP), TR-104, TR-140, TR-143, TR-111, TR-64, TR-181, TR-196, and TR-135. Its core functionalities include device auto-registration, zero-touch provisioning, firmware management, and advanced remote diagnostics. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments, including AI-powered configuration and diagnostic troubleshooting capabilities.

## User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

## Project Progress

**Current Status**: ~65% (119+/181 tests passing)

### Phase 3 Completed - API Standardization ✅ (October 2025)
- **TR-143 Diagnostics** (10/10 tests): Complete ✅ - Ping, Traceroute, Download/Upload tests, NAT traversal, result validation
- **TR-369 USP Operations** (11/13 tests): getParameters ✅, device validation ✅, deleteObject ✅, operate ✅, reboot ✅, createSubscription ✅, listSubscriptions ✅, deleteSubscription ✅, online validation ✅, notification_type validation ✅
  - Remaining: setParameters, mtp_transports (both require MQTT/WebSocket service mocking)
  - Database: notification_type enum constraint ✅, 'http' mtp_type support ✅, API response standardization {success, data} ✅
- **Storage Service API** (6/6 tests): Production-ready ✅ - All endpoints use {success, data} format, fillable fields (service_name, storage_type, server_name, share_path), accessors (total_capacity_mb, used_capacity_mb, filesystem_type), auto service_instance generation
- **VoIP Service API** (7/7 tests): Production-ready ✅ - All endpoints use {success, data} format, fillable fields (service_name, service_type), accessors (line_number), auto service_instance generation, validation errors {success: false, errors}

### Phase 2 Completed - TR-069 Core Protocol ✅ (October 2025)
- **Connection Request Mechanism** (7/7 tests): RFC 2617 Digest Auth with 401 challenge/response flow, Basic Auth, offline device validation, network error handling
- **Inform Flow & Session Management** (7/7 tests): Device auto-registration, SOAP validation, session cookies, multi-session handling
- **Parameter Operations** (7/7 tests): SetParameterValues/GetParameterValues responses, TransferComplete with FaultCode=0 bugfix, result_data field mapping, fallback task correlation

### Phase 1 Completed - Quick Wins ✅ (October 2025)
- **Firmware Management** (11/11 tests): Fixed validation order - model compatibility before online status check
- **Femtocell TR-196** (5/5 tests): RF parameters, GPS sync, auto-configuration
- **IoT Devices TR-181** (6/6 tests): Smart home integration, protocol validation
- **STB/IPTV TR-135** (5/5 tests): Channel management, streaming, QoS

**Total Completed**: 124+/181 tests (Phases 1+2+3 partial) | **Next**: Complete Phase 3 remaining tests (VoIP firmware, USP service mocking)

## System Architecture

### UI/UX Decisions
The web interface uses the Soft UI Dashboard Laravel template, providing a modern, responsive design with navigation, real-time statistics, interactive Chart.js visualizations, filtering, pagination, and modal forms for CRUD operations. The dashboard auto-refreshes every 30 seconds.

### Technical Implementations
- **Protocol Support**: Implements TR-069 (CWMP) via a dedicated SOAP endpoint, TR-369 (USP) with Protocol Buffers over MQTT/WebSocket/HTTP/XMPP, and full provisioning/management for various TR protocols including TR-104, TR-140, TR-143, TR-111, TR-64, TR-181, TR-196, and TR-135.
- **XMPP Transport for TR-369 USP**: Proof-of-concept integration with Prosody XMPP server for real-time TR-369 USP message exchange, utilizing `pdahal/php-xmpp` library. USP Protocol Buffers messages are base64 encoded within custom XMPP stanzas.
- **Database**: PostgreSQL with optimized indexing and multi-tenancy support.
- **Asynchronous Processing**: Laravel Horizon with Redis queues for provisioning, firmware deployment, and TR-069 requests.
- **API Security**: API Key authentication for all v1 RESTful API endpoints.
- **Scalability**: Achieved through database optimizations and a high-throughput queue system.
- **Configuration**: Uses Laravel environment variables.

### Feature Specifications
- **Device Management**: Auto-registration (Serial Number, OUI, Product Class), zero-touch provisioning with configuration profiles, and comprehensive firmware management.
- **TR-181 Data Model**: Parameters stored with type, path, access, and update history.
- **Connection Management**: System-initiated connection requests and TR-369 subscription/notification for device events.
- **AI Integration**: OpenAI is used for intelligent generation, validation, optimization of TR-069/TR-369 configuration templates, and AI Diagnostic Troubleshooting (issue detection, root cause analysis, solution recommendations based on TR-143 test results).
- **Multi-Tenant Architecture**: Supports multiple customers and services with a 3-level web hierarchy.
- **Data Model Import**: Automated XML parser for importing vendor-specific (e.g., MikroTik) and Broadband Forum (BBF) standard TR-069 data models (TR-181, TR-098, TR-104, TR-140, TR-143).
- **Configuration Templates**: Database-driven templates for WiFi, VoIP, Storage with integrated validation rules.
- **Parameter Validation**: Comprehensive validation engine supporting data model schema, template-specific business rules, indexed paths, and strict type checking.
- **Router Manufacturers & Products Database**: Hierarchical view of manufacturers and router models with detailed specifications.
- **TR-143 Diagnostics**: UI and workflow for Ping, Traceroute, Download, and Upload tests, supporting NAT traversal.
- **Network Topology Map**: Real-time visualization of connected LAN/WiFi clients on device detail pages, collected via TR-069 network scans.
- **Auto-Mapping Data Model**: Intelligent automatic data model assignment during TR-069 Inform flow using multiple matching strategies (exact, partial, OUI-based, vendor-only, BBF fallback).
- **NAT Traversal & Pending Commands Queue**: Production-grade solution for executing TR-069 commands on CPE devices behind NAT/firewalls by queuing commands for the next TR-069 session if an immediate Connection Request fails.

## External Dependencies
- **PostgreSQL 16+**: Primary relational database.
- **Redis 7+**: Queue driver for Laravel Horizon and WebSocket message routing.
- **Laravel Horizon**: Manages and monitors Redis queues.
- **Guzzle**: HTTP client for TR-069 Connection Requests.
- **Google Protocol Buffers v4.32.1**: For TR-369 USP message encoding/decoding.
- **PHP-MQTT Client v1.6.1**: For USP broker-based transport.
- **Prosody XMPP Server**: Carrier-grade XMPP/Jabber server for TR-369 USP XMPP transport.
- **pdahal/php-xmpp v1.0.1**: PHP XMPP client library.
- **Soft UI Dashboard**: Laravel template for the administrative web interface.
- **Chart.js**: JavaScript library for interactive charts.
- **FontAwesome**: Icon library.
- **Nginx**: Production web server and reverse proxy.
- **Supervisor/Systemd**: Process management.
- **OpenAI**: Integrated for AI-powered configuration management and diagnostic analysis.