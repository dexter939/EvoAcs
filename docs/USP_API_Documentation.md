# USP TR-369 RESTful API Documentation

## Overview
The ACS system provides RESTful API endpoints for managing TR-369 USP (User Services Platform) devices. These endpoints enable remote configuration, monitoring, and control of modern CPE devices using the TR-369 protocol.

## Authentication
All API endpoints require authentication using an API key.

### Methods
- **Header**: `X-API-Key: your-api-key-here`
- **Query Parameter**: `?api_key=your-api-key-here`

### Default API Key
```
acs-secret-key-change-in-production
```
⚠️ **Important**: Change this key in production environments via the `ACS_API_KEY` environment variable.

## Base URL
```
http://your-domain.com/api/v1
```

## Endpoints

### 1. Get Parameters
Retrieves parameter values from a USP device.

**Endpoint**: `POST /usp/devices/{device}/get-params`

**Request Body**:
```json
{
  "paths": [
    "Device.DeviceInfo.ModelName",
    "Device.DeviceInfo.SoftwareVersion"
  ]
}
```

**Response** (MQTT Transport):
```json
{
  "message": "USP Get request sent via MQTT",
  "msg_id": "api-get-abc123",
  "device": "USP-9ebd4723c3",
  "paths": ["Device.DeviceInfo.ModelName"],
  "mtp": "mqtt"
}
```

**Response** (HTTP Transport):
```json
{
  "message": "USP Get request queued (HTTP MTP requires device to poll)",
  "msg_id": "api-get-abc123",
  "device": "USP-9ebd4723c3",
  "paths": ["Device.DeviceInfo.ModelName"],
  "mtp": "http",
  "note": "Device must poll /usp endpoint to receive request"
}
```

---

### 2. Set Parameters
Modifies parameter values on a USP device.

**Endpoint**: `POST /usp/devices/{device}/set-params`

**Request Body**:
```json
{
  "parameters": {
    "Device.ManagementServer.PeriodicInformInterval": "300",
    "Device.ManagementServer.ConnectionRequestUsername": "admin"
  },
  "allow_partial": true
}
```

**Parameters**:
- `parameters` (required, object): Key-value pairs of parameter paths and values
- `allow_partial` (optional, boolean): Allow partial success if some parameters fail (default: true)

**Response**:
```json
{
  "message": "USP Set request sent via MQTT",
  "msg_id": "api-set-xyz789",
  "device": "USP-9ebd4723c3",
  "parameters": {...},
  "allow_partial": true,
  "mtp": "mqtt"
}
```

---

### 3. Operate Command
Executes an operation/command on a USP device.

**Endpoint**: `POST /usp/devices/{device}/operate`

**Request Body**:
```json
{
  "command": "Device.SelfTest()",
  "params": {
    "TestType": "Full"
  }
}
```

**Common Commands**:
- `Device.Reboot()` - Reboot device
- `Device.FactoryReset()` - Factory reset
- `Device.SelfTest()` - Self-test diagnostics
- `Device.WiFi.Radio.1.Stats.Reset()` - Reset WiFi statistics

**Response**:
```json
{
  "message": "USP Operate request sent via MQTT",
  "msg_id": "api-operate-def456",
  "device": "USP-9ebd4723c3",
  "command": "Device.SelfTest()",
  "params": {...},
  "mtp": "mqtt"
}
```

---

### 4. Add Object Instance
Creates a new object instance on a USP device.

**Endpoint**: `POST /usp/devices/{device}/add-object`

**Request Body**:
```json
{
  "object_path": "Device.WiFi.AccessPoint.",
  "params": {
    "Enable": "true",
    "SSIDReference": "Device.WiFi.SSID.1",
    "SSIDAdvertisementEnabled": "true"
  }
}
```

**Response**:
```json
{
  "message": "USP Add request sent via MQTT",
  "msg_id": "api-add-ghi789",
  "device": "USP-9ebd4723c3",
  "object_path": "Device.WiFi.AccessPoint.",
  "params": {...},
  "mtp": "mqtt"
}
```

