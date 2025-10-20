# Getting Started - Guida Introduttiva

Benvenuto nella guida introduttiva per ACS (Auto Configuration Server). Questa sezione ti guiderà attraverso l'installazione, configurazione e primo utilizzo del sistema.

---

## 📚 Indice Sezione

1. [Installazione](installation.md) - Setup completo del sistema
2. [Configurazione Iniziale](configuration.md) - Configurazione ambiente e database
3. [Quick Start](quick-start.md) - Avvio rapido e primo login
4. [Credenziali Test](test-credentials.md) - Utenti e dati di test

---

## ⚡ Quick Start (5 minuti)

### Prerequisiti Rapidi
```bash
# Verifica versioni richieste
php --version    # >= 8.2
psql --version   # >= 16
redis-cli --version  # >= 7
```

### Setup Express
```bash
# 1. Clone repository (se non già presente)
git clone <repository-url> acs
cd acs

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Configure database (.env)
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=acs_production
DB_USERNAME=acs_user
DB_PASSWORD=your_secure_password

# 5. Run migrations & seeders
php artisan migrate --seed

# 6. Start server
php artisan serve --host=0.0.0.0 --port=5000
```

### Primo Login
```
URL: http://localhost:5000
Email: admin@acs.local
Password: password
```

**✅ Sistema pronto!** Vai alla [Dashboard](http://localhost:5000/acs/dashboard)

---

## 📖 Guide Dettagliate

### [Installazione Completa](installation.md)
Guida step-by-step per installazione production-ready:
- Requisiti sistema dettagliati
- Installazione dipendenze (PHP, PostgreSQL, Redis, Nginx)
- Configurazione Prosody XMPP per TR-369
- Setup Laravel Horizon
- Configurazione SSL/TLS

### [Configurazione](configuration.md)
Configurazione avanzata del sistema:
- Environment variables (.env)
- Database connection & optimization
- Redis configuration
- Queue workers (Laravel Horizon)
- SMTP/Email settings
- OpenAI API integration
- CORS & Security headers

### [Quick Start](quick-start.md)
Tutorial passo-passo per iniziare:
- Login e navigazione dashboard
- Registrazione primo dispositivo CPE
- Configurazione primo template
- Test connessione TR-069
- Visualizzazione alarms real-time

### [Credenziali Test](test-credentials.md)
Utenti e dati pre-caricati per testing:
- Utenti con diversi ruoli (Admin, Manager, Operator, Viewer)
- Dispositivi CPE di esempio
- Configuration templates
- Alarms di test

---

## 🎯 Percorso Apprendimento Consigliato

### 1️⃣ Settimana 1: Fondamentali
- [ ] Installazione sistema
- [ ] Primo login e familiarizzazione UI
- [ ] Registrazione 1-5 dispositivi test
- [ ] Esplorazione dashboard e statistiche

### 2️⃣ Settimana 2: Device Management
- [ ] TR-069 connection request
- [ ] TR-369 USP messaging
- [ ] Configuration templates
- [ ] Firmware upload & deployment

### 3️⃣ Settimana 3: Monitoring & Provisioning
- [ ] Alarms & real-time monitoring
- [ ] Advanced provisioning bulk operations
- [ ] Network topology visualization
- [ ] Performance metrics

### 4️⃣ Settimana 4: Advanced Features
- [ ] AI Configuration Assistant
- [ ] Multi-tenant configuration
- [ ] Security & RBAC management
- [ ] Production deployment

---

## 🔑 Concetti Chiave

### ACS (Auto Configuration Server)
Server centrale che gestisce dispositivi CPE tramite protocolli TR (Technical Report) della Broadband Forum.

### CPE (Customer Premises Equipment)
Dispositivi presso il cliente finale (router, modem, gateway, ONT, set-top box).

### TR-069 (CWMP)
Protocollo standard per remote management via SOAP/XML con session-based communication.

### TR-369 (USP)
Universal Service Platform moderno basato su Protocol Buffers con supporto MQTT/WebSocket/XMPP.

### Zero-Touch Provisioning
Auto-configurazione dispositivi senza intervento manuale tramite configuration profiles.

### Carrier-Grade
Sistema enterprise progettato per gestire 100.000+ dispositivi con alta affidabilità e performance.

---

## 🚨 Troubleshooting Comune

### Server Non Parte
```bash
# Verifica porte disponibili
sudo netstat -tlnp | grep :5000

# Kill processo esistente
sudo kill -9 $(lsof -ti:5000)

# Restart server
php artisan serve --host=0.0.0.0 --port=5000
```

### Database Connection Failed
```bash
# Test connessione PostgreSQL
psql -h localhost -U acs_user -d acs_production

# Verifica .env
cat .env | grep DB_

# Ricrea database
php artisan migrate:fresh --seed
```

### Redis Connection Error
```bash
# Verifica Redis running
redis-cli ping  # Deve rispondere "PONG"

# Start Redis
sudo systemctl start redis

# Test connessione Laravel
php artisan tinker
>>> Redis::ping();
```

### Queue Worker Non Processa Job
```bash
# Restart Horizon
php artisan horizon:terminate

# Start Horizon manualmente
php artisan horizon

# Verifica jobs in coda
php artisan queue:work --once
```

---

## 📚 Risorse Aggiuntive

### Video Tutorials
- [ACS Overview & Demo](link) - 15 min
- [TR-069 Setup Guide](link) - 20 min
- [TR-369 USP Configuration](link) - 25 min
- [Advanced Provisioning Workflow](link) - 30 min

### Documentazione Esterna
- [Broadband Forum TR-069](https://www.broadband-forum.org/technical/download/TR-069.pdf)
- [USP Architecture](https://usp.technology/)
- [Laravel 11 Docs](https://laravel.com/docs/11.x)
- [PostgreSQL Manual](https://www.postgresql.org/docs/16/)

### Community & Support
- **GitHub Issues**: Report bug e feature requests
- **Forum**: Community discussion
- **Email Support**: support@acs-system.local
- **Slack Channel**: #acs-support

---

## 🎓 Certificazioni & Training

### ACS Administrator Certification
- Durata: 2 settimane
- Costo: €999
- Include: Installazione, configurazione, monitoring, troubleshooting

### ACS Developer Certification
- Durata: 4 settimane
- Costo: €1,999
- Include: API development, custom integrations, protocol implementation

### ACS Security Specialist
- Durata: 1 settimana
- Costo: €799
- Include: RBAC, security hardening, compliance, audit logging

---

## ✅ Checklist Setup Completo

### Installazione Base
- [ ] PHP 8.2+ installato
- [ ] PostgreSQL 16+ configurato
- [ ] Redis 7+ running
- [ ] Composer dependencies installed
- [ ] npm packages installed

### Configurazione
- [ ] .env file configurato
- [ ] Database migrato
- [ ] Seeders eseguiti
- [ ] Laravel Horizon attivo
- [ ] Prosody XMPP configured (TR-369)

### Testing
- [ ] Login come admin funzionante
- [ ] Dashboard caricata correttamente
- [ ] Alarms dashboard accessibile
- [ ] SSE stream connesso
- [ ] Queue worker processa jobs

### Production Ready
- [ ] SSL/TLS configurato
- [ ] Nginx reverse proxy setup
- [ ] Firewall configurato
- [ ] Backup automatici attivi
- [ ] Monitoring & alerting setup

---

**Prossimo Step**: [Installazione Dettagliata →](installation.md)

---

**Ultima Modifica**: Ottobre 2025  
**Versione**: 1.0
