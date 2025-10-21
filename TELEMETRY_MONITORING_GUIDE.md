# 📊 ACS Telemetry & Monitoring Guide

## Overview
Il sistema ACS include un'infrastruttura completa di telemetria e monitoring per supportare operazioni carrier-grade con 100,000+ devices.

---

## 🎯 Sistema di Telemetria

### Architettura
- **SystemMetric Model**: Storage persistente metriche PostgreSQL
- **CollectSystemMetrics Command**: Collector automatico eseguito ogni 5 minuti
- **AlertMonitoringService**: Engine per alert rules e notifiche
- **Telemetry API**: RESTful endpoints per accesso programmatico

### Metriche Raccolte

#### System Resources
- `cpu_load_1min`, `cpu_load_5min`, `cpu_load_15min` - Carico CPU
- `memory_usage_mb`, `memory_peak_mb` - Utilizzo memoria (MB)
- `disk_usage_percent` - Utilizzo disco (%)

#### Database Metrics
- `db_query_time_ms` - Tempo query (milliseconds)
- `db_active_connections` - Connessioni attive
- `db_total_connections` - Connessioni totali
- `db_size_mb` - Dimensione database (MB)

#### Device Metrics
- `devices_total` - Totale devices registrati
- `devices_online`, `devices_offline` - Status devices
- `devices_online_percent` - Percentuale online
- `devices_active_last_hour` - Devices attivi ultima ora
- `devices_registered_last_hour` - Nuove registrazioni

#### Queue Metrics
- `queue_pending_jobs` - Jobs in coda
- `queue_failed_jobs` - Jobs falliti
- `queue_failed_batches` - Batch falliti

#### Alarm Metrics
- `alarms_active` - Alarms attivi
- `alarms_critical` - Alarms critici
- `alarms_last_hour` - Alarms ultima ora

#### Cache Metrics
- `cache_operational` - Status cache (1 = OK, 0 = Error)

---

## 🖥️ Dashboard Web

### Performance Monitoring Dashboard
**URL**: `/acs/performance-monitoring`

**Funzionalità**:
- Grafici tempo reale performance sistema
- CPU, Memory, Disk utilization
- Database query performance
- Queue throughput
- Device statistics

### Advanced Monitoring Dashboard
**URL**: `/acs/advanced-monitoring`

**Funzionalità**:
- Alert rules management
- Alert notifications history
- System metrics visualization
- Create/edit/delete alert rules
- Multi-channel notifications (Email, SMS, Webhook, Slack)

### Laravel Horizon Dashboard
**URL**: `/horizon`

**Funzionalità**:
- Queue monitoring real-time
- Job throughput metrics
- Failed jobs management
- Queue workers status
- Job batches tracking

---

## 🔌 Telemetry API

### Autenticazione
Tutti gli endpoint richiedono API Key:
```
Authorization: Bearer YOUR_API_KEY
```

### Endpoints

#### 1. Current Metrics
```http
GET /api/v1/telemetry/current
```

**Query Parameters**:
- `metrics[]` (optional) - Array di metriche da recuperare

**Response**:
```json
{
  "status": "success",
  "timestamp": "2025-10-21T09:08:20+00:00",
  "metrics": {
    "cpu_load_1min": {
      "value": 6.61,
      "type": "gauge",
      "tags": {"interval": "1min"},
      "recorded_at": "2025-10-21T09:08:18+00:00"
    },
    "memory_usage_mb": {
      "value": 28.00,
      "type": "gauge",
      "tags": {"unit": "MB"},
      "recorded_at": "2025-10-21T09:08:18+00:00"
    }
  }
}
```

#### 2. Metric History
```http
GET /api/v1/telemetry/history?metric=cpu_load_1min&hours=24
```

**Query Parameters**:
- `metric` (required) - Nome metrica
- `hours` (optional, default: 24) - Ore di storico

**Response**:
```json
{
  "status": "success",
  "metric": "cpu_load_1min",
  "hours": 24,
  "data_points": 288,
  "history": [
    {
      "timestamp": "2025-10-21T09:08:18+00:00",
      "value": 6.61,
      "tags": {"interval": "1min"}
    }
  ]
}
```

#### 3. Metrics Summary
```http
GET /api/v1/telemetry/summary
```

**Response**:
```json
{
  "status": "success",
  "timestamp": "2025-10-21T09:08:20+00:00",
  "total_metrics": 22,
  "summary": [
    {
      "metric": "cpu_load_1min",
      "data_points": 288,
      "avg": 6.61,
      "min": 5.24,
      "max": 7.99,
      "last_recorded": "2025-10-21T09:08:18+00:00"
    }
  ]
}
```

