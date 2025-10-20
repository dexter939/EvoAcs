# Real-Time Alarms & Monitoring System

Sistema carrier-grade di monitoraggio allarmi in tempo reale con notifiche SSE push, dashboard interattiva, e RBAC granulare.

---

## 📋 Indice

- [Panoramica](#panoramica)
- [Caratteristiche](#caratteristiche)
- [Architettura](#architettura)
- [Dashboard UI](#dashboard-ui)
- [SSE Real-Time Stream](#sse-real-time-stream)
- [RBAC & Permissions](#rbac--permissions)
- [API Reference](#api-reference)
- [Database Schema](#database-schema)
- [Configuration](#configuration)
- [Best Practices](#best-practices)

---

## 🎯 Panoramica

Il sistema **Alarms & Monitoring** fornisce monitoraggio carrier-grade per dispositivi CPE con:

- ✅ **Real-time notifications** via Server-Sent Events (SSE)
- ✅ **Dashboard interattiva** con filtri avanzati e statistiche
- ✅ **Bulk operations** per gestione massiva allarmi
- ✅ **RBAC enforcement** server-side granulare
- ✅ **Security audit logging** completo
- ✅ **Carrier-grade reliability** (heartbeat, auto-reconnect)

### Use Cases

1. **Network Operations Center (NOC)** - Monitoraggio 24/7 di dispositivi CPE
2. **Field Service** - Alert su dispositivi offline o malfunzionanti
3. **Performance Monitoring** - CPU/Memory/Network usage alerts
4. **Firmware Management** - Notifiche upgrade failed/success
5. **Configuration Sync** - Alert su problemi provisioning

---

## ⚡ Caratteristiche

### Severity Levels
```
CRITICAL → Richiede azione immediata (device offline, security breach)
MAJOR    → Problema significativo (high CPU, firmware failed)
MINOR    → Issue non bloccante (config sync warning)
WARNING  → Potenziale problema futuro (memory leak detected)
INFO     → Evento informativo (device rebooted successfully)
```

### Alarm Categories
- **device** - Device connectivity & health
- **performance** - CPU, Memory, Network metrics
- **configuration** - Provisioning & config sync
- **firmware** - Firmware management operations
- **security** - Security events & breaches
- **informational** - General system events

### Alarm Status Workflow
```
active → acknowledged → cleared
  ↓
(auto-escalation timer)
  ↓
escalated
```

---

## 🏗️ Architettura

### System Components

```
┌─────────────────────────────────────────────────────────┐
│                    Client Browser                       │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────────┐ │
│  │ Dashboard UI │  │ SSE Client   │  │ DataTable     │ │
│  └──────────────┘  └──────────────┘  └───────────────┘ │
└───────────────┬─────────────────────────────────────────┘
                │ HTTP/SSE
                ▼
┌─────────────────────────────────────────────────────────┐
│              Laravel Application Layer                   │
│  ┌──────────────────┐  ┌──────────────────────────────┐ │
│  │ AlarmsController │  │ CheckPermission Middleware   │ │
│  └──────────────────┘  └──────────────────────────────┘ │
│  ┌──────────────────┐  ┌──────────────────────────────┐ │
│  │ AlarmService     │  │ SecurityLog                  │ │
│  └──────────────────┘  └──────────────────────────────┘ │
└───────────────┬─────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────┐
│              PostgreSQL Database                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────────┐  │
│  │ alarms   │  │ users    │  │ security_logs        │  │
│  └──────────┘  └──────────┘  └──────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### Event Flow

```
1. CPE Device → Event Trigger (offline, high CPU, etc.)
2. Event Handler → Create Alarm (AlarmService)
3. Database → Insert alarm record
4. SSE Stream → Broadcast to connected clients
5. Client Browser → Receive notification
6. Dashboard UI → Auto-update (no refresh needed)
7. User Action → Acknowledge/Clear alarm
8. Security Log → Audit trail logged
```

---

## 🖥️ Dashboard UI

### Statistics Cards

Dashboard mostra 6 statistics cards real-time:

```
┌────────────┬────────────┬────────────┬────────────┬────────────┬────────────┐
│   Total    │  Critical  │   Major    │   Minor    │  Warning   │    Info    │
│   Alarms   │   Alarms   │   Alarms   │   Alarms   │   Alarms   │   Alarms   │
├────────────┼────────────┼────────────┼────────────┼────────────┼────────────┤
│     156    │     12     │     28     │     45     │     51     │     20     │
└────────────┴────────────┴────────────┴────────────┴────────────┴────────────┘
```

### Filtri Avanzati

**Severity Filter**:
- All Severities (default)
- Critical only
- Major only
- Minor only
- Warning only
- Info only

**Category Filter**:
- All Categories (default)
- Device
- Performance
- Configuration
- Firmware
- Security
- Informational

**Status Filter**:
- Active (default) - Mostra solo allarmi attivi
- Acknowledged - Allarmi acknowledged ma non cleared
- Cleared - Allarmi risolti
- All Status - Tutti gli stati

### DataTable Features

- ✅ **Sorting** - Click su colonne per ordinare
- ✅ **Search** - Full-text search globale
- ✅ **Pagination** - 10/25/50/100 records per page
- ✅ **Export** - CSV, Excel, PDF, Print
- ✅ **Responsive** - Mobile-friendly design
- ✅ **Real-time updates** - Auto-refresh via SSE

### Bulk Operations

Selezione multipla con checkbox per:

**Bulk Acknowledge**: Riconosce allarmi selezionati
```javascript
// Richiede permission: alarms.manage
POST /acs/alarms/bulk-acknowledge
Body: { alarm_ids: [1, 2, 3, 4, 5] }
```

**Bulk Clear**: Risolve allarmi selezionati
```javascript
// Richiede permission: alarms.manage
POST /acs/alarms/bulk-clear
Body: { alarm_ids: [1, 2, 3, 4, 5] }
```

Conferma richiesta prima dell'esecuzione per prevenire azioni accidentali.

---

## 🔄 SSE Real-Time Stream

### Carrier-Grade Features

**Heartbeat Mechanism**:
```javascript
// Server invia ping ogni 30 secondi
event: heartbeat
data: {"timestamp": "2025-10-20T05:46:24Z"}
```

**Auto-Reconnect**:
```javascript
// Client reconnette automaticamente con exponential backoff
Attempt 1: 1 secondo
Attempt 2: 2 secondi
Attempt 3: 4 secondi
Attempt 4: 8 secondi
...max 30 secondi
```

**Multi-Client Support**:
- Supporta migliaia di connessioni SSE simultanee
- Load balancing-ready
- No memory leaks
- Graceful degradation

### Event Types

#### 1. New Alarm
```javascript
event: new
data: {
  "alarm": {
    "id": 123,
    "device_id": 456,
    "alarm_type": "device_offline",
    "severity": "critical",
    "status": "active",
    "category": "device",
    "title": "Device Offline",
    "description": "Connection timeout after 3 retries",
    "raised_at": "2025-10-20T05:46:24Z"
  }
}
```

#### 2. Alarm Acknowledged
```javascript
event: acknowledged
data: {
  "alarm_id": 123,
  "acknowledged_by": "admin@acs.local",
  "acknowledged_at": "2025-10-20T05:47:12Z"
}
```

#### 3. Alarm Cleared
```javascript
event: cleared
data: {
  "alarm_id": 123,
  "cleared_by": "operator@acs.local",
  "cleared_at": "2025-10-20T05:48:05Z",
  "resolution": "Device reconnected after router reboot"
}
```

### Client Implementation

```javascript
// Connection
const eventSource = new EventSource('/acs/alarms/stream');

// Event Listeners
eventSource.addEventListener('new', (e) => {
    const alarm = JSON.parse(e.data).alarm;
    // Add row to DataTable
    // Update statistics cards
    // Show browser notification (optional)
});

eventSource.addEventListener('acknowledged', (e) => {
    const data = JSON.parse(e.data);
    // Update row badge to "Acknowledged"
    // Update statistics
});

eventSource.addEventListener('cleared', (e) => {
    const data = JSON.parse(e.data);
    // Remove row from DataTable (if filter = active)
    // Update statistics
});

eventSource.addEventListener('heartbeat', (e) => {
    console.log('SSE Heartbeat ping');
    // Update connection status indicator
});

// Error Handling
eventSource.onerror = () => {
    console.error('SSE Connection lost, reconnecting...');
    // Auto-reconnect handled by browser EventSource
};
```

---

## 🔒 RBAC & Permissions

### Permission Model

**alarms.view** (Lettura):
- Dashboard access
- Statistics visualization
- SSE stream connection
- Alarm details view

**alarms.manage** (Gestione):
- Acknowledge alarms
- Clear alarms
- Bulk operations
- Resolution notes

### Route Protection

```php
// routes/web.php

// READ Operations (alarms.view)
Route::middleware(['permission:alarms.view'])->group(function () {
    Route::get('/alarms', [AlarmsController::class, 'index']);
    Route::get('/alarms/stats', [AlarmsController::class, 'getStats']);
    Route::get('/alarms/stream', [AlarmsController::class, 'stream']);
});

// WRITE Operations (alarms.manage)
Route::middleware(['permission:alarms.manage'])->group(function () {
    Route::post('/alarms/{id}/acknowledge', [AlarmsController::class, 'acknowledge']);
    Route::post('/alarms/{id}/clear', [AlarmsController::class, 'clear']);
    Route::post('/alarms/bulk-acknowledge', [AlarmsController::class, 'bulkAcknowledge']);
    Route::post('/alarms/bulk-clear', [AlarmsController::class, 'bulkClear']);
});
```

### Role Mapping

| Ruolo | alarms.view | alarms.manage | Use Case |
|-------|-------------|---------------|----------|
| **Administrator** | ✅ | ✅ | Full access operations |
| **Manager** | ✅ | ✅ | Team lead / Supervisor |
| **Operator** | ✅ | ✅ | NOC operator |
| **Viewer** | ✅ | ❌ | Read-only monitoring |
| **Support** | ❌ | ❌ | No access |

### Security Logging

Tutte le operazioni sono tracciate in `security_logs`:

```sql
SELECT action, user_id, metadata, severity, created_at
FROM security_logs
WHERE action LIKE 'alarm_%'
ORDER BY created_at DESC;

-- Example entries:
-- alarm_acknowledged (user_id: 1, severity: info)
-- alarm_cleared (user_id: 2, severity: info)
-- alarm_bulk_acknowledged (user_id: 1, severity: info)
-- unauthorized_access (user_id: 5, severity: critical)
```

---

## 📡 API Reference

### GET /acs/alarms
Recupera lista allarmi con paginazione e filtri.

**Permission**: `alarms.view`

**Query Parameters**:
```
?severity=critical       # Filter by severity
&category=device         # Filter by category
&status=active          # Filter by status
&page=1                 # Pagination
&per_page=25            # Records per page
```

**Response**:
```json
{
  "data": [
    {
      "id": 123,
      "device_id": 456,
      "device": {
        "serial_number": "HCQ087W9HRS",
        "manufacturer": "MikroTik"
      },
      "alarm_type": "device_offline",
      "severity": "critical",
      "status": "active",
      "category": "device",
      "title": "Device Offline",
      "description": "Connection timeout",
      "raised_at": "2025-10-20T05:46:24Z",
      "acknowledged_at": null,
      "acknowledged_by": null,
      "cleared_at": null
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 156,
    "per_page": 25,
    "last_page": 7
  }
}
```

### GET /acs/alarms/stats
Recupera statistiche allarmi real-time.

**Permission**: `alarms.view`

**Response**:
```json
{
  "total": 156,
  "by_severity": {
    "critical": 12,
    "major": 28,
    "minor": 45,
    "warning": 51,
    "info": 20
  },
  "by_status": {
    "active": 120,
    "acknowledged": 24,
    "cleared": 12
  },
  "by_category": {
    "device": 45,
    "performance": 38,
    "configuration": 32,
    "firmware": 18,
    "security": 8,
    "informational": 15
  }
}
```

### POST /acs/alarms/{id}/acknowledge
Riconosce singolo allarme.

**Permission**: `alarms.manage`

**Response**:
```json
{
  "success": true,
  "message": "Alarm acknowledged successfully",
  "alarm": {
    "id": 123,
    "status": "acknowledged",
    "acknowledged_at": "2025-10-20T05:47:12Z",
    "acknowledged_by": 1
  }
}
```

### POST /acs/alarms/{id}/clear
Risolve e chiude allarme.

**Permission**: `alarms.manage`

**Body**:
```json
{
  "resolution": "Device reconnected after router reboot"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Alarm cleared successfully",
  "alarm": {
    "id": 123,
    "status": "cleared",
    "cleared_at": "2025-10-20T05:48:05Z",
    "resolution": "Device reconnected after router reboot"
  }
}
```

### POST /acs/alarms/bulk-acknowledge
Riconosce multipli allarmi.

**Permission**: `alarms.manage`

**Body**:
```json
{
  "alarm_ids": [1, 2, 3, 4, 5]
}
```

**Response**:
```json
{
  "success": true,
  "message": "5 alarms acknowledged successfully",
  "count": 5
}
```

---

## 🗄️ Database Schema

### Table: `alarms`

```sql
CREATE TABLE alarms (
    id BIGSERIAL PRIMARY KEY,
    device_id BIGINT REFERENCES cpe_devices(id) ON DELETE CASCADE,
    alarm_type VARCHAR(255) NOT NULL,
    severity VARCHAR(50) CHECK (severity IN ('critical', 'major', 'minor', 'warning', 'info')),
    status VARCHAR(50) CHECK (status IN ('active', 'acknowledged', 'cleared')),
    category VARCHAR(100),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    metadata JSON,
    raised_at TIMESTAMP NOT NULL,
    acknowledged_at TIMESTAMP,
    acknowledged_by BIGINT REFERENCES users(id),
    cleared_at TIMESTAMP,
    resolution TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Indexes for performance
CREATE INDEX idx_alarms_device_id ON alarms(device_id);
CREATE INDEX idx_alarms_severity ON alarms(severity);
CREATE INDEX idx_alarms_status ON alarms(status);
CREATE INDEX idx_alarms_raised_at ON alarms(raised_at DESC);
CREATE INDEX idx_alarms_category ON alarms(category);
```

### Relationships

- `alarms.device_id` → `cpe_devices.id` (Many-to-One)
- `alarms.acknowledged_by` → `users.id` (Many-to-One)

---

## ⚙️ Configuration

### Environment Variables

```bash
# .env

# Alarms Configuration
ALARMS_AUTO_ACKNOWLEDGE=false         # Auto-acknowledge low severity
ALARMS_RETENTION_DAYS=90              # Delete cleared alarms after N days
ALARMS_ESCALATION_TIMEOUT=3600        # Auto-escalate after N seconds
ALARMS_EMAIL_NOTIFICATIONS=true       # Send email on critical alarms
ALARMS_BROWSER_NOTIFICATIONS=true     # Enable browser push notifications

# SSE Configuration
SSE_HEARTBEAT_INTERVAL=30             # Heartbeat ping interval (seconds)
SSE_CONNECTION_TIMEOUT=300            # Max idle time before disconnect
SSE_MAX_RETRIES=10                    # Max reconnection attempts
```

### Alarm Rules

File: `config/alarms.php`

```php
return [
    'severity_levels' => [
        'critical' => 1,
        'major' => 2,
        'minor' => 3,
        'warning' => 4,
        'info' => 5,
    ],
    
    'auto_escalation' => [
        'critical' => 1800,  // 30 min
        'major' => 3600,     // 1 hour
        'minor' => 7200,     // 2 hours
    ],
    
    'notification_channels' => [
        'critical' => ['email', 'sms', 'sse'],
        'major' => ['email', 'sse'],
        'minor' => ['sse'],
        'warning' => ['sse'],
        'info' => ['sse'],
    ],
];
```

---

## 📊 Best Practices

### 1. Alarm Lifecycle Management

**DO**:
- ✅ Acknowledge alarms appena visualizzati per indicare awareness
- ✅ Aggiungere resolution notes chiare quando cleared
- ✅ Investigare pattern di allarmi ricorrenti
- ✅ Usare bulk operations per gestione massiva

**DON'T**:
- ❌ Ignorare allarmi critical per periodi prolungati
- ❌ Clear allarmi senza investigare root cause
- ❌ Lasciare troppi allarmi acknowledged senza azione

### 2. Performance Optimization

**Large Datasets**:
```sql
-- Usa indexes per query veloci
EXPLAIN ANALYZE
SELECT * FROM alarms
WHERE status = 'active'
  AND severity IN ('critical', 'major')
ORDER BY raised_at DESC
LIMIT 100;
```

**Pagination**:
```javascript
// Sempre usare paginazione per large datasets
const alarms = await fetch('/acs/alarms?per_page=25&page=1');
```

### 3. SSE Connection Management

**Browser Tab Management**:
```javascript
// Close SSE connection quando tab non attiva
document.addEventListener('visibilitychange', () => {
    if (document.hidden && eventSource) {
        eventSource.close();
    } else {
        // Reconnect when tab becomes active
        connectSSE();
    }
});
```

### 4. Security

**Rate Limiting**:
```php
// Limita bulk operations per prevenire abuse
Route::post('/alarms/bulk-acknowledge')
    ->middleware('throttle:10,1'); // Max 10 requests/min
```

**Input Validation**:
```php
// Valida alarm IDs in bulk operations
$validatedData = $request->validate([
    'alarm_ids' => 'required|array|max:100',
    'alarm_ids.*' => 'required|integer|exists:alarms,id'
]);
```

---

## 🔧 Troubleshooting

### SSE Non Riceve Notifiche

**Diagnosi**:
```javascript
// Console Browser
eventSource.readyState  // 0=CONNECTING, 1=OPEN, 2=CLOSED
```

**Soluzioni**:
1. Verifica permission `alarms.view`
2. Check firewall/proxy non blocca SSE
3. Restart workflow ACS Server
4. Clear browser cache

### Alarms Non Visualizzati in Dashboard

**Diagnosi**:
```sql
SELECT COUNT(*) FROM alarms WHERE status = 'active';
```

**Soluzioni**:
1. Verifica filtri attivi (severity/category/status)
2. Check RBAC permissions utente
3. Inspect Network tab per errori HTTP

### Performance Degradation

**Diagnosi**:
```sql
-- Check numero allarmi totali
SELECT COUNT(*) FROM alarms;

-- Check slow queries
EXPLAIN ANALYZE SELECT * FROM alarms WHERE status = 'active';
```

**Soluzioni**:
1. Cleanup vecchi allarmi cleared (retention policy)
2. Verifica indexes database
3. Optimize DataTable pagination
4. Enable Redis caching

---

**Vedi Anche**:
- [RBAC Guide](../../docs/ALARMS_RBAC_GUIDE.md)
- [Testing Guide](../../docs/ALARMS_RBAC_TESTING_GUIDE.md)
- [API Reference](../api/rest-api.md)

---

**Ultima Modifica**: Ottobre 2025  
**Versione**: 1.0
