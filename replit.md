# Overview
The ACS (Auto Configuration Server) project is a carrier-grade Laravel system designed to manage over 100,000 CPE devices. It supports a comprehensive suite of TR protocols, including TR-069 and TR-369. Its core functionalities encompass device auto-registration, zero-touch provisioning, firmware management, and advanced remote diagnostics. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments, including AI-powered configuration and diagnostic troubleshooting capabilities.

# User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

# System Architecture

## UI/UX Decisions
The web interface uses the Soft UI Dashboard Laravel template, providing a modern, responsive design. Key features include a redesigned dashboard, enhanced CPE device configuration editors, a real-time alarms system, card-based device listings, a tabbed device details modal, an AI-Powered Configuration Assistant Dashboard, a Network Topology Map, an Advanced Provisioning Dashboard, a Performance Monitoring Dashboard, and an Advanced Monitoring & Alerting System.

## Technical Implementations
- **Protocol Support**: Comprehensive implementation of TR-069 (CWMP) via SOAP and TR-369 (USP) using Protocol Buffers over MQTT/WebSocket/HTTP/XMPP, alongside other TR protocols.
- **Database**: PostgreSQL with optimized indexing and multi-tenancy.
- **Performance Optimizations**: Strategic database indexes, multi-tier Redis caching, and a centralized CacheService for high-traffic operations and frequent queries.
- **Asynchronous Processing**: Laravel Horizon with Redis queues for provisioning, firmware, and TR-069 requests.
- **API Security**: API Key authentication for v1 RESTful endpoints.
- **Security Hardening**: Enterprise-grade security with rate limiting, DDoS protection, RBAC (Role-Based Access Control), input validation/sanitization, security audit logging, and IP blacklist management.
- **Scalability**: Achieved through database optimizations, Redis caching, and a high-throughput queue system.
- **Configuration**: Laravel environment variables.

## Feature Specifications
- **Device Management**: Auto-registration, zero-touch provisioning with configuration profiles, and firmware management.
- **Advanced Provisioning**: Enterprise-grade system with bulk operations, scheduling, templates library, conditional rules, configuration versioning with rollback, pre-flight validation, and staged rollout.
- **TR-181 Data Model**: Parameters stored with type, path, access, and update history.
- **Connection Management**: System-initiated connection requests and TR-369 subscription/notification.
- **AI-Powered Configuration Assistant**: Integrates OpenAI GPT-4o-mini for template generation, configuration validation, optimization, diagnostic analysis, and historical pattern detection.
- **Multi-Tenant Architecture**: Supports multiple customers with a 3-level web hierarchy.
- **Data Model Import**: Automated XML parser for vendor-specific and BBF standard TR-069 data models.
- **Configuration Templates**: Database-driven templates with validation rules.
- **BBF-Compliant Parameter Validation**: Production-ready validation engine supporting 12+ BBF data types and version-specific constraints.
- **Router Manufacturers & Products Database**: Hierarchical view of manufacturers and models.
- **TR-143 Diagnostics**: UI and workflow for Ping, Traceroute, Download, and Upload tests.
- **Network Topology Map**: Real-time interactive visualization of connected LAN/WiFi clients using vis.js.
- **Auto-Mapping Data Model**: Intelligent automatic data model assignment.
- **NAT Traversal & Pending Commands Queue**: Solution for executing TR-069 commands on devices behind NAT/firewalls.
- **Real-time Alarms & Monitoring**: Carrier-grade alarm management with SSE real-time notifications, a dashboard, and event-driven processing.
- **Advanced Monitoring & Alerting System**: Comprehensive infrastructure with multi-channel alert notifications, a configurable alert rules engine, real-time system metrics tracking, and an alert management dashboard.

# External Dependencies
- **PostgreSQL 16+**: Primary relational database.
- **Redis 7+**: Queue driver for Laravel Horizon and WebSocket message routing.
- **Laravel Horizon**: Manages Redis queues.
- **Guzzle**: HTTP client.
- **Google Protocol Buffers v4.32.1**: For TR-369 USP message encoding/decoding.
- **PHP-MQTT Client v1.6.1**: For USP broker-based transport.
- **Prosody XMPP Server**: For TR-369 USP XMPP transport.
- **pdahal/php-xmpp v1.0.1**: PHP XMPP client library.
- **Soft UI Dashboard**: Laravel template for the admin interface.
- **Chart.js**: JavaScript library for interactive charts.
- **FontAwesome**: Icon library.
- **Nginx**: Production web server and reverse proxy.
- **Supervisor/Systemd**: Process management.
- **OpenAI**: For AI-powered configuration and diagnostics.