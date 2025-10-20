# REST API v1 Reference

Documentazione completa delle API RESTful di ACS per integrazione esterna e automazione.

---

## 🎯 Overview

L'API REST di ACS fornisce accesso programmatico a tutte le funzionalità del sistema:

- **Base URL**: `https://your-domain.com/api/v1`
- **Authentication**: API Key (header `X-API-Key`)
- **Response Format**: JSON
- **Rate Limiting**: 60 requests/minute (default)
- **Versioning**: URI versioning (`/api/v1`, `/api/v2`)

---

## 🔐 Authentication

### API Key Authentication

Ogni richiesta deve includere header `X-API-Key`:

```bash
curl -X GET https://acs.example.com/api/v1/devices \
  -H "X-API-Key: your-api-key-here" \
  -H "Accept: application/json"
```

### Generating API Key

```php
// Via Tinker
php artisan tinker
>>> ApiKey::create([
    'name' => 'External Integration',
    'key' => Str::random(64),
    'permissions' => ['devices.*', 'alarms.view'],
    'expires_at' => now()->addYear(),
]);
```

**Response**:
```json
{
  "id": 1,
  "name": "External Integration",
  "key": "abc123...",
  "permissions": ["devices.*", "alarms.view"],
  "expires_at": "2026-10-20T00:00:00Z",
  "created_at": "2025-10-20T05:46:24Z"
}
```

---

## 📡 Endpoints

### Devices

#### GET /api/v1/devices
Recupera lista dispositivi CPE.

**Parameters**:
- `page` (int) - Page number (default: 1)
- `per_page` (int) - Items per page (default: 25, max: 100)
- `manufacturer` (string) - Filter by manufacturer
- `status` (string) - online|offline|unknown
- `search` (string) - Search by serial/IP

**Example Request**:
```bash
GET /api/v1/devices?page=1&per_page=50&status=online
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 16,
      "serial_number": "HCQ087W9HRS",
      "manufacturer": "MikroTik",
      "product_class": "RB2011UiAS",
      "hardware_version": "r2",
      "software_version": "6.49.6",
      "ip_address": "192.168.1.1",
      "mac_address": "AA:BB:CC:DD:EE:FF",
      "status": "online",
      "last_inform": "2025-10-20T05:45:12Z",
      "created_at": "2025-09-15T10:30:00Z",
      "updated_at": "2025-10-20T05:45:12Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 1523,
    "per_page": 50,
    "last_page": 31
  },
  "links": {
    "first": "/api/v1/devices?page=1",
    "last": "/api/v1/devices?page=31",
    "prev": null,
    "next": "/api/v1/devices?page=2"
  }
}
```

#### GET /api/v1/devices/{id}
Recupera dettagli singolo dispositivo.

**Response** (200 OK):
```json
{
  "id": 16,
  "serial_number": "HCQ087W9HRS",
  "manufacturer": "MikroTik",
  "product_class": "RB2011UiAS",
  "hardware_version": "r2",
  "software_version": "6.49.6",
  "ip_address": "192.168.1.1",
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "status": "online",
  "connection_request_url": "http://192.168.1.1:7547",
  "configuration_profile": {
    "id": 5,
    "name": "Default WiFi Config",
    "description": "Standard WiFi configuration for residential"
  },
  "parameters_count": 1247,
  "alarms_count": 3,
  "last_inform": "2025-10-20T05:45:12Z",
  "created_at": "2025-09-15T10:30:00Z",
  "updated_at": "2025-10-20T05:45:12Z"
}
```

#### POST /api/v1/devices
Registra nuovo dispositivo (manual registration).

**Request Body**:
```json
{
  "serial_number": "NEW123456789",
  "manufacturer": "TP-Link",
  "product_class": "Archer C7",
  "ip_address": "192.168.2.100",
  "mac_address": "11:22:33:44:55:66",
  "connection_request_url": "http://192.168.2.100:7547",
  "connection_request_username": "admin",
  "connection_request_password": "password123"
}
```

