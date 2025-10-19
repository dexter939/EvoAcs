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
- **AI-Powered Configuration Assistant Dashboard**: Interactive interface with 5 AI-powered tools (Template Generation, Configuration Validation, Optimization, Diagnostic Analysis, Historical Pattern Detection) featuring modal-based workflows, real-time results display, and OpenAI GPT-4o-mini integration.
- **Network Topology Map**: Real-time interactive visualization dashboard using vis.js library with device selection dropdown, connection type filters (LAN, 2.4GHz, 5GHz, 6GHz WiFi), color-coded nodes, auto-refresh capability, manual network scan trigger, and detailed node information panel with signal strength display.
- **Advanced Provisioning Dashboard**: Enterprise-level provisioning interface with bulk operations (multi-device selection and filtering), scheduled provisioning calendar, templates library with 8 categories, conditional provisioning rules, configuration history with rollback, pre-flight validation checks, staged rollout strategy, and real-time analytics with Chart.js visualization.
- **Performance Monitoring Dashboard**: Real-time system performance dashboard with database query metrics (queries/sec, slow queries analysis), Redis cache statistics (hit rate, memory usage, connected clients), Laravel Horizon queue monitoring (jobs/min, pending/failed counts), average response time tracking, database indexes analysis with usage statistics, and auto-refresh capability (30-second intervals).

## Technical Implementations
- **Protocol Support**: Comprehensive implementation of TR-069 (CWMP) via SOAP and TR-369 (USP) using Protocol Buffers over MQTT/WebSocket/HTTP/XMPP, alongside other TR protocols.
- **XMPP Transport for TR-369 USP**: Proof-of-concept integration with Prosody XMPP server.
- **Database**: PostgreSQL with optimized indexing and multi-tenancy.
- **Performance Optimizations**: 
  - **Database Indexes**: Strategic composite indexes on high-traffic tables (cpe_devices, device_parameters, provisioning_tasks, tr069_sessions, alarms, network_clients) created with CONCURRENTLY for zero-downtime deployment
  - **Redis Caching Strategy**: Multi-tier caching with TTL optimization (device data: 5min, parameters: 10min, profiles: 30min, data models: 1hr) for 100,000+ devices
  - **CacheService**: Centralized cache management with device data caching, parameter caching, topology caching, statistics caching, bulk invalidation, cache warm-up, and hit-rate tracking
  - **Query Result Caching**: Automatic caching for frequent queries (online devices, dashboard statistics, network topology)
  - **Performance Monitoring**: Real-time metrics dashboard tracking database performance, cache efficiency, queue throughput, and index usage statistics
- **Asynchronous Processing**: Laravel Horizon with Redis queues for provisioning, firmware, and TR-069 requests.
- **API Security**: API Key authentication for v1 RESTful endpoints.
- **Scalability**: Achieved through database optimizations, Redis caching layer, and a high-throughput queue system capable of managing 100,000+ CPE devices.
- **Configuration**: Laravel environment variables.

## Feature Specifications
- **Device Management**: Auto-registration, zero-touch provisioning with configuration profiles, and firmware management.
- **Advanced Provisioning Enhancements**: Enterprise-grade provisioning system with bulk operations, scheduling, templates library, and configuration versioning:
  - **Bulk Provisioning**: Multi-device provisioning with device filtering (by status, manufacturer, model, firmware, service), configurable execution modes (immediate/scheduled/staged rollout), pre-flight validation checks, and success threshold-based rollout control
  - **Scheduled Provisioning**: Calendar-based scheduling with recurrence support (once, daily, weekly, monthly) for automated configuration deployment
  - **Templates Library**: Categorized configuration templates (WiFi, VoIP, Security, QoS, WAN, LAN, Parental Control, Diagnostics) with quick-apply functionality
  - **Conditional Provisioning Rules**: Automatic configuration application based on device characteristics (manufacturer, model, firmware version, service type)
  - **Configuration Versioning & Rollback**: Historical tracking of configuration changes with one-click rollback to previous versions
  - **Pre-flight Validation**: Device online verification, data model compatibility checking, automatic backup, and rollback-on-failure support
  - **Staged Rollout**: Percentage-based batch deployment with configurable batch size, delay between batches, and success threshold verification
  - **Provisioning Analytics**: Success rate tracking, top templates usage statistics, and timeline visualization with Chart.js integration
  - Interactive dashboard with 6 specialized tabs (Bulk, Scheduled, Templates, Conditional Rules, History & Rollback, Analytics)
- **TR-181 Data Model**: Parameters stored with type, path, access, and update history.
- **Connection Management**: System-initiated connection requests and TR-369 subscription/notification.
- **AI-Powered Configuration Assistant**: Full-featured AI dashboard with OpenAI GPT-4o-mini integration providing:
  - **Template Generation**: Automatic TR-069/TR-369 configuration creation based on device type, manufacturer, model, and required services
  - **Configuration Validation**: TR-181 compliance checking, security issue detection, and performance problem identification
  - **Configuration Optimization**: AI-powered suggestions for performance, security, or stability improvements with parameter-specific recommendations
  - **Diagnostic Analysis**: TR-143 test result analysis with root cause identification, issue detection, and troubleshooting solutions
  - **Historical Pattern Detection**: Device diagnostic history analysis to identify recurring issues, degradation patterns, and preventive recommendations
  - Interactive modal-based UI with real-time results display, confidence scoring, and actionable insights
- **Multi-Tenant Architecture**: Supports multiple customers with a 3-level web hierarchy.
- **Data Model Import**: Automated XML parser for vendor-specific and BBF standard TR-069 data models.
- **Configuration Templates**: Database-driven templates with validation rules.
- **BBF-Compliant Parameter Validation**: Production-ready validation engine supporting 12+ BBF data types (boolean, int, unsignedInt, long, unsignedLong, string, dateTime, base64, hexBinary, IPAddress, MACAddress, list), enumeration validation with allowed values, units-aware validation with positive/negative sign support (dBm, dB, kbps, Mbps, Gbps, KB, MB, GB, ms, seconds, minutes, hours), version-specific constraints, strict type checking with pure PHP string-based numeric comparison (no BCMath dependency, 32-bit PHP compatible), indexed path support, and enhanced error reporting with suggestions.
- **Router Manufacturers & Products Database**: Hierarchical view of manufacturers and models.
- **TR-143 Diagnostics**: UI and workflow for Ping, Traceroute, Download, and Upload tests.
- **Network Topology Map**: Real-time interactive visualization of connected LAN/WiFi clients using vis.js library. Features include: device selection dropdown, connection type filters (ALL/LAN/WiFi 2.4/5/6 GHz) with live counters, color-coded nodes (Gateway: purple gradient, LAN: cyan, WiFi 2.4GHz: green, WiFi 5GHz: orange, WiFi 6GHz: red), physics-based graph layout with force-directed positioning, signal strength visualization (node size and dBm display), interactive node selection with detailed information panel (IP, MAC, hostname, connection type, interface, signal quality, last seen timestamp), manual network scan trigger via TR-069 GetParameterValues, 30-second auto-refresh toggle, and smooth graph animations with zoom/pan controls.
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