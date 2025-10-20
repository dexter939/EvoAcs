# Architettura Sistema ACS

Panoramica completa dell'architettura carrier-grade del sistema Auto Configuration Server.

---

## 🎯 Design Principles

### 1. Scalabilità Orizzontale
Sistema progettato per gestire **100.000+ dispositivi** attraverso:
- Load balancing multi-server
- Database read replicas
- Redis clustering
- Queue distribution

### 2. High Availability
- 99.9% uptime target
- Automatic failover
- Health checks
- Circuit breakers

### 3. Performance
- Sub-second response time
- Async processing per operazioni pesanti
- Multi-tier caching strategy
- Database query optimization

### 4. Security-First
- RBAC granulare
- Security audit logging
- Input validation/sanitization
- Rate limiting & DDoS protection

---

## 🏗️ Architecture Diagram

### High-Level Overview

```
┌──────────────────────────────────────────────────────────────────────┐
│                         Load Balancer (Nginx)                        │
│                    SSL/TLS Termination + Routing                     │
└────────────────────────┬─────────────────────────────────────────────┘
                         │
         ┌───────────────┴───────────────┐
         │                               │
         ▼                               ▼
┌─────────────────────┐         ┌─────────────────────┐
│   ACS Server #1     │         │   ACS Server #2     │
│  (Laravel 11 App)   │         │  (Laravel 11 App)   │
└──────────┬──────────┘         └──────────┬──────────┘
           │                               │
           └───────────────┬───────────────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ PostgreSQL   │  │    Redis     │  │   Prosody    │
│   Primary    │  │   Cluster    │  │     XMPP     │
└──────┬───────┘  └──────────────┘  └──────────────┘
       │
       ├─► Read Replica #1
       └─► Read Replica #2
```

### Component Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                       │
│  ┌────────────┐  ┌────────────┐  ┌───────────────────────┐ │
│  │  Blade     │  │  Alpine.js │  │  Chart.js / vis.js    │ │
│  │ Templates  │  │  Frontend  │  │  Visualizations       │ │
│  └────────────┘  └────────────┘  └───────────────────────┘ │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────┴─────────────────────────────────────┐
│                   Application Layer                         │
│  ┌───────────────┐  ┌───────────────┐  ┌─────────────────┐ │
│  │ Controllers   │  │  Services     │  │  Middleware     │ │
│  │ (HTTP Logic)  │  │  (Business)   │  │  (RBAC/Auth)    │ │
│  └───────────────┘  └───────────────┘  └─────────────────┘ │
│  ┌───────────────┐  ┌───────────────┐  ┌─────────────────┐ │
│  │ Queue Jobs    │  │  Validators   │  │  Form Requests  │ │
│  └───────────────┘  └───────────────┘  └─────────────────┘ │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────┴─────────────────────────────────────┐
│                     Domain Layer                            │
│  ┌───────────────┐  ┌───────────────┐  ┌─────────────────┐ │
│  │   Models      │  │  Repositories │  │    Events       │ │
│  │  (Eloquent)   │  │  (Data Access)│  │  (Domain)       │ │
│  └───────────────┘  └───────────────┘  └─────────────────┘ │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────┴─────────────────────────────────────┐
│                 Infrastructure Layer                        │
│  ┌───────────────┐  ┌───────────────┐  ┌─────────────────┐ │
│  │  PostgreSQL   │  │     Redis     │  │  External APIs  │ │
│  │   Database    │  │  Cache/Queue  │  │  (OpenAI/MQTT)  │ │
│  └───────────────┘  └───────────────┘  └─────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## 📦 Component Details

### 1. Web Application (Laravel 11)

**Responsabilità**:
- HTTP request handling
- Business logic execution
- Template rendering
- API endpoints
- WebSocket/SSE management

**Tecnologie**:
- **Framework**: Laravel 11 (PHP 8.2+)
- **Template Engine**: Blade
- **Validation**: Form Requests
- **Authentication**: Laravel Auth + RBAC custom

**Struttura Directory**:
```
app/
├── Http/
│   ├── Controllers/      # HTTP controllers
│   ├── Middleware/       # Auth, RBAC, CORS
│   └── Requests/         # Form validation
├── Models/               # Eloquent models
├── Services/             # Business logic
│   ├── AlarmService.php
│   ├── CacheService.php
│   ├── TR069Service.php
│   └── USPService.php
├── Jobs/                 # Queue jobs
└── Events/               # Domain events
```

### 2. Database Layer (PostgreSQL 16+)

