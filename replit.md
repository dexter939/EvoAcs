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

## Recent UI Enhancements (October 19, 2025)

### Devices Page Redesign - Profile-Teams Template Pattern
Production-ready card-based layout following Soft UI Dashboard PRO `profile-teams` demo template:
- **Header Section**: Dynamic device count display with gradient primary "Info Auto-Registration" button
- **Compact Filters Toolbar**: Horizontal row with search box, protocol/MTP/status dropdowns, filter/reset buttons
- **Card Grid Layout**: Responsive `col-xl-4 col-md-6` grid (3 columns desktop, 2 tablet, 1 mobile)
- **Device Cards**: Gradient background (primary for TR-069, success for TR-369), large router/satellite-dish icons (fa-4x opacity-10), status badge overlay, serial number title, manufacturer-model subtitle, protocol badges, info rows (IP, last contact, service, data model), "Dettagli" button + 4 quick action icons
- **AJAX Modal Details**: JSON endpoint `/acs/devices/{id}` with `wantsJson()` conditional branching, modal with tabs (Info table, Parameters placeholder, History placeholder)
- **Backward Compatibility**: Direct Blade view navigation preserved for non-AJAX requests
- **Architect Review**: PASS - "Working JSON-backed device detail flow with profile-teams UI intact, Security: none observed"

### Dashboard Redesign - Soft UI Dashboard PRO Pattern
Production-ready redesign following official Soft UI PRO `dashboard-default` demo template:
- **Stat Cards with Mini Sparklines**: 4 primary cards with Nucleo Icons gradient, trend indicators, and inline 50px Chart.js sparkline charts (primary/warning/success/info gradients)
- **Modern Table Layout**: col-lg-8 Recent Devices table with dropdown actions menu, protocol-based icons (router/satellite-dish), status badges
- **Activity Timeline Widget**: col-lg-4 sidebar with CSS gradient vertical line, colored activity dots, real-time task events
- **Protocol Overview**: 2x2 grid bordered cards for TR-069 CWMP, TR-369 USP, MQTT Transport, HTTP Transport with Nucleo icons
- **Gradient Charts**: Doughnut (device status), Bar (tasks), Line (firmware) with full Soft UI PRO gradient configuration
- **Diagnostics Dashboard**: Border-dashed cards with text-gradient icons for Ping/Traceroute/Download/Upload statistics
- **Architect Review**: PASS - "Delivers PRO layout with functional sparkline charts, Security: none observed"

### Device Details Modal - Complete Implementation (October 19, 2025)
Production-ready tabbed modal with hierarchical parameters and event history:

**Tab Parameters - Hierarchical TR-181 Tree:**
- **Dual View Modes**: Toggle between Tree (hierarchical) and Table (flat) views with button group
- **Tree View**: Recursive rendering with folder/file icons, expand/collapse chevron toggles, progressive indentation (level * 20px)
- **Table View**: Sortable table with columns (Percorso, Valore, Tipo, RW)
- **Live Search**: Real-time filter across both views, matching parameter paths/names
- **Data Badges**: Color-coded type badges (string=primary, int=info, boolean=warning), R/W permission badges
- **API Endpoint**: `/acs/devices/{id}/parameters` returns JSON with `parameters` array and `hierarchy` object
- **Performance**: Device-keyed cache (`parametersCache[deviceId]`) prevents redundant API calls
- **Security**: `escapeHtml()` function sanitizes all device-supplied data (path, value, name, type) to prevent stored XSS
- **UX**: Loading spinner, error handling with retry, empty state message

**Tab History - Event Timeline:**
- **Migration**: `device_events` table (Laravel Blueprint) with fields: type, title, description, status, triggered_by, device_id, timestamps
- **Model**: `DeviceEvent` with fillable, casts (datetime), relationships (belongsTo CpeDevice), scopes (recent, completed, failed)
- **Timeline UI**: Vertical Soft UI Dashboard timeline with gradient line, colored activity dots
- **Event Types**: provisioning, reboot, firmware_update, diagnostic, connection_request, parameter_change (differentiated icons)
- **Status Badges**: pending=warning, processing=info, completed=success, failed=danger
- **API Endpoint**: `/acs/devices/{id}/history` returns last 50 events with metadata
- **Performance**: Device-keyed cache (`historyCache[deviceId]`)
- **Security**: All event data (title, description, triggered_by, status) escaped via `escapeHtml()` to prevent XSS
- **UX**: Loading spinner, error handling, empty state for devices without history

