## Overview
The ACS (Auto Configuration Server) project is a carrier-grade system built with Laravel 11, designed to manage over 100,000 CPE (Customer Premises Equipment) devices. It supports a comprehensive suite of protocols including TR-069 (CWMP), TR-369 (USP), TR-104, TR-140, TR-143, TR-111, TR-64, TR-181, TR-196, and TR-135. Its core functionalities include device auto-registration, zero-touch provisioning, firmware management, and advanced remote diagnostics. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments, including AI-powered configuration and diagnostic troubleshooting capabilities.

## User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

## System Architecture

### UI/UX Decisions
The web interface uses the Soft UI Dashboard Laravel template, providing a modern, responsive design with navigation, real-time statistics, interactive Chart.js visualizations, filtering, pagination, and modal forms for CRUD operations. The dashboard auto-refreshes every 30 seconds.
- **Dashboard Redesign**: Features stat cards with sparklines, a modern table layout for devices, an activity timeline, and protocol overviews.
- **CPE Device Addition UI**: Enhanced modal with hierarchical manufacturer/model selection, auto-fill for device specifications, and a manual entry mode.
- **CPE Configuration Editor**: Interactive modal with specialized tabs for WiFi, LAN, Port Forwarding, QoS, Parental Control, and Advanced (JSON) parameter editing, supporting both TR-069 and TR-369 protocols.
- **Real-time Alarms System**: Event-driven alarm management with a dedicated dashboard, SSE real-time push notifications, and customizable toast alerts.

### Technical Implementations
- **Protocol Support**: Implements TR-069 (CWMP) via SOAP, TR-369 (USP) with Protocol Buffers over MQTT/WebSocket/HTTP/XMPP, and various other TR protocols.
- **XMPP Transport for TR-369 USP**: Proof-of-concept integration with Prosody XMPP server for real-time TR-369 USP message exchange using `pdahal/php-xmpp`.
- **Database**: PostgreSQL with optimized indexing and multi-tenancy support.
- **Asynchronous Processing**: Laravel Horizon with Redis queues for provisioning, firmware deployment, and TR-069 requests.
- **API Security**: API Key authentication for all v1 RESTful API endpoints.
- **Scalability**: Achieved through database optimizations and a high-throughput queue system.
- **Configuration**: Uses Laravel environment variables.

### Feature Specifications
- **Device Management**: Auto-registration, zero-touch provisioning with configuration profiles, and comprehensive firmware management.
- **TR-181 Data Model**: Parameters stored with type, path, access, and update history.
- **Connection Management**: System-initiated connection requests and TR-369 subscription/notification for device events.
- **AI Integration**: OpenAI for intelligent generation, validation, optimization of TR-069/TR-369 configuration templates, and AI Diagnostic Troubleshooting.
- **Multi-Tenant Architecture**: Supports multiple customers and services with a 3-level web hierarchy.
- **Data Model Import**: Automated XML parser for importing vendor-specific and Broadband Forum (BBF) standard TR-069 data models.
- **Configuration Templates**: Database-driven templates for WiFi, VoIP, Storage with integrated validation rules.
- **Parameter Validation**: Comprehensive validation engine supporting data model schema, business rules, indexed paths, and strict type checking.
- **Router Manufacturers & Products Database**: Hierarchical view of manufacturers and router models with detailed specifications.
- **TR-143 Diagnostics**: UI and workflow for Ping, Traceroute, Download, and Upload tests, supporting NAT traversal.
- **Network Topology Map**: Real-time visualization of connected LAN/WiFi clients via TR-069 network scans.
- **Auto-Mapping Data Model**: Intelligent automatic data model assignment using multiple matching strategies.
- **NAT Traversal & Pending Commands Queue**: Production-grade solution for executing TR-069 commands on CPE devices behind NAT/firewalls by queuing commands.

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