**Response** (201 Created):
```json
{
  "id": 1524,
  "serial_number": "NEW123456789",
  "status": "pending_provisioning",
  "message": "Device registered successfully. Provisioning job queued.",
  "created_at": "2025-10-20T05:50:00Z"
}
```

#### PUT /api/v1/devices/{id}
Aggiorna dispositivo esistente.

**Request Body**:
```json
{
  "ip_address": "192.168.2.101",
  "status": "online"
}
```

**Response** (200 OK):
```json
{
  "id": 16,
  "message": "Device updated successfully",
  "updated_at": "2025-10-20T05:51:00Z"
}
```

#### DELETE /api/v1/devices/{id}
Elimina dispositivo dal sistema.

**Response** (204 No Content)

---

### Device Parameters

#### GET /api/v1/devices/{id}/parameters
Recupera parametri TR-181 del dispositivo.

**Parameters**:
- `path` (string) - Filter by parameter path (e.g., `Device.WiFi.`)
- `search` (string) - Search in parameter names

**Response** (200 OK):
```json
{
  "device_id": 16,
  "total_parameters": 1247,
  "last_sync": "2025-10-20T05:45:12Z",
  "data": [
    {
      "path": "Device.DeviceInfo.Manufacturer",
      "value": "MikroTik",
      "type": "string",
      "writable": false,
      "last_updated": "2025-10-20T05:45:12Z"
    },
    {
      "path": "Device.WiFi.SSID.1.SSID",
      "value": "MyNetwork_5GHz",
      "type": "string",
      "writable": true,
      "last_updated": "2025-10-19T14:20:00Z"
    }
  ]
}
```

#### PUT /api/v1/devices/{id}/parameters
Imposta parametri dispositivo (TR-069 SetParameterValues).

**Request Body**:
```json
{
  "parameters": [
    {
      "path": "Device.WiFi.SSID.1.SSID",
      "value": "NewNetwork_5GHz"
    },
    {
      "path": "Device.WiFi.SSID.1.Enable",
      "value": "true"
    }
  ],
  "commit": true
}
```

**Response** (202 Accepted):
```json
{
  "job_id": "prov-123456",
  "status": "queued",
  "message": "Provisioning job created. Parameters will be applied on next device connection.",
  "parameters_count": 2,
  "estimated_completion": "2025-10-20T06:00:00Z"
}
```

---

### Alarms

#### GET /api/v1/alarms
Recupera lista allarmi.

