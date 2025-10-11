# Sistema ACS (Auto Configuration Server)

## Panoramica del Progetto
Sistema ACS carrier-grade sviluppato in Laravel 11 per la gestione di oltre 100.000 dispositivi CPE (Customer Premises Equipment) con supporto per i protocolli TR-069 (CWMP), TR-181 e TR-369 (USP).

## Caratteristiche Implementate

### 1. Server TR-069 (CWMP) con Session Management ✅
- **Endpoint SOAP**: `/tr069` per gestione richieste Inform dai dispositivi
- **Protocollo TR-069**: Supporto completo per comunicazione SOAP/HTTP
- **Auto-registrazione dispositivi**: Identificazione automatica tramite Serial Number, OUI, Product Class
- **Gestione Inform**: Parsing e gestione eventi dai dispositivi CPE
- **Session Management Stateful**: Sistema completo di gestione sessioni TR-069
  - Tabella `tr069_sessions` per tracking sessioni SOAP attive
  - Cookie HTTP per session tracking (standard TR-069)
  - Queue comandi SOAP con JSON array in database
  - Message ID incrementale per correlazione richiesta/risposta
  - Timeout automatico sessioni (30 secondi default)
  - Chiusura sessione su empty request
- **Task Processing**: Quando un dispositivo fa Inform, il sistema automaticamente accoda le task nella sessione e le invia sequenzialmente
- **CWMP Operations**: GetParameterValues, SetParameterValues, Reboot, Download implementati e funzionanti

### 2. Database Ottimizzato
- **PostgreSQL**: Database ad alte prestazioni con indici ottimizzati per 100K+ dispositivi
- **Tabelle principali**:
  - `cpe_devices`: Gestione dispositivi CPE con indici su serial_number, status, last_inform
  - `configuration_profiles`: Profili di configurazione per provisioning zero-touch
  - `firmware_versions`: Gestione versioni firmware con tracciamento
  - `device_parameters`: Parametri TR-181 per dispositivo
  - `provisioning_tasks`: Code di provisioning asincrono
  - `firmware_deployments`: Deployment firmware programmati

### 3. Sistema di Code Asincrono ✅
- **Laravel Horizon**: Installato e configurato per gestione code Redis
- **Queue Jobs**:
  - `ProcessProvisioningTask`: Processa task di provisioning in modo asincrono con retry logic (max 3 tentativi)
  - `ProcessFirmwareDeployment`: Gestione deployment firmware (preparato)
  - `SendTR069Request`: Invio richieste SOAP ai dispositivi con timeout e gestione errori
- **Auto-dispatch**: Le task vengono automaticamente accodate quando create e processate quando il dispositivo si connette
- **Retry Logic**: Configurato con 3 tentativi e delay di 60 secondi tra i retry
- **Timeout**: 120 secondi per ogni job per evitare blocchi

### 4. Sicurezza API ✅
- **API Key Authentication**: Middleware custom per protezione endpoints
- **Header richiesto**: `X-API-Key` o parametro `api_key` in query string
- **Protezione completa**: Tutte le API v1 protette da autenticazione
- **Configurazione**: API key configurabile tramite variabile d'ambiente `ACS_API_KEY`
- **Endpoints pubblici**: Solo TR-069 e Dashboard rimangono pubblici (necessario per protocollo)

### 5. API RESTful (v1) - Protette ✅
Tutte le API sono accessibili tramite `/api/v1/` con autenticazione API Key:

#### Gestione Dispositivi
- `GET /devices` - Lista dispositivi con filtri (status, manufacturer, search)
- `GET /devices/{id}` - Dettagli dispositivo
- `POST /devices` - Registrazione manuale dispositivo
- `PUT /devices/{id}` - Aggiornamento dispositivo
- `DELETE /devices/{id}` - Eliminazione dispositivo

#### Provisioning
- `POST /devices/{id}/provision` - Provisioning dispositivo con profilo
- `POST /devices/{id}/parameters/get` - Richiesta GetParameterValues TR-069
- `POST /devices/{id}/parameters/set` - Richiesta SetParameterValues TR-069
- `POST /devices/{id}/reboot` - Reboot remoto dispositivo
- `GET /tasks` - Lista task di provisioning
- `GET /tasks/{id}` - Dettagli task

#### Firmware Management
- `GET /firmware` - Lista versioni firmware
- `POST /firmware` - Upload nuova versione firmware
- `POST /firmware/{id}/deploy` - Deploy firmware su dispositivi selezionati

