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
- **TR-104 (VoIP) Support**: Full VoIP service provisioning supporting SIP/MGCP/H.323.
- **TR-140 (Storage) Support**: Complete NAS/storage service management with multi-protocol file server support.
- **TR-143 (Diagnostics) Support**: Comprehensive remote diagnostics suite including IPPing, TraceRoute, Download/Upload Diagnostics, and UDPEcho tests.
- **TR-111 (Device Modeling) Support**: Complete device capability discovery with GetParameterNames parsing.
- **TR-64 (LAN-Side Configuration) Support**: UPnP/SSDP-based LAN device discovery and SOAP service invocation.
- **TR-181 (IoT Extension) Support**: Smart home device management supporting ZigBee, Z-Wave, WiFi, BLE, Matter, and Thread protocols.
- **TR-196 (Femtocell) Support**: Full femtocell RF management with GPS location tracking and frequency configuration.
- **TR-135 (STB/IPTV) Support**: Set-Top Box provisioning with support for various frontends and streaming protocols.
- **Database**: PostgreSQL with optimized indexes, supporting multi-tenancy for customers and services.
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
- **Connection Request**: System-initiated connection requests to devices using HTTP Digest/Basic Auth.
- **TR-369 Subscription/Notification**: Full event subscription system for device notifications via API and Web UI.
- **TR-104 VoIP Provisioning**: Complete SIP/MGCP/H.323 service configuration.
- **TR-140 Storage Service**: Full NAS storage management with logical volume configuration and RAID support.
- **TR-111 Device Capabilities**: Automated device capability discovery through GetParameterNames parsing.
- **TR-64 LAN Device Management**: UPnP/SSDP-based discovery processes and SOAP service invocation.
- **TR-181 IoT Device Provisioning**: Smart home device management for various protocols.
- **TR-196 Femtocell RF Management**: GPS-based location tracking, frequency configuration, and TxPower control.
- **TR-135 STB/IPTV Services**: Set-Top Box provisioning with streaming protocol support and real-time QoS monitoring.
- **Multi-Tenant Architecture**: Supports multiple customers and services with dedicated database tables and a 3-level web hierarchy (Customers, Customer Detail, Service Detail).

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

## Recent Development History

### Multi-Tenant CRUD Operations Implementation (October 2025)
- **Customer CRUD Web Interface**: Full Create/Read/Update/Delete operations for customers via web UI with Bootstrap 5 modals
  - "Nuovo Cliente" button on `/acs/customers` page opens Add Customer modal
  - Edit/Delete icon buttons in customer table rows
  - 3 modals: Add Customer (POST), Edit Customer (PUT), Delete Customer (DELETE with confirmation)
  - Form fields: name (required), external_id (unique), contact_email (required email), timezone (select), status (enum select)
  - JavaScript handlers populate Edit/Delete modals with customer data using data attributes
- **Service CRUD Web Interface**: Full Create/Read/Update/Delete operations for services via web UI with Bootstrap 5 modals
  - "Nuovo Servizio" button on `/acs/customers/{id}` customer detail page opens Add Service modal
  - Edit/Delete icon buttons in service table rows
  - 3 modals: Add Service (POST), Edit Service (PUT), Delete Service (DELETE with confirmation)
  - Form fields: name (required), service_type (enum: FTTH/VoIP/IPTV/IoT/Femtocell/Other), contract_number (unique), sla_tier (Standard/Premium/Enterprise), status (enum select)
  - Hidden input field `customer_id` auto-populated from current customer context
- **Controller CRUD Methods** (AcsController.php):
  - `storeCustomer(Request)`: POST handler with validation, creates customer, redirects to `/acs/customers` with success message
  - `updateCustomer(Request, $customerId)`: PUT handler with validation including unique check excluding current customer ID, updates customer, redirects back
  - `destroyCustomer($customerId)`: DELETE handler, soft deletes customer (cascades to services via Eloquent), redirects with success message
  - `storeService(Request)`: POST handler with validation including customer_id FK check, auto-sets activation_at to now(), redirects to customer detail page
  - `updateService(Request, $serviceId)`: PUT handler with validation, updates service, redirects to customer detail page
  - `destroyService($serviceId)`: DELETE handler, sets service_id=NULL for all associated devices before soft delete (preserves devices), redirects to customer detail page