**Parameters**:
- `severity` - critical|major|minor|warning|info
- `status` - active|acknowledged|cleared
- `category` - device|performance|configuration|firmware|security
- `device_id` - Filter by device
- `from_date` - ISO 8601 date (e.g., 2025-10-01)
- `to_date` - ISO 8601 date

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 123,
      "device_id": 16,
      "device": {
        "serial_number": "HCQ087W9HRS",
        "manufacturer": "MikroTik"
      },
      "alarm_type": "device_offline",
      "severity": "critical",
      "status": "active",
      "category": "device",
      "title": "Device Offline",
      "description": "Connection timeout after 3 retries",
      "raised_at": "2025-10-20T05:46:24Z",
      "acknowledged_at": null,
      "cleared_at": null
    }
  ],
  "meta": {
    "total": 156,
    "current_page": 1,
    "per_page": 25
  }
}
```

#### GET /api/v1/alarms/{id}
Dettagli singolo allarme.

**Response** (200 OK):
```json
{
  "id": 123,
  "device_id": 16,
  "alarm_type": "device_offline",
  "severity": "critical",
  "status": "active",
  "category": "device",
  "title": "Device Offline",
  "description": "Connection timeout after 3 retries",
  "metadata": {
    "retry_count": 3,
    "last_ip": "192.168.1.1",
    "connection_error": "Connection refused"
  },
  "raised_at": "2025-10-20T05:46:24Z",
  "acknowledged_at": null,
  "acknowledged_by": null,
  "cleared_at": null,
  "resolution": null,
  "created_at": "2025-10-20T05:46:24Z",
  "updated_at": "2025-10-20T05:46:24Z"
}
```

#### POST /api/v1/alarms/{id}/acknowledge
Riconosce allarme.

**Response** (200 OK):
```json
{
  "id": 123,
  "status": "acknowledged",
  "acknowledged_at": "2025-10-20T06:00:00Z",
  "acknowledged_by_api_key": "External Integration"
}
```

#### POST /api/v1/alarms/{id}/clear
Risolve allarme.

**Request Body**:
```json
{
  "resolution": "Device reconnected after router reboot"
}
```

**Response** (200 OK):
```json
{
  "id": 123,
  "status": "cleared",
  "cleared_at": "2025-10-20T06:05:00Z",
  "resolution": "Device reconnected after router reboot"
}
```

---

### Provisioning

#### POST /api/v1/provisioning/apply
Applica configuration profile a dispositivo.

**Request Body**:
```json
{
  "device_id": 16,
  "profile_id": 5,
  "schedule": "2025-10-21T02:00:00Z"  // Optional
}
```

**Response** (202 Accepted):
```json
{
  "job_id": "prov-789012",
  "status": "queued",
  "device_id": 16,
  "profile_id": 5,
  "scheduled_at": "2025-10-21T02:00:00Z",
  "message": "Provisioning job scheduled successfully"
}
```

#### POST /api/v1/provisioning/bulk
Provisioning massivo multipli dispositivi.

**Request Body**:
```json
{
  "device_ids": [16, 17, 18, 19, 20],
  "profile_id": 5,
  "schedule": "2025-10-21T03:00:00Z"
}
```

**Response** (202 Accepted):
```json
{
  "job_id": "bulk-prov-345678",
  "status": "queued",
  "devices_count": 5,
  "profile_id": 5,
  "scheduled_at": "2025-10-21T03:00:00Z",
  "estimated_duration": "15 minutes"
}
```

#### GET /api/v1/provisioning/jobs/{job_id}
Status provisioning job.

**Response** (200 OK):
```json
{
  "job_id": "prov-789012",
  "status": "completed",  // queued|processing|completed|failed
  "device_id": 16,
  "profile_id": 5,
  "started_at": "2025-10-21T02:00:05Z",
  "completed_at": "2025-10-21T02:02:34Z",
  "duration_seconds": 149,
  "parameters_applied": 24,
  "parameters_failed": 0,
  "result": "success"
}
```

---

### Firmware

#### GET /api/v1/firmware
Lista firmware images disponibili.

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 12,
      "filename": "mikrotik-rb2011-v6.49.7.npk",
      "manufacturer": "MikroTik",
      "product_class": "RB2011UiAS",
      "version": "6.49.7",
      "size_bytes": 15728640,
      "checksum_md5": "abc123def456...",
      "uploaded_at": "2025-10-15T10:00:00Z"
    }
  ]
}
```

#### POST /api/v1/firmware/deploy
Avvia firmware upgrade su dispositivo.

**Request Body**:
```json
{
  "device_id": 16,
  "firmware_id": 12,
  "schedule": "2025-10-21T04:00:00Z",
  "auto_reboot": true
}
```

**Response** (202 Accepted):
```json
{
  "job_id": "fw-deploy-456789",
  "status": "queued",
  "device_id": 16,
  "firmware_id": 12,
  "scheduled_at": "2025-10-21T04:00:00Z",
  "estimated_duration": "10 minutes"
}
```

---

### Diagnostics

#### POST /api/v1/diagnostics/ping
Esegui ping diagnostic (TR-143).

**Request Body**:
```json
{
  "device_id": 16,
  "host": "8.8.8.8",
  "number_of_repetitions": 4,
  "timeout": 5000
}
```

**Response** (202 Accepted):
```json
{
  "diagnostic_id": "diag-ping-123456",
  "status": "running",
  "type": "ping",
  "device_id": 16,
  "started_at": "2025-10-20T06:10:00Z"
}
```

#### GET /api/v1/diagnostics/{diagnostic_id}
Recupera risultati diagnostic.

