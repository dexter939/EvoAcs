# Wiki ACS - Auto Configuration Server

**Benvenuto nella documentazione completa del sistema ACS (Auto Configuration Server)**

[![Laravel](https://img.shields.io/badge/Laravel-11-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16+-blue.svg)](https://postgresql.org)
[![License](https://img.shields.io/badge/License-Proprietary-yellow.svg)]()

---

## 📖 Indice Generale

### 🚀 [Getting Started](getting-started/README.md)
- [Installazione](getting-started/installation.md)
- [Configurazione Iniziale](getting-started/configuration.md)
- [Primo Avvio](getting-started/quick-start.md)
- [Credenziali Test](getting-started/test-credentials.md)

### 🏗️ [Architettura](architecture/README.md)
- [Panoramica Sistema](architecture/overview.md)
- [Database Schema](architecture/database.md)
- [Stack Tecnologico](architecture/tech-stack.md)
- [Multi-Tenancy](architecture/multi-tenancy.md)
- [Performance & Scalabilità](architecture/performance.md)

### ⚡ [Funzionalità](features/README.md)
- [Gestione Dispositivi CPE](features/device-management.md)
- [Protocolli TR (TR-069, TR-369 USP)](features/tr-protocols.md)
- [Real-time Alarms & Monitoring](features/alarms.md)
- [Advanced Provisioning](features/provisioning.md)
- [AI Configuration Assistant](features/ai-assistant.md)
- [Network Topology Map](features/network-topology.md)
- [Firmware Management](features/firmware.md)
- [TR-143 Diagnostics](features/diagnostics.md)
- [BBF Parameter Validation](features/bbf-validation.md)
- [NAT Traversal](features/nat-traversal.md)

### 🔌 [API Reference](api/README.md)
- [RESTful API v1](api/rest-api.md)
- [TR-069 CWMP Endpoints](api/tr069.md)
- [TR-369 USP Endpoints](api/tr369-usp.md)
- [WebSocket & SSE](api/realtime.md)
- [Authentication](api/authentication.md)

### 🔒 [Sicurezza](security/README.md)
- [RBAC - Role-Based Access Control](security/rbac.md)
- [Autenticazione & Sessioni](security/authentication.md)
- [Security Hardening](security/hardening.md)
- [Audit Logging](security/audit-logging.md)
- [Rate Limiting & DDoS Protection](security/rate-limiting.md)

### 🚀 [Deployment](deployment/README.md)
- [Requisiti Sistema](deployment/requirements.md)
- [Deployment Production](deployment/production.md)
- [Docker Deployment](deployment/docker.md)
- [Nginx Configuration](deployment/nginx.md)
- [Monitoring & Alerting](deployment/monitoring.md)

### 💻 [Sviluppo](development/README.md)
- [Ambiente Sviluppo](development/setup.md)
- [Coding Standards](development/standards.md)
- [Testing](development/testing.md)
- [Database Migrations](development/migrations.md)
- [Queue System](development/queues.md)

### 🔧 [Troubleshooting](troubleshooting/README.md)
- [Problemi Comuni](troubleshooting/common-issues.md)
- [Log Analysis](troubleshooting/logs.md)
- [Performance Issues](troubleshooting/performance.md)
- [Database Issues](troubleshooting/database.md)

---

## 🎯 Quick Links

| Categoria | Link Rapidi |
|-----------|-------------|
| **Setup Rapido** | [Installazione](getting-started/installation.md) • [Quick Start](getting-started/quick-start.md) |
| **Protocolli** | [TR-069](features/tr-protocols.md#tr-069) • [TR-369 USP](features/tr-protocols.md#tr-369-usp) |
| **RBAC** | [Guida Permissions](security/rbac.md) • [Testing RBAC](security/rbac-testing.md) |
| **API** | [REST API](api/rest-api.md) • [TR-069 API](api/tr069.md) • [USP API](api/tr369-usp.md) |
| **Deployment** | [Production](deployment/production.md) • [Docker](deployment/docker.md) |

---

## 📊 Panoramica Progetto

### Cos'è ACS?

**ACS (Auto Configuration Server)** è un sistema carrier-grade progettato per gestire oltre **100.000+ dispositivi CPE** (Customer Premises Equipment) attraverso una suite completa di protocolli TR (Technical Report) della Broadband Forum.

### Caratteristiche Principali

#### 🌐 Multi-Protocol Support
- **TR-069 (CWMP)** - Remote management via SOAP/XML
- **TR-369 (USP)** - Universal Service Platform via Protocol Buffers
- **TR-181** - Device Data Model for CPE parameters
- **TR-143** - Network diagnostics (Ping, Traceroute, Download/Upload tests)
- **7+ altri protocolli TR** per VoIP, Storage, IoT, STB/IPTV, Femtocell

#### 🚀 Enterprise Features
- ✅ **Zero-Touch Provisioning** - Configurazione automatica dispositivi
- ✅ **Firmware Management** - Aggiornamenti firmware OTA (Over-The-Air)
- ✅ **Real-time Monitoring** - Allarmi e SSE push notifications carrier-grade
- ✅ **Advanced Provisioning** - Bulk operations, scheduling, rollback
- ✅ **AI-Powered Assistant** - OpenAI GPT-4 per configurazione e diagnostica
- ✅ **Network Topology** - Visualizzazione real-time rete LAN/WiFi
- ✅ **Multi-Tenant** - Architettura 3-level hierarchy per ISP/MSP

#### 🔒 Security & Compliance
- ✅ **RBAC Granulare** - 5 ruoli predefiniti, 25+ permissions
- ✅ **Security Hardening** - Rate limiting, DDoS protection, input sanitization
- ✅ **Audit Logging** - Security event tracking completo
- ✅ **BBF-Compliant** - Validazione parametri standard Broadband Forum

#### ⚡ Performance & Scalability
- ✅ **100.000+ devices** supportati
- ✅ **Redis Caching** - Multi-tier cache per high-traffic
- ✅ **Queue System** - Laravel Horizon con Redis per async processing
- ✅ **Database Optimization** - Indexing strategico PostgreSQL
- ✅ **SSE Streaming** - Real-time notifications carrier-grade

---

## 🛠️ Stack Tecnologico

### Backend
- **Laravel 11** - PHP Framework
- **PHP 8.2+** - Linguaggio server-side
- **PostgreSQL 16+** - Database relazionale
- **Redis 7+** - Cache e Queue driver

### Frontend
- **Soft UI Dashboard PRO** - Template admin moderno
- **Alpine.js** - JavaScript framework leggero
- **Chart.js** - Grafici interattivi
- **vis.js** - Network topology visualization
- **DataTables** - Tabelle responsive avanzate

### Protocol Support
- **Google Protocol Buffers** - TR-369 USP encoding
- **PHP-MQTT Client** - MQTT broker transport
- **Prosody XMPP** - XMPP messaging protocol
- **Guzzle HTTP** - HTTP/REST client

### AI & Analytics
- **OpenAI GPT-4o-mini** - AI configuration assistant
- **Laravel Horizon** - Queue monitoring dashboard

---

## 📈 Statistiche Progetto

```
Dispositivi Supportati:    100.000+
Protocolli TR Implementati: 10+
Database Tables:           50+
API Endpoints:             200+
RBAC Permissions:          25+
Ruoli Predefiniti:         5
Lines of Code:             50.000+
```

---

## 🎓 Per Chi Inizia

### 1. Installa il Sistema
Segui la [guida installazione](getting-started/installation.md) per setup completo.

### 2. Configura Primo Dispositivo
Leggi la [guida device management](features/device-management.md) per registrare il primo CPE.

### 3. Esplora Features
- [Alarms & Monitoring](features/alarms.md) - Sistema monitoraggio real-time
- [Advanced Provisioning](features/provisioning.md) - Provisioning enterprise-grade
- [AI Assistant](features/ai-assistant.md) - Assistente configurazione AI-powered

### 4. Deploy in Production
Consulta la [guida deployment](deployment/production.md) per messa in produzione.

---

## 📞 Supporto & Contributi

### Documentazione
- **Wiki Completa**: Questa documentazione
- **API Reference**: [api/README.md](api/README.md)
- **Troubleshooting**: [troubleshooting/README.md](troubleshooting/README.md)

### Risorse Esterne
- [Broadband Forum TR Specifications](https://www.broadband-forum.org/)
- [Laravel Documentation](https://laravel.com/docs)
- [TR-069 Protocol Spec](https://www.broadband-forum.org/technical/download/TR-069.pdf)
- [TR-369 USP Spec](https://usp.technology/)

---

## 📝 Versioning

| Versione | Data | Highlights |
|----------|------|------------|
| **v11.0** | Ottobre 2025 | Alarms & Monitoring, RBAC completo, AI Assistant |
| **v10.5** | Settembre 2025 | TR-369 USP implementation, Network Topology |
| **v10.0** | Agosto 2025 | Advanced Provisioning, BBF Validation |
| **v9.0** | Luglio 2025 | TR-069 CWMP completo, Multi-tenancy |

---

## 🏆 Team & Credits

**Sviluppato per**: ISP & MSP carrier-grade operations  
**Stack**: Laravel 11 + PostgreSQL + Redis  
**UI Template**: Soft UI Dashboard PRO by Creative Tim  
**AI Integration**: OpenAI GPT-4o-mini  

---

## 📄 Licenza

Proprietaria - Tutti i diritti riservati

---

**Ultima Modifica**: Ottobre 2025  
**Versione Wiki**: 1.0  
**Sistema**: ACS Carrier-Grade v11.0