- **Routes** (routes/web.php): 6 new RESTful routes added to `/acs` prefix group:
  - POST `/acs/customers` → `storeCustomer` (route: `acs.customers.store`)
  - PUT `/acs/customers/{customerId}` → `updateCustomer` (route: `acs.customers.update`)
  - DELETE `/acs/customers/{customerId}` → `destroyCustomer` (route: `acs.customers.destroy`)
  - POST `/acs/services` → `storeService` (route: `acs.services.store`)
  - PUT `/acs/services/{serviceId}` → `updateService` (route: `acs.services.update`)
  - DELETE `/acs/services/{serviceId}` → `destroyService` (route: `acs.services.destroy`)
- **Validation & Security**:
  - Laravel validation rules enforce required fields, email format, unique constraints (external_id, contract_number)
  - CSRF protection automatic via `@csrf` tokens in all forms
  - Soft deletes used for both customers and services, preserving data integrity and audit trail
  - Unique constraint validation includes current record ID exclusion for updates (e.g., `unique:customers,external_id,$customerId`)
- **Data Integrity**:
  - Service deletion safely handles device associations by setting `service_id=NULL` before delete (devices not orphaned)
  - Customer deletion uses soft delete which cascades to services via Eloquent relationships
  - All forms redirect back to appropriate pages with success flash messages
- **Testing Status**: Manual testing verified all CRUD operations functional, buttons visible, modals working, server running without errors

### Device-to-Service Assignment Feature (October 13, 2025)
- **Single Device Assignment** from Devices Page (`/acs/devices`):
  - Added "Servizio" column in CPE devices table showing assigned service name (as link) and customer name
  - Displays "Non assegnato" for unassigned devices
  - Assignment button (link icon) on each device row opens assignment modal
  - Modal with cascading dropdowns: select customer, then service (dynamically loaded)
  - JavaScript dynamically loads services via API endpoint `/acs/customers/{customerId}/services-list`
  - Submit assigns device to selected service via POST `/acs/devices/{id}/assign-service`
- **Multiple Devices Assignment** from Service Detail Page (`/acs/services/{id}`):
  - "Assegna Dispositivi" button in service devices card header (green button)
  - Modal displays list of all unassigned devices (service_id=NULL) with checkboxes
  - Device list loaded via API endpoint GET `/acs/devices/unassigned-list`
  - Shows device serial number, manufacturer, model, protocol badge, and status badge
  - "Seleziona Tutti" / "Deseleziona Tutti" buttons for bulk selection
  - Submit assigns multiple selected devices to service via POST `/acs/services/{serviceId}/assign-devices`
- **Controller Methods** (AcsController.php):
  - `assignDeviceToService(Request, $deviceId)`: Assigns single device to service with validation (service not terminated)
  - `getCustomerServices($customerId)`: API endpoint returning JSON list of non-terminated services for a customer
  - `getUnassignedDevices()`: API endpoint returning JSON list of devices where service_id IS NULL
  - `assignMultipleDevices(Request, $serviceId)`: Assigns multiple devices to service with security validations
  - Updated `devices()` method to eager load `service.customer` relation (prevents N+1 queries)
- **Routes** (routes/web.php): 4 new routes added:
  - GET `/acs/customers/{customerId}/services-list` → `getCustomerServices` (API endpoint)
  - POST `/acs/devices/{id}/assign-service` → `assignDeviceToService`
  - GET `/acs/devices/unassigned-list` → `getUnassignedDevices` (API endpoint, placed before `/devices/{id}` to avoid routing conflict)
  - POST `/acs/services/{serviceId}/assign-devices` → `assignMultipleDevices`
- **Security & Validation**:
  - CSRF protection on all POST requests via Laravel tokens
  - `assignMultipleDevices` validates all device IDs are unassigned before processing
  - Returns HTTP 422 with list of already-assigned device serial numbers if validation fails
  - Double safeguard: UPDATE query also includes `whereNull('service_id')` to prevent race conditions
  - Prevents "stealing" devices from other services via crafted requests
  - Frontend displays detailed error messages showing which devices are already assigned
- **Bug Fixes**:
  - Fixed column name bug: changed `last_contact_at` to `last_contact` in devices query and view (matches actual database schema)
- **Testing Status**: 
  - Manual testing verified single device assignment working correctly
  - API endpoint `/acs/devices/unassigned-list` tested and returns correct JSON
  - "Assegna Dispositivi" button visible on service detail page
  - Security validation reviewed and approved by architect (prevents device stealing)
  - Frontend error handling tested for 422 responses