**Schema Design**:
- **50+ tables** per entità domain
- **Indexes strategici** per performance
- **Foreign keys** per data integrity
- **JSON columns** per metadata flessibile
- **Partitioning** su tabelle large (alarms, logs)

**Key Tables**:
```sql
cpe_devices (100k+ rows)
├─ device_parameters (millions)
├─ alarms (high churn)
├─ firmware_images
└─ configuration_profiles

users
├─ user_role (many-to-many)
└─ security_logs (audit trail)

roles
└─ role_permission (many-to-many)
```

**Performance Optimizations**:
- B-tree indexes su foreign keys
- Partial indexes su status fields
- JSONB GIN indexes per metadata
- Connection pooling (PgBouncer)

### 3. Caching Layer (Redis 7+)

**Multi-Tier Strategy**:

**L1: Application Cache** (in-memory)
```php
// Fast lookup per request
CacheService::remember($key, 60, fn() => $data);
```

**L2: Redis Cache** (shared)
```php
// Cross-server sharing
Redis::setex("device:{$id}:params", 300, $params);
```

**L3: Database** (source of truth)

**Use Cases**:
- Device parameter cache (alta lettura)
- Session storage
- Rate limiting counters
- Real-time statistics
- Queue backing

### 4. Queue System (Laravel Horizon + Redis)

**Queue Architecture**:
```
┌─────────────────────┐
│   Job Dispatcher    │
└──────────┬──────────┘
           │
     ┌─────┴─────┐
     │   Redis   │
     │   Queues  │
     └─────┬─────┘
           │
    ┌──────┴──────┐
    │   Horizon   │
    │   Workers   │
    └──────┬──────┘
           │
   ┌───────┴────────┐
   │  Job Execution │
   └────────────────┘
```

**Queue Types**:
- `default` - General jobs (low priority)
- `provisioning` - Configuration jobs (high priority)
- `firmware` - Firmware deployments (medium)
- `tr069` - TR-069 requests (realtime)
- `usp` - TR-369 USP messages (realtime)

**Worker Configuration**:
```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['tr069', 'usp'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
        ],
        'supervisor-2' => [
            'connection' => 'redis',
            'queue' => ['provisioning', 'firmware'],
            'processes' => 5,
            'tries' => 3,
        ],
    ],
],
```

### 5. Protocol Handlers

#### TR-069 (CWMP) Handler
```
CPE Device → HTTP POST (SOAP/XML)
     ↓
Nginx → Laravel Route (/tr069)
     ↓
TR069Controller → Parse SOAP Envelope
     ↓
TR069Service → Process RPC Method
     ↓
Queue Job → Execute Device Operation
     ↓
SOAP Response → CPE Device
```

#### TR-369 (USP) Handler
```
CPE Device → MQTT/STOMP/WebSocket (Protobuf)
     ↓
Prosody XMPP / MQTT Broker
     ↓
Laravel Listener → USPService
     ↓
Protobuf Decode → Process USP Message
     ↓
Queue Job → Execute Operation
     ↓
USP Response → CPE Device
```

### 6. Real-Time Communication

**Server-Sent Events (SSE)**:
- Alarms notifications
- Dashboard live updates
- System status updates

**WebSocket** (future):
- Bi-directional communication
- Real-time device console
- Interactive diagnostics

---

## 🔄 Data Flow Patterns

### 1. Device Registration (Zero-Touch)

```
1. CPE boots → TR-069 Inform
2. ACS receives Inform → Extract serial number
3. Check if device exists in DB
4. If new:
   - Create device record
   - Assign default configuration profile
   - Queue provisioning job
5. Send GetParameterValues RPC
6. Store device parameters
7. Trigger initial configuration
8. Device ready for management
```

### 2. Configuration Provisioning

```
User → Dashboard → Select device → Apply profile
     ↓
ProfileController → Validate profile
     ↓
ProvisioningJob → Queue (high priority)
     ↓
Worker executes:
  1. Build parameter values
  2. Validate against BBF schema
  3. Send SetParameterValues (TR-069) or Set (USP)
  4. Wait for response
  5. Verify configuration applied
  6. Log success/failure
     ↓
AlarmService → Create alarm if failed
     ↓
SSE Stream → Notify dashboard
```

### 3. Firmware Upgrade

```
Admin → Upload firmware file → S3/Local storage
     ↓
Select target devices → Schedule upgrade
     ↓
FirmwareDeploymentJob → Queue per device
     ↓
Worker executes:
  1. Send Download RPC (URL, checksum)
  2. Device downloads firmware
  3. Device sends TransferComplete event
  4. Verify checksum
  5. Trigger reboot
  6. Wait for device reconnection
  7. Verify new firmware version
     ↓
Update device record + alarm if failed
```

