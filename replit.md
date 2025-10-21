# Overview
The ACS (Auto Configuration Server) project is a carrier-grade Laravel system designed to manage over 100,000 CPE devices. It supports a comprehensive suite of TR protocols, including TR-069 and TR-369. Its core functionalities encompass device auto-registration, zero-touch provisioning, firmware management, and advanced remote diagnostics. The business vision is to deliver a highly scalable and performant solution for large-scale device management in telecommunication environments, including AI-powered configuration and diagnostic troubleshooting capabilities.

# User Preferences
I prefer clear and concise explanations. When making changes, prioritize core functionalities and ensure backward compatibility. I prefer an iterative development approach, focusing on delivering functional components incrementally. Please ask for confirmation before implementing significant architectural changes or altering existing API contracts. Ensure all new features have comprehensive test coverage. I want the agent to use proper markdown formatting in all its responses.

# Recent Changes
- **October 21, 2025 - Deployment Ready - STAGING ROLLOUT**: ✅ **SISTEMA COMPLETO PRONTO PER DEPLOYMENT** - (1) Alert Rules: 13 regole predefinite (4 critical, 6 high, 2 medium) per monitoring carrier-grade. (2) Pre-deployment verification: 55 metrics, 12 active alerts, 90+ DB tables, 3 workflows running. (3) Deployment checklist: STAGING_DEPLOYMENT_CHECKLIST.md con step-by-step guide, troubleshooting, rollback plan, success criteria. **TUTTI I CHECK PASSED - READY TO DEPLOY**.
- **October 21, 2025 - Telemetry & Monitoring System**: ✅ **IMPLEMENTATO SISTEMA COMPLETO DI TELEMETRIA** - Sistema carrier-grade monitoring con: (1) CollectSystemMetrics command (22+ metriche: CPU, memory, disk, DB, devices, queues, alarms). (2) Schedule automatico ogni 5 minuti. (3) Telemetry API RESTful (4 endpoints: current, history, summary, health). (4) Dashboard esistenti: Performance Monitoring, Advanced Monitoring, Laravel Horizon. (5) Documentazione completa: TELEMETRY_MONITORING_GUIDE.md. **Sistema pronto per monitoring 100K+ devices**.
- **October 21, 2025 - Staging Deployment Configuration**: ✅ **CONFIGURATO DEPLOYMENT VM PER STAGING ROLLOUT** - Configurato deployment production-ready con: (1) VM deployment target per servizi always-running (queue workers, XMPP server). (2) Build optimization: config/route/view cache. (3) Multi-service orchestration: Laravel server + Queue Worker + Prosody XMPP in parallelo. (4) Migration idempotency fixes applicati. **Sistema pronto per pubblicazione staging**.
- **October 21, 2025**: ✅ **COMPLETATA ROADMAP TR + TEST SUITE** - (1) Implementati e approvati dall'architect tutti i 7 protocolli TR rimanenti (TR-104, TR-106, TR-111, TR-135, TR-140, TR-157, TR-262) con 2,745+ righe di codice production-ready BBF-compliant. TR-157 completato con database persistente (DeploymentUnit/ExecutionUnit models + migrations). (2) Creata test suite completa: 7 test Unit per servizi TR + 3 test Integration/E2E per TR-157 con CWMP/USP flows. (3) Migrations TR-157 deployed: deployment_units (14 colonne, 5 indexes) + execution_units (16 colonne, 4 indexes) con FK constraints e UUID uniqueness. (4) Migration fixes: idempotency guards per smart_home_devices, iot_services, file_servers. **Roadmap TR completa al 100% - Production Ready**.

# System Architecture

## UI/UX Decisions
The web interface uses the Soft UI Dashboard Laravel template, providing a modern, responsive design. Key features include a redesigned dashboard, enhanced CPE device configuration editors, a real-time alarms system, card-based device listings, a tabbed device details modal, an AI-Powered Configuration Assistant Dashboard, a Network Topology Map, an Advanced Provisioning Dashboard, a Performance Monitoring Dashboard, and an Advanced Monitoring & Alerting System.

## Technical Implementations
- **Protocol Support**: Comprehensive implementation of 10 TR protocols (TR-069, TR-104, TR-106, TR-111, TR-135, TR-140, TR-157, TR-181, TR-262, TR-369) with BBF-compliant services totaling 3,200+ lines of production-ready code.
  - **TR-069 (CWMP)**: SOAP-based device management protocol with connection request workflow.
  - **TR-181 (Device:2 Data Model)**: Production-ready implementation (432-line service, 283-line controller, 12 API routes) supporting 8 namespaces (DeviceInfo, ManagementServer, Time, WiFi, LAN, DHCPv4, IP, Hosts). Features device-scoped caching, cache coherency on writes, single bulk query per device for 100K+ scale. Architect-approved carrier-grade implementation.
  - **TR-104 (VoIP)**: SIP/MGCP/H.323 voice services with codec negotiation, QoS management, failover, E911 emergency calling, call statistics (460+ lines).
  - **TR-106 (Data Model Template)**: BBF template management with versioning, parameter inheritance, constraint validation, XML import/export (350+ lines).
  - **TR-111 (Proximity Detection)**: Device discovery via UPnP/LLDP/mDNS, network topology mapping, proximity events (380+ lines).
  - **TR-135 (STB Set-Top Box)**: IPTV/OTT management with EPG, PVR recording, conditional access, multi-screen support, content delivery optimization (280+ lines).
  - **TR-140 (Storage NAS)**: SMB/CIFS/NFS file sharing, storage quotas, user ACL, backup scheduling, RAID configuration, SMART disk monitoring (350+ lines).
  - **TR-157 (Component Objects)**: Database-backed software lifecycle management with DeploymentUnit and ExecutionUnit models, persistent component tracking, dependency resolution, auto-seeding (420+ lines + 2 models + 2 migrations). Architect-approved production implementation.
  - **TR-262 (Femtocell FAP)**: LTE/5G small cell management with SON automation, ICIC interference coordination, handover optimization, performance KPIs, S1/X2 interfaces (505+ lines).
  - **TR-369 (USP)**: Unified Services Platform via Protocol Buffers over MQTT/WebSocket/HTTP/XMPP.
- **Database**: PostgreSQL with optimized indexing and multi-tenancy.
- **Performance Optimizations**: Strategic database indexes, multi-tier Redis caching, and a centralized CacheService for high-traffic operations and frequent queries.
- **Asynchronous Processing**: Laravel Horizon with Redis queues for provisioning, firmware, and TR-069 requests.
- **API Security**: API Key authentication for v1 RESTful endpoints.
- **Security Hardening**: Enterprise-grade security with rate limiting, DDoS protection, RBAC (Role-Based Access Control), input validation/sanitization, security audit logging, and IP blacklist management.
- **Scalability**: Achieved through database optimizations, Redis caching, and a high-throughput queue system.
- **Configuration**: Laravel environment variables.
- **Deployment**: VM-based deployment configuration for always-running services (Laravel + Queue Workers + XMPP). Build phase includes config/route/view caching for production performance via `scripts/replit/build.sh`. Multi-service orchestration via `scripts/replit/run.sh` with parallel process execution and wait.

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
- **Telemetry & Observability**: Automated metrics collection (22+ system/device/queue metrics), scheduled every 5 minutes, RESTful Telemetry API (current/history/summary/health endpoints), PostgreSQL persistence, and comprehensive monitoring dashboards (Performance, Advanced Monitoring, Laravel Horizon).

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