#### 4. Health Check
```http
GET /api/v1/telemetry/health
```

**Response**:
```json
{
  "status": "healthy",
  "healthy": true,
  "last_metric_collected": "2025-10-21T09:08:20+00:00",
  "minutes_since_last_metric": 2,
  "issues": []
}
```

---

## ⚙️ Configurazione

### Schedule Collector
Il collector automatico è configurato in `routes/console.php`:

```php
Schedule::command('metrics:collect')->everyFiveMinutes()->withoutOverlapping();
```

### Esecuzione Manuale
```bash
php artisan metrics:collect
```

### Verifica Schedule
```bash
php artisan schedule:list
```

---

## 🚨 Alert Rules

### Creare Alert Rule via Dashboard
1. Vai a `/acs/advanced-monitoring`
2. Click "Create Alert Rule"
3. Configura:
   - Nome e descrizione
   - Metrica da monitorare
   - Condizione (>, <, ==, !=)
   - Threshold value
   - Severity (low, medium, high, critical)
   - Duration (minuti)
   - Notification channels (email, sms, webhook, slack)
   - Recipients

### Esempio Alert Rules
- **High CPU**: `cpu_load_1min > 10` per 5 minuti
- **Low Memory**: `memory_usage_mb > 1000` per 10 minuti
- **Devices Offline**: `devices_offline > 100` per 15 minuti
- **Queue Backlog**: `queue_pending_jobs > 1000` per 5 minuti
- **Cache Down**: `cache_operational == 0` per 1 minuto

---

## 📈 Deployment Monitoring (Post-Staging)

Dopo il deployment su Replit, accedi a:

### Replit Monitoring Dashboard
1. **Overview Tab**: Status e configurazione deployment
2. **Logs Tab**: Real-time logs con filtering
3. **Resources Tab**: CPU e memoria utilization
4. **Analytics Tab** (VM Deployment):
   - Page views statistics
   - Top URLs e referrers
   - HTTP status codes
   - Request durations
   - Top browsers/devices/countries

### Metriche Disponibili
- Request throughput (req/s)
- Response times (p50, p95, p99)
- Error rates
- CPU/Memory usage trends
- Database connections

---

## 🔍 Troubleshooting

### Nessuna Metrica Raccolta
```bash
# Verifica schedule
php artisan schedule:list

# Esegui manualmente
php artisan metrics:collect

# Verifica database
SELECT COUNT(*) FROM system_metrics;
```

### Alert Non Triggerati
```bash
# Verifica alert rules
SELECT * FROM alert_rules WHERE is_active = true;

# Esegui valutazione manuale
php artisan tinker
>>> app(\App\Services\AlertMonitoringService::class)->evaluateAllRules();
```

### Performance Issues
```bash
# Pulisci vecchie metriche (>30 giorni)
php artisan tinker
>>> App\Models\SystemMetric::where('recorded_at', '<', now()->subDays(30))->delete();
```

---

## 📊 Best Practices

### 1. Retention Policy
Configura job scheduled per pulizia metriche vecchie:
```php
Schedule::call(function () {
    SystemMetric::where('recorded_at', '<', now()->subDays(30))->delete();
})->weekly();
```

### 2. Alert Fatigue
- Evita soglie troppo basse
- Usa `duration_minutes` adeguato
- Configura cooldown tra notifiche

### 3. API Rate Limiting
- Limita chiamate telemetry API a 60/min
- Usa caching per dashboard

### 4. Monitoring Monitoring
- Crea alert per "no metrics collected"
- Monitor health endpoint ogni 5 minuti

---

## 🎯 Metriche Correnti (Snapshot)

```
CPU Load (1/5/15min):     6.6 / 7.8 / 8.8
Memory Usage:             28 MB
Disk Usage:               65.76%
DB Active Connections:    1
DB Size:                  14.23 MB
Devices Total:            0
Devices Online:           0
Queue Pending:            0
Queue Failed:             0
Alarms Active:            0
Cache Status:             ✅ Operational
```

---

## 📚 Risorse Aggiuntive

- **Codice**: `app/Console/Commands/CollectSystemMetrics.php`
- **Service**: `app/Services/AlertMonitoringService.php`
- **Model**: `app/Models/SystemMetric.php`
- **API Controller**: `app/Http/Controllers/Api/TelemetryController.php`
- **Dashboard Views**: `resources/views/acs/performance-monitoring.blade.php`

---

**Sistema pronto per monitoring carrier-grade 100K+ devices!** 🚀