---

### 5. Delete Object Instance
Removes object instances from a USP device.

**Endpoint**: `POST /usp/devices/{device}/delete-object`

**Request Body**:
```json
{
  "object_paths": [
    "Device.WiFi.AccessPoint.5.",
    "Device.WiFi.AccessPoint.6."
  ]
}
```

**Response**:
```json
{
  "message": "USP Delete request sent via MQTT",
  "msg_id": "api-delete-jkl012",
  "device": "USP-9ebd4723c3",
  "object_paths": ["Device.WiFi.AccessPoint.5."],
  "mtp": "mqtt"
}
```

---

### 6. Reboot Device
Reboots a USP device using the standard Device.Reboot() command.

**Endpoint**: `POST /usp/devices/{device}/reboot`

**Request Body**: None required

**Response**:
```json
{
  "message": "USP Reboot command sent via MQTT",
  "msg_id": "api-reboot-mno345",
  "device": "USP-9ebd4723c3",
  "mtp": "mqtt"
}
```

---

## Error Responses

### 400 Bad Request - Invalid Device Protocol
```json
{
  "error": "Device is not a TR-369 USP device",
  "device_protocol": "tr069"
}
```

### 401 Unauthorized - Invalid API Key
```json
{
  "error": "Unauthorized",
  "message": "Invalid or missing API key"
}
```

### 404 Not Found - Device Not Found
```json
{
  "message": "No query results for model [App\\Models\\CpeDevice] {id}"
}
```

### 422 Unprocessable Entity - Validation Error
```json
{
  "message": "The paths field is required.",
  "errors": {
    "paths": ["The paths field is required."]
  }
}
```

### 500 Internal Server Error
```json
{
  "error": "Failed to send USP Get request",
  "message": "MQTT connection failed"
}
```

---

## MTP (Message Transfer Protocol) Types

The API supports two transport mechanisms:

### MQTT Transport
- Devices connected via MQTT broker
- Messages sent immediately via pub/sub
- Real-time bidirectional communication
- Topic structure: `usp/agent/{endpoint-id}/request`

### HTTP Transport
- Devices using HTTP polling
- Messages queued until device polls
- Device must periodically GET from `/usp` endpoint
- Suitable for NAT/firewall environments

---

## Usage Examples

### cURL Examples

**Get Parameters**:
```bash
curl -X POST "http://localhost:5000/api/v1/usp/devices/1/get-params" \
  -H "X-API-Key: acs-secret-key-change-in-production" \
  -H "Content-Type: application/json" \
  -d '{"paths": ["Device.DeviceInfo.ModelName"]}'
```

**Set Parameters**:
```bash
curl -X POST "http://localhost:5000/api/v1/usp/devices/1/set-params" \
  -H "X-API-Key: acs-secret-key-change-in-production" \
  -H "Content-Type: application/json" \
  -d '{
    "parameters": {
      "Device.ManagementServer.PeriodicInformInterval": "600"
    },
    "allow_partial": true
  }'
```

**Reboot Device**:
```bash
curl -X POST "http://localhost:5000/api/v1/usp/devices/1/reboot" \
  -H "X-API-Key: acs-secret-key-change-in-production"
```

---

## Import to Postman

Import the Postman collection from `docs/USP_API_Collection.postman.json` to get started quickly with pre-configured requests.

**Steps**:
1. Open Postman
2. Click "Import" → "Upload Files"
3. Select `USP_API_Collection.postman.json`
4. Configure environment variables:
   - `BASE_URL`: Your API base URL
   - `API_KEY`: Your API key
   - `DEVICE_ID`: Test device ID

---

## Rate Limiting

Currently no rate limiting is enforced. This will be added in future versions for production deployments.

---

## Versioning

Current API version: **v1**

The API follows semantic versioning. Breaking changes will result in a new major version (v2, v3, etc.).