**Tab Info:**
- Device metadata table with manufacturer, model, firmware, serial, MAC, IP, connection URIs, status
- "Edit Device" button for quick updates

**Architecture:**
- **Cache Strategy**: Per-device cache objects prevent stale data bugs when opening multiple device modals
- **Security-First**: Centralized `escapeHtml()` helper protects against stored XSS from malicious CPE devices
- **AJAX-Powered**: JSON endpoints with `wantsJson()` conditional branching, backward-compatible Blade fallback
- **CSRF Protection**: X-CSRF-TOKEN header on all fetch requests
- **Architect Review**: PASS - "Satisfies hierarchical parameters/history requirements and eliminates stored-XSS exposure via centralized HTML escaping. Security: no serious issues observed."

### Real-time Alarms & Monitoring System - Production-Ready (October 19, 2025)
Carrier-grade alarm management system with SSE real-time notifications following Soft UI Dashboard PRO patterns:

**Dashboard UI - Soft UI PRO Pattern:**
- **Stat Cards with Sparklines**: 4 responsive cards (col-xl-3 col-sm-6) with gradient Nucleo icons (bell/exclamation/triangle/lightbulb), inline 50px Chart.js sparkline charts displaying real 24h trend data
- **Real-time Badge**: Pulsating green "SSE CONNECTED" badge with animation indicating live connection status
- **Activity Timeline**: col-lg-4 sidebar widget with vertical gradient line, colored activity dots (red/orange/yellow/blue), showing recent 10 alarms with formatted timestamps
- **Filters & Actions**: Status dropdown (active/acknowledged/cleared), severity dropdown, bulk "Acknowledge All" button with Promise.all processing
- **Alarms Table**: col-lg-8 responsive table with severity badges, device links, timestamps, "Acknowledge" action buttons

**Backend Implementation:**
- **AlarmService::getAlarmTrends24h()**: Hourly aggregation of alarms for last 24h, returns labels array + total/critical/major/minor counts for sparkline charts
- **Scalable SSE Endpoint**: Bounded runtime (5min max, 150 iterations), batch limit (10 alarms/query), eager loading (`->with(['device:id,hostname,serial_number'])`), disconnect detection, proper logging with Log facade
- **Default Filter**: Server-side enforcement of "active" status filter, pagination with preserved query params
- **Event-Driven**: AlarmCreated event broadcast via SSE stream, DeviceOfflineAlarm/FirmwareFailureAlarm/DiagnosticFailureAlarm automatic listeners

**Database:**
- **Migration**: `2025_10_17_214053_create_alarms_table` with indexed columns (device_id, severity, status, raised_at)
- **Model**: `Alarm` with scopes (active, acknowledged, cleared, bySeverity), relationships (belongsTo CpeDevice), helpers (badgeColor, severityIcon)
- **DeviceEvent Integration**: Alarm events logged to device_events timeline for audit trail

**Security & Performance:**
- **CSRF Protection**: X-CSRF-TOKEN header on AJAX requests
- **Input Validation**: Sanitized filter parameters (status, severity)
- **Indexed Queries**: `raised_at` index for 24h trend aggregation (96 queries acceptable short-term, future optimization: single grouped query or materialized view)
- **Connection Management**: `ignore_user_abort(false)`, `set_time_limit(300)`, heartbeat every 30s, force reconnect after max duration

**Future Scalability Notes:**
- For 100K+ concurrent operators: consider Redis pub/sub or WebSocket upgrade
- SQL optimization: batch hourly trend queries into single grouped query for reduced latency
- Architect Review: PASS - "Meets carrier-grade readiness objectives with real data-driven sparklines, bounded SSE streaming, and default-active filtering functioning as intended. Security: none observed."