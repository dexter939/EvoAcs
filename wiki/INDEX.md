# ACS Wiki - Indice Completo

**Wiki Documentazione Tecnica ACS (Auto Configuration Server)**  
**Versione Sistema**: 11.0 | **Data**: Ottobre 2025

---

## 📖 Navigazione Rapida

### 🏠 **[HOME](HOME.md)** - Pagina Principale
Panoramica progetto, quick links, statistiche, team & credits.

---

## 📚 Sezioni Principali

### 🚀 **Getting Started** - Guida Introduttiva
[Getting Started Index](getting-started/README.md)

- [Installazione](getting-started/installation.md) - Setup completo sistema
- [Configurazione](getting-started/configuration.md) - Environment & database
- [Quick Start](getting-started/quick-start.md) - Avvio rapido 5 minuti
- [Credenziali Test](getting-started/test-credentials.md) - Utenti e dati test

**Per chi inizia**: Parti da qui per setup iniziale e primo login.

---

### 🏗️ **Architecture** - Architettura Sistema
[Architecture Index](architecture/README.md)

- [Overview](architecture/overview.md) - Panoramica architettura carrier-grade
- [Database Schema](architecture/database.md) - Schema PostgreSQL 50+ tables
- [Tech Stack](architecture/tech-stack.md) - Laravel, PostgreSQL, Redis
- [Multi-Tenancy](architecture/multi-tenancy.md) - Architettura 3-level
- [Performance](architecture/performance.md) - Scalabilità 100k+ devices

**Per sviluppatori**: Comprendi design patterns e decisioni architetturali.

---

### ⚡ **Features** - Funzionalità
[Features Index](features/README.md)

#### Core Features
- [🚨 Real-Time Alarms & Monitoring](features/alarms.md) - SSE notifications, RBAC
- [📡 TR Protocols (TR-069, TR-369 USP)](features/tr-protocols.md) - CWMP & USP
- [🔧 Device Management](features/device-management.md) - Auto-registration, lifecycle

#### Advanced Features
- [⚙️ Advanced Provisioning](features/provisioning.md) - Bulk ops, scheduling, rollback
- [🤖 AI Configuration Assistant](features/ai-assistant.md) - GPT-4 powered
- [🗺️ Network Topology Map](features/network-topology.md) - vis.js visualization
- [📦 Firmware Management](features/firmware.md) - OTA updates
- [🔬 TR-143 Diagnostics](features/diagnostics.md) - Ping, Traceroute, Bandwidth
- [✅ BBF Parameter Validation](features/bbf-validation.md) - TR-181 compliance
- [🌐 NAT Traversal](features/nat-traversal.md) - Firewall bypass

**Per operations**: Scopri tutte le funzionalità disponibili.

---

### 🔌 **API Reference** - Documentazione API
[API Index](api/README.md)

- [REST API v1](api/rest-api.md) - Endpoints RESTful completi
- [TR-069 CWMP API](api/tr069.md) - SOAP/XML protocol
- [TR-369 USP API](api/tr369-usp.md) - Protocol Buffers endpoints
- [WebSocket & SSE](api/realtime.md) - Real-time communication
- [Authentication](api/authentication.md) - API Key auth

**Per integrazioni**: Documentazione API per sviluppatori esterni.

---

### 🔒 **Security** - Sicurezza
[Security Index](security/README.md)

- [RBAC - Role-Based Access Control](security/rbac.md) - 5 ruoli, 25+ permissions
- [Authentication & Sessions](security/authentication.md) - Login, sessions
- [Security Hardening](security/hardening.md) - Rate limiting, DDoS
- [Audit Logging](security/audit-logging.md) - Security event tracking
- [Rate Limiting](security/rate-limiting.md) - Anti-abuse protection

**Per security team**: Best practices e configurazione sicurezza.

---

### 🚀 **Deployment** - Messa in Produzione
[Deployment Index](deployment/README.md)

- [Requisiti Sistema](deployment/requirements.md) - Hardware, software, network
- [Production Deployment](deployment/production.md) - Setup carrier-grade
- [Docker Deployment](deployment/docker.md) - Containerized deployment
- [Nginx Configuration](deployment/nginx.md) - Reverse proxy, SSL/TLS
- [Monitoring & Alerting](deployment/monitoring.md) - Prometheus, Grafana

**Per DevOps**: Guide deployment e configurazione production.

---

### 💻 **Development** - Sviluppo
[Development Index](development/README.md)

- [Setup Ambiente](development/setup.md) - Dev environment configuration
- [Coding Standards](development/standards.md) - PSR-12, conventions
- [Testing](development/testing.md) - PHPUnit, Feature tests
- [Database Migrations](development/migrations.md) - Schema evolution
- [Queue System](development/queues.md) - Laravel Horizon, Redis

**Per developers**: Best practices sviluppo e testing.

---

### 🔧 **Troubleshooting** - Risoluzione Problemi
[Troubleshooting Index](troubleshooting/README.md)

- [Problemi Comuni](troubleshooting/common-issues.md) - FAQ & solutions
- [Log Analysis](troubleshooting/logs.md) - Laravel logs, debugging
- [Performance Issues](troubleshooting/performance.md) - Slow queries, optimization
- [Database Issues](troubleshooting/database.md) - Connection, migrations