### 6. Interfaccia Web Professionale ✅
- **Template**: Soft UI Dashboard Laravel integrato
- **Endpoints Web**: Disponibili sotto `/acs/*`
- **Pagine Implementate**:
  - `/acs/dashboard` - Dashboard principale con statistiche real-time (dispositivi, task, firmware)
  - `/acs/devices` - Gestione dispositivi CPE con tabella paginata, bottoni azioni (View/Provision/Reboot)
  - `/acs/devices/{id}` - Pagina dettaglio dispositivo con info complete, parametri TR-181, task recenti
  - `/acs/provisioning` - Interfaccia provisioning con form e lista task
  - `/acs/firmware` - Upload firmware con validazione file/URL, deploy con selezione dispositivi multipli
  - `/acs/tasks` - Monitoraggio coda task Horizon
  - `/acs/profiles` - CRUD completo profili configurazione (create/edit/delete con modal)
  
- **Funzionalità CRUD Complete**:
  - **Profili Configurazione**: Form creazione con nome/descrizione/parametri JSON, modal modifica, modal eliminazione con conferma
  - **Firmware Management**: Form upload con manufacturer/model/version, upload file o URL download (validato), checkbox versione stabile, modal deploy con selezione dispositivi multipli
  - **Azioni Dispositivi**: Modal provisioning con selezione profilo, modal reboot con conferma, bottoni azioni su ogni dispositivo
  - **Dettaglio Dispositivo**: Pagina completa con info dispositivo, parametri TR-181, task recenti con badge stato
  
- **Caratteristiche UI**:
  - Design responsive e moderno con Soft UI Dashboard
  - Sidebar navigazione con sezioni organizzate (Gestione CPE, Sistema, TR-069)
  - Card statistiche con contatori real-time
  - Navbar con contatore dispositivi online (aggiornamento AJAX ogni 30s)
  - Tabelle con paginazione Laravel e gestione empty states
  - Badge colorati per stati (online/offline, completed/failed, stable/beta)
  - Modal Bootstrap per azioni (create/edit/delete/deploy/provision/reboot)
  - Validazione server-side con messaggi errore e success flash
  - Footer con link API, TR-069 Endpoint e Documentazione

## Architettura

### Modelli Eloquent
- `CpeDevice`: Dispositivo CPE con relazioni a profili, parametri, task
- `ConfigurationProfile`: Profili di configurazione con parametri JSON
- `FirmwareVersion`: Versioni firmware con hash e validazione
- `DeviceParameter`: Parametri TR-181 per dispositivo
- `ProvisioningTask`: Task asincroni di provisioning
- `FirmwareDeployment`: Deployment firmware programmati

### Servizi
- `TR069Service`: Generazione richieste SOAP TR-069 (GetParameterValues, SetParameterValues, Reboot, Download)

### Controllers
- `TR069Controller`: Gestione protocollo TR-069 CWMP
- `Api/DeviceController`: CRUD dispositivi via API
- `Api/ProvisioningController`: Provisioning e gestione task via API
- `Api/FirmwareController`: Gestione firmware via API
- `DashboardController`: Dashboard statistiche JSON (legacy API)
- `AcsController`: Interfaccia web completa con CRUD per profili, firmware, dispositivi (provision/reboot/dettaglio)

## Protocolli Supportati

### TR-069 (CWMP)
- ✅ Inform (registrazione dispositivi)
- ✅ InformResponse
- ✅ GetParameterValues (preparato)
- ✅ SetParameterValues (preparato)
- ✅ Reboot (preparato)
- ✅ Download (preparato per firmware)

### TR-181 (Data Model)
- ✅ Storage parametri device con tipo e percorso
- ✅ Gestione parametri writable/readonly
- ✅ Tracking ultimo aggiornamento

### TR-369 (USP)
- 🔄 Da implementare nella fase 2

## Scalabilità e Performance

### Ottimizzazioni Database
- Indici compositi su colonne frequentemente interrogate
- Soft deletes per tracciamento storico
- JSON fields per dati flessibili (device_info, wan_info, wifi_info)
- Paginazione su tutte le query (default 50 record)

### Sistema di Code (Preparato)
- Laravel Horizon installato per gestione code Redis
- Provisioning tasks con stato e retry logic
- Schedulazione task con timestamp

## Configurazione

### Variabili d'Ambiente
Il sistema utilizza le seguenti variabili d'ambiente (già configurate):
- `DB_CONNECTION=pgsql`
- `DATABASE_URL` (PostgreSQL)
- `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`

### Porta Server
- Il server Laravel è in ascolto su `0.0.0.0:5000`
- Endpoint principale: `http://0.0.0.0:5000`
- TR-069 endpoint: `http://0.0.0.0:5000/tr069`

### Asset Management
- Gli assets (CSS, JavaScript, immagini) usano **percorsi relativi** (`/assets/*`) invece di `asset()` helper
- Questo garantisce compatibilità con l'app mobile Replit e qualsiasi dominio/proxy
- Workflow configurato con auto-kill processi zombie PHP prima dell'avvio per evitare conflitti porta 5000

## Note sull'Implementazione MVP