---

## 🔐 Security Architecture

### Authentication Flow

```
User → Login Form → POST /login
     ↓
AuthController → Validate credentials
     ↓
Check rate limiting (max 5 attempts/min)
     ↓
Hash comparison (bcrypt)
     ↓
Create session + CSRF token
     ↓
SecurityLog → Log successful login
     ↓
Redirect → Dashboard
```

### Authorization (RBAC)

```
HTTP Request → Route Middleware Stack
     ↓
1. Authenticate (auth middleware)
     ↓
2. CheckPermission (permission:{slug})
     ↓
3. User → Role → Permissions (many-to-many)
     ↓
4. If authorized → Controller
   If denied → 403 Forbidden + SecurityLog
```

### API Authentication

```
External API Client → Include header: X-API-Key
     ↓
ApiKeyMiddleware → Lookup api_keys table
     ↓
Verify key valid + not expired
     ↓
Attach client to request context
     ↓
Rate limit per client (configurable)
     ↓
Proceed to controller
```

---

## ⚡ Performance Optimizations

### 1. Database Query Optimization

**Eager Loading**:
```php
// BAD: N+1 queries
$devices = Device::all();
foreach ($devices as $device) {
    echo $device->manufacturer->name; // Query per iteration
}

// GOOD: 2 queries total
$devices = Device::with('manufacturer')->get();
```

**Query Caching**:
```php
// Cache frequent queries
$manufacturers = CacheService::remember('manufacturers.all', 3600, function() {
    return Manufacturer::orderBy('name')->get();
});
```

### 2. Redis Caching Strategy

**Device Parameters** (hot data):
```php
// Write-through cache
CacheService::putDeviceParams($deviceId, $params, ttl: 300);

// Read with fallback
$params = CacheService::getDeviceParams($deviceId)
    ?? DB::query()->where('device_id', $deviceId)->get();
```

### 3. Queue Processing

**Batch Jobs**:
```php
// Instead of 1000 individual jobs
foreach ($devices as $device) {
    dispatch(new ProvisionDevice($device)); // BAD
}

// Batch processing
dispatch(new BulkProvisionDevices($devices)); // GOOD
```

### 4. Asset Optimization

**Frontend**:
- Laravel Mix per bundling & minification
- CSS/JS concatenation
- Image optimization (WebP)
- CDN per static assets
- Browser caching headers

---

## 📊 Monitoring & Observability

### Health Checks

```
GET /up → Laravel health endpoint
├─ Database connectivity
├─ Redis connectivity
├─ Queue worker status
└─ Disk space available
```

### Metrics Collection

**Laravel Horizon**:
- Queue throughput
- Job success/failure rates
- Worker utilization
- Memory usage

**Custom Metrics**:
```php
// Track device operations
Metrics::increment('device.registered');
Metrics::gauge('devices.online', $count);
Metrics::histogram('tr069.request.duration', $ms);
```

### Logging

**Laravel Log Channels**:
```php
'channels' => [
    'stack' => ['daily', 'slack'],
    'daily' => ['path' => 'storage/logs/laravel.log'],
    'slack' => ['url' => env('LOG_SLACK_WEBHOOK_URL')],
    'security' => ['path' => 'storage/logs/security.log'],
],
```

---

## 🚀 Scalability Strategies

### Horizontal Scaling

**Application Servers**:
```
Load Balancer
├─ ACS Server #1 (primary)
├─ ACS Server #2 (secondary)
├─ ACS Server #3 (secondary)
└─ ACS Server #N
```

**Database Scaling**:
```
PostgreSQL Primary (write)
├─ Read Replica #1 (read)
├─ Read Replica #2 (read)
└─ Read Replica #N
```

**Redis Clustering**:
```
Redis Cluster
├─ Master #1 (shard 0-5460)
├─ Master #2 (shard 5461-10922)
└─ Master #3 (shard 10923-16383)
   Each master → 1+ replicas
```

### Vertical Scaling

**Server Sizing** (per 25k devices):
- CPU: 8 cores
- RAM: 32 GB
- Disk: 500 GB SSD
- Network: 1 Gbps

---

**Vedi Anche**:
- [Database Schema](database.md)
- [Tech Stack](tech-stack.md)
- [Performance Guide](performance.md)

---

**Ultima Modifica**: Ottobre 2025  
**Versione**: 1.0