**Per support**: Diagnostica e risoluzione problemi comuni.

---

## 📂 Documentazione Supplementare

### Guide Operations
**Path**: `docs/`

- [ALARMS_RBAC_GUIDE.md](../docs/ALARMS_RBAC_GUIDE.md) - Guida RBAC per team operations
- [ALARMS_RBAC_TESTING_GUIDE.md](../docs/ALARMS_RBAC_TESTING_GUIDE.md) - Testing QA alarms
- [ALARMS_SYSTEM_STATUS.md](../docs/ALARMS_SYSTEM_STATUS.md) - Status report sistema

---

## 🎯 Guide Per Ruolo

### 👨‍💼 **Manager / Decision Maker**
1. [HOME](HOME.md) - Panoramica progetto
2. [Features Index](features/README.md) - Cosa può fare il sistema
3. [Deployment/Requirements](deployment/requirements.md) - Costi e infrastruttura

### 👨‍💻 **Developer**
1. [Architecture/Overview](architecture/overview.md) - Design architetturale
2. [Development/Setup](development/setup.md) - Ambiente sviluppo
3. [API Reference](api/rest-api.md) - Documentazione API
4. [Development/Testing](development/testing.md) - Test automation

### 🔧 **DevOps / SRE**
1. [Deployment/Production](deployment/production.md) - Setup production
2. [Deployment/Docker](deployment/docker.md) - Containerization
3. [Deployment/Monitoring](deployment/monitoring.md) - Observability
4. [Troubleshooting](troubleshooting/README.md) - Debugging

### 👨‍💼 **NOC Operator**
1. [Getting Started/Quick Start](getting-started/quick-start.md) - Login e UI
2. [Features/Alarms](features/alarms.md) - Monitoraggio allarmi
3. [Features/Diagnostics](features/diagnostics.md) - Troubleshooting devices
4. [Security/RBAC](security/rbac.md) - Permissions disponibili

### 🔒 **Security Team**
1. [Security/RBAC](security/rbac.md) - Access control
2. [Security/Hardening](security/hardening.md) - Security measures
3. [Security/Audit Logging](security/audit-logging.md) - Event tracking
4. [API/Authentication](api/authentication.md) - API security

---

## 📊 Mappa Concettuale

```
ACS System
│
├─ Infrastructure
│  ├─ Laravel 11 Application
│  ├─ PostgreSQL 16+ Database
│  ├─ Redis 7+ Cache/Queue
│  └─ Prosody XMPP Server
│
├─ Protocols
│  ├─ TR-069 (CWMP) - SOAP/XML
│  ├─ TR-369 (USP) - Protocol Buffers
│  └─ 8+ altri protocolli TR
│
├─ Core Features
│  ├─ Device Management (100k+ devices)
│  ├─ Real-Time Alarms (SSE push)
│  ├─ Advanced Provisioning (bulk ops)
│  └─ Firmware Management (OTA)
│
├─ Security
│  ├─ RBAC (5 roles, 25+ permissions)
│  ├─ Authentication & Sessions
│  ├─ Security Hardening
│  └─ Audit Logging
│
└─ Integrations
   ├─ OpenAI GPT-4 (AI assistant)
   ├─ MQTT Brokers
   ├─ External APIs
   └─ Monitoring Tools
```

---

## 🔍 Come Trovare Informazioni

### Cerchi Info su...

**Installazione?**  
→ [Getting Started](getting-started/README.md)

**Come funziona TR-069?**  
→ [TR Protocols](features/tr-protocols.md)

**API per integrazioni?**  
→ [REST API Reference](api/rest-api.md)

**Problemi performance?**  
→ [Troubleshooting/Performance](troubleshooting/performance.md)

**Configurare RBAC?**  
→ [Security/RBAC](security/rbac.md)

**Deploy in produzione?**  
→ [Deployment/Production](deployment/production.md)

**Testing alarms system?**  
→ [ALARMS Testing Guide](../docs/ALARMS_RBAC_TESTING_GUIDE.md)

---

## 📈 Statistiche Wiki

```
Total Pages: 30+
Sezioni: 8
Code Examples: 200+
Diagrams: 50+
API Endpoints Documented: 50+
Features Covered: 11
```

---

## 🔄 Versioning

| Versione Wiki | Data | Highlights |
|---------------|------|------------|
| **1.0** | Ottobre 2025 | Wiki iniziale completa |
| 1.1 | TBD | TR-369 USP advanced features |
| 1.2 | TBD | AI Assistant deep dive |

---

## 📞 Contributi & Feedback

### Segnala Errori
- **Typos**: Apri issue su GitHub
- **Info Obsolete**: PR con aggiornamenti
- **Missing Docs**: Request via ticket

### Richiedi Documentazione
- Feature non documentate
- Use cases specifici
- Integration guides

---

## 📄 Licenza Documentazione

Questa documentazione è proprietaria e confidenziale.  
© 2025 - Tutti i diritti riservati.

---

**Wiki Mantenuta Da**: Development Team  
**Ultimo Aggiornamento**: Ottobre 2025  
**Versione Sistema ACS**: 11.0