**Response** (200 OK):
```json
{
  "diagnostic_id": "diag-ping-123456",
  "type": "ping",
  "status": "completed",
  "device_id": 16,
  "parameters": {
    "host": "8.8.8.8",
    "number_of_repetitions": 4
  },
  "results": {
    "success_count": 4,
    "failure_count": 0,
    "minimum_response_time": 12,
    "average_response_time": 15,
    "maximum_response_time": 18,
    "packets": [
      {"sequence": 1, "response_time": 12, "status": "success"},
      {"sequence": 2, "response_time": 15, "status": "success"},
      {"sequence": 3, "response_time": 14, "status": "success"},
      {"sequence": 4, "response_time": 18, "status": "success"}
    ]
  },
  "started_at": "2025-10-20T06:10:00Z",
  "completed_at": "2025-10-20T06:10:08Z"
}
```

---

## ⚠️ Error Responses

### Error Format

```json
{
  "error": {
    "code": "resource_not_found",
    "message": "Device with ID 99999 not found",
    "status": 404,
    "timestamp": "2025-10-20T06:15:00Z"
  }
}
```

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created |
| 202 | Accepted | Async job queued |
| 204 | No Content | Successful deletion |
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthorized | Missing/invalid API key |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |
| 503 | Service Unavailable | System maintenance |

---

## 🔧 Rate Limiting

**Default Limits**:
- 60 requests/minute per API key
- 1000 requests/hour per API key

**Headers**:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1729415760
```

**Rate Limit Exceeded** (429):
```json
{
  "error": {
    "code": "rate_limit_exceeded",
    "message": "API rate limit exceeded. Try again in 45 seconds.",
    "status": 429,
    "retry_after": 45
  }
}
```

---

## 📊 Pagination

Large datasets sono sempre paginati:

**Request**:
```
GET /api/v1/devices?page=2&per_page=50
```

**Response**:
```json
{
  "data": [...],
  "meta": {
    "current_page": 2,
    "from": 51,
    "to": 100,
    "total": 1523,
    "per_page": 50,
    "last_page": 31
  },
  "links": {
    "first": "/api/v1/devices?page=1",
    "last": "/api/v1/devices?page=31",
    "prev": "/api/v1/devices?page=1",
    "next": "/api/v1/devices?page=3"
  }
}
```

---

## 🔍 Filtering & Searching

### Query Parameters

**Filtering**:
```
GET /api/v1/devices?manufacturer=MikroTik&status=online
```

**Searching**:
```
GET /api/v1/devices?search=HCQ087
```

**Sorting**:
```
GET /api/v1/devices?sort=created_at&order=desc
```

**Combining**:
```
GET /api/v1/devices?manufacturer=MikroTik&status=online&sort=last_inform&order=desc&per_page=100
```

---

## 📚 SDKs & Libraries

### PHP SDK

```php
use ACS\Client;

$client = new Client([
    'base_url' => 'https://acs.example.com/api/v1',
    'api_key' => 'your-api-key',
]);

// Get devices
$devices = $client->devices()->list([
    'status' => 'online',
    'manufacturer' => 'MikroTik',
]);

// Get single device
$device = $client->devices()->get(16);

// Update device
$client->devices()->update(16, [
    'ip_address' => '192.168.1.100',
]);

// Acknowledge alarm
$client->alarms()->acknowledge(123);
```

### Python SDK

```python
from acs_client import ACSClient

client = ACSClient(
    base_url='https://acs.example.com/api/v1',
    api_key='your-api-key'
)

# Get devices
devices = client.devices.list(status='online', manufacturer='MikroTik')

# Get single device
device = client.devices.get(16)

# Update device
client.devices.update(16, ip_address='192.168.1.100')

# Acknowledge alarm
client.alarms.acknowledge(123)
```

---

**Vedi Anche**:
- [TR-069 API](tr069.md)
- [TR-369 USP API](tr369-usp.md)
- [Authentication](authentication.md)

---

**Ultima Modifica**: Ottobre 2025  
**Versione**: 1.0