### Funzionalità Complete ✅
- TR-069 Inform handling con auto-registrazione dispositivi
- Estrazione e salvataggio ConnectionRequestURL per comunicazione bidirezionale
- Queue Jobs asincroni con Horizon (ProcessProvisioningTask, ProcessFirmwareDeployment)
- SOAP operations (GetParameterValues, SetParameterValues, Reboot, Download)
- API RESTful protette con API Key authentication
- Zero-touch provisioning con risoluzione parametri da ConfigurationProfile
- Firmware deployment con task asincrone
- Interfaccia web completa con CRUD profili, firmware upload/deploy, azioni dispositivi
- Validazione firmware upload: richiede file o URL download per prevenire deploy vuoti

### Response Handling & Callback (Fase 2) ✅
- **Response Detection**: Sistema automatico per rilevare tipo messaggio SOAP (Inform vs Response)
- **GetParameterValuesResponse**: Parsing completo parametri TR-181 con aggiornamento task
- **SetParameterValuesResponse**: Verifica status code e aggiornamento stato task (completed/failed)
- **RebootResponse**: Conferma reboot dispositivo e completamento task
- **TransferComplete Callback**: Gestione completa download firmware con:
  - **Cookie-less Correlation**: TransferComplete arriva in nuova sessione HTTP senza cookie → correlazione via DeviceId extraction da SOAP body
  - **Deterministic Task Correlation**: CommandKey-based matching (`task_<id>`) per prevenire race conditions con deployment multipli simultanei
  - **Fallback Chain**: 3 livelli di correlazione (CommandKey → Session → Device fallback) con logging per diagnostica
  - Parsing FaultStruct per errori download
  - Aggiornamento task di provisioning con risultato dettagliato
  - Aggiornamento FirmwareDeployment con stato finale (completed/failed)
  - Tracciamento tempi (start_time, complete_time)
  - Logging fault code e fault string per troubleshooting

### Limitazioni Note (da completare in Fase 2+)
- **Horizon Dashboard**: Horizon installato ma dashboard non esposta (può essere aggiunta con route dedicata)
- **Connection Request**: Comunicazione bidirezionale ACS→CPE tramite ConnectionRequestURL (preparato ma non testato)
- **Diagnostica Avanzata**: Comandi ping, traceroute, speed test da implementare

## Prossimi Sviluppi (Fase 2)

### Alta Priorità
1. **TransferComplete & Callbacks**: Gestione eventi asincroni da dispositivi (TransferComplete, Diagnostics Complete)
2. **TR-069 Session Management**: Gestione transazioni multiple e session state
3. **Protocollo USP/TR-369**: Implementazione MQTT/WebSocket
4. **Diagnostica Avanzata**: Test remoti (ping, traceroute, speed test)
5. **Monitoraggio Real-time**: Metriche prestazioni e alerting
6. **Load Balancing**: Configurazione HAProxy/Nginx per architettura distribuita

### Media Priorità
6. **Backup/Restore Configurazioni**: Versionamento configurazioni dispositivi
7. **Interfaccia Call Center**: Dashboard operatori con troubleshooting tools
8. **Campagne Firmware**: Deploy di massa con finestre temporali e retry policies
9. **Sicurezza Avanzata**: Certificate management, device authentication, E2E encryption

## Struttura Directory
```
/
├── app/
│   ├── Http/Controllers/
│   │   ├── TR069Controller.php
│   │   ├── DashboardController.php
│   │   └── Api/
│   │       ├── DeviceController.php
│   │       ├── ProvisioningController.php
│   │       └── FirmwareController.php
│   ├── Models/
│   │   ├── CpeDevice.php
│   │   ├── ConfigurationProfile.php
│   │   ├── FirmwareVersion.php
│   │   ├── DeviceParameter.php
│   │   ├── ProvisioningTask.php
│   │   └── FirmwareDeployment.php
│   └── Services/
│       └── TR069Service.php
├── database/migrations/
├── routes/
│   ├── web.php (TR-069 endpoints, dashboard)
│   └── api.php (API v1)
└── config/
```

## Note Tecniche

### Gestione Dispositivi
- Ogni dispositivo è identificato univocamente dal `serial_number`
- Il campo `last_inform` traccia l'ultimo contatto TR-069
- Stati dispositivo: online, offline, provisioning, error
- Supporto soft delete per tracciamento storico

### Task di Provisioning
- Task types: set_parameters, get_parameters, add_object, delete_object, download, reboot, factory_reset, diagnostic
- Stato task: pending, processing, completed, failed, cancelled
- Retry automatico configurabile (max_retries)
- Schedulazione con timestamp

### Firmware Management
- Versioning con manufacturer, model, version
- Hash file per integrità
- Flag is_stable per release production
- Deployment schedulati con tracking progresso

## Versione
**v1.0.0** - MVP Base
- Data: 11 Ottobre 2025
- Status: In sviluppo
- Protocolli: TR-069 ✅, TR-181 ✅, TR-369 🔄
