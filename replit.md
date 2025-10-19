# Overview
The ACS (Auto Configuration Server) project is a carrier-grade system built with Laravel 11, designed to manage over 100,000 CPE (Customer Premises Equipment) devices. It supports a comprehensive suite of protocols including TR-069 (CWMP), TR-369 (USP), TR-104, TR-140, TR-143, TR-111, TR-64, TR-181, TR-196, and TR-135. Its core functionalities include device auto-registration, zero-touch provisioning, firmware management, and advanced remote diagnostics. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments, including AI-powered configuration and diagnostic troubleshooting capabilities.

# User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

# System Architecture

## UI/UX Decisions
The web interface utilizes the Soft UI Dashboard Laravel template, offering a modern, responsive design. Key UI/UX features include:
- A redesigned dashboard with stat cards, modern table layouts, activity timelines, and protocol overviews.
- Enhanced CPE device addition and configuration editors with specialized tabs for various settings (WiFi, LAN, Port Forwarding, QoS, Parental Control, Advanced JSON).
- A real-time alarms system with a dedicated dashboard, SSE push notifications, and customizable toast alerts.
- Production-ready card-based layout for device listings with dynamic device counts, compact filters, and quick action icons.
- Fully implemented tabbed device details modal featuring hierarchical parameter display, event history, and device metadata.

## Technical Implementations
- **Protocol Support**: Comprehensive implementation of TR-069 (CWMP) via SOAP and TR-369 (USP) using Protocol Buffers over MQTT/WebSocket/HTTP/XMPP, alongside other TR protocols.
- **XMPP Transport for TR-369 USP**: Proof-of-concept integration with Prosody XMPP server.
- **Database**: PostgreSQL with optimized indexing and multi-tenancy.
- **Asynchronous Processing**: Laravel Horizon with Redis queues for provisioning, firmware, and TR-069 requests.
- **API Security**: API Key authentication for v1 RESTful endpoints.
- **Scalability**: Achieved through database optimizations and a high-throughput queue system.
- **Configuration**: Laravel environment variables.

## Feature Specifications
- **Device Management**: Auto-registration, zero-touch provisioning with configuration profiles, and firmware management.
- **TR-181 Data Model**: Parameters stored with type, path, access, and update history.
- **Connection Management**: System-initiated connection requests and TR-369 subscription/notification.
- **AI Integration**: OpenAI for intelligent configuration template generation, validation, optimization, and diagnostic troubleshooting.
- **Multi-Tenant Architecture**: Supports multiple customers with a 3-level web hierarchy.
- **Data Model Import**: Automated XML parser for vendor-specific and BBF standard TR-069 data models.
- **Configuration Templates**: Database-driven templates with validation rules.
- **BBF-Compliant Parameter Validation**: Production-ready validation engine supporting 12+ BBF data types (boolean, int, unsignedInt, long, unsignedLong, string, dateTime, base64, hexBinary, IPAddress, MACAddress, list), enumeration validation with allowed values, units-aware validation with positive/negative sign support (dBm, dB, kbps, Mbps, Gbps, KB, MB, GB, ms, seconds, minutes, hours), version-specific constraints, strict type checking with pure PHP string-based numeric comparison (no BCMath dependency, 32-bit PHP compatible), indexed path support, and enhanced error reporting with suggestions.
- **Router Manufacturers & Products Database**: Hierarchical view of manufacturers and models.
- **TR-143 Diagnostics**: UI and workflow for Ping, Traceroute, Download, and Upload tests.
- **Network Topology Map**: Real-time visualization of connected clients via TR-069 scans.
- **Auto-Mapping Data Model**: Intelligent automatic data model assignment.
- **NAT Traversal & Pending Commands Queue**: Production-grade solution for executing TR-069 commands on devices behind NAT/firewalls.
- **Real-time Alarms & Monitoring**: Carrier-grade alarm management with SSE real-time notifications, a dashboard, and event-driven processing.

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