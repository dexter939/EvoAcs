# ACS GitHub Release Auto-Update System

Sistema carrier-grade per la gestione automatica degli aggiornamenti software ACS tramite GitHub Releases con approvazione manuale.

## 📋 Indice

- [Panoramica](#panoramica)
- [Architettura](#architettura)
- [Configurazione Iniziale](#configurazione-iniziale)
- [Formato Release Package](#formato-release-package)
- [Workflow Completo](#workflow-completo)
- [API Endpoints](#api-endpoints)
- [CLI Commands](#cli-commands)
- [Security & Best Practices](#security--best-practices)

---

## 🎯 Panoramica

Il sistema implementa un flusso completo di gestione aggiornamenti:

1. **Check Automatico**: Ogni settimana controlla GitHub per nuove releases
2. **Download & Staging**: Download automatico package in area staging
3. **Approvazione Manuale**: Admin approva/rigetta update via dashboard/API
4. **Deployment Controllato**: Applicazione con backup automatico e rollback su failure
5. **Health Checks**: Verifica post-deployment con auto-rollback se necessario

### Vantaggi

✅ **Sicurezza**: Approvazione manuale + validazione checksum SHA256  
✅ **Affidabilità**: Backup automatico + rollback transactional  
✅ **Tracciabilità**: Storia completa deployment con audit trail  
✅ **Zero-downtime**: Health checks pre/post deployment  
✅ **Multi-environment**: Supporto local/staging/production separati  

---

## 🏗️ Architettura

### Componenti Principali

```
┌──────────────────┐
│  GitHub Release  │ ← Pubblicazione nuova versione
└────────┬─────────┘
         │
         ▼
┌──────────────────────────┐
│ CheckGitHubUpdates (CLI) │ ← Scheduled settimanalmente
│ - Fetch latest release   │
│ - Compare versions       │
│ - Download + Validate    │
└────────┬─────────────────┘
         │
         ▼
┌───────────────────────┐
│  UpdateStaging        │ ← Area di staging sicura
│  storage/app/updates/ │
│  - Package .tar.gz    │
│  - Extracted files    │
│  - Checksum SHA256    │
└────────┬──────────────┘
         │
         ▼
┌──────────────────────┐
│  Admin Dashboard     │ ← Approvazione manuale
│  - Review changelog  │
│  - Approve/Reject    │
│  - Schedule deploy   │
└────────┬─────────────┘
         │
         ▼
┌─────────────────────────┐
│ UpdateApplication       │ ← Deploy transactional
│ - Backup current        │
│ - Copy files            │
│ - Run migrations        │
│ - Health checks         │
│ - Rollback on failure   │
└─────────────────────────┘
```

### Database Schema

```sql
-- system_versions table estesa
ALTER TABLE system_versions ADD (
    github_release_url VARCHAR,
    github_release_tag VARCHAR(100),
    download_path VARCHAR,
    package_checksum VARCHAR(64),  -- SHA256
    approval_status ENUM('pending', 'approved', 'rejected', 'scheduled'),
    approved_by VARCHAR(100),
    approved_at TIMESTAMP,
    scheduled_at TIMESTAMP,
    changelog TEXT,
    release_notes TEXT
);
```

---

## ⚙️ Configurazione Iniziale

### 1. GitHub Repository

Configura le variabili d'ambiente:

```bash
# .env
GITHUB_REPO_OWNER=dexter939
GITHUB_REPO_NAME=EvoAcs
```

### 2. Connessione GitHub

Il sistema usa **Replit GitHub Connector** già configurato per:
- Autenticazione OAuth automatica
- Token refresh automatico
- Rate limiting: 5000 req/h (authenticated)

### 3. Scheduler

Il comando `system:check-updates` è già configurato in `routes/console.php`:

```php
Schedule::command('system:check-updates --auto-stage')
    ->weekly()
    ->mondays()
    ->at('03:00')
    ->withoutOverlapping()
    ->onOneServer();
```

**Frequenza**: Ogni lunedì alle 03:00 AM  
**Comportamento**: Auto-staging nuove releases

---

## 📦 Formato Release Package

### Struttura Release GitHub

Ogni release deve seguire questo formato:

```yaml
Tag: v1.2.0
Name: "ACS v1.2.0 - Feature X"
Body: |
  ## Novità
  - Feature A
  - Feature B
  
  ## Bug Fix
  - Fix #123
  
  ## Breaking Changes
  - NONE

Assets:
  - acs-v1.2.0.tar.gz (package principale)
  - checksums.txt (SHA256 hashes)
```

### Package Content (.tar.gz)

Il package deve contenere:

```
acs-v1.2.0/
├── app/                    # Codice applicazione
├── config/                 # File configurazione
├── database/migrations/    # Nuove migrations
├── routes/                 # Routes
├── resources/             # Views & assets
├── public/                # Assets pubblici
└── .release-info.json     # Metadati release
```

**IMPORTANTE**: Escludere sempre:
- `vendor/` (composer install su target)
- `node_modules/` (npm install su target)
- `storage/` (dati runtime)
- `.env` (secrets specifici ambiente)
- `.git/` (version control)

### .release-info.json

```json
{
  "version": "1.2.0",
  "release_date": "2025-10-21",
  "min_php_version": "8.2",
  "min_laravel_version": "11.0",
  "requires_migration": true,
  "breaking_changes": false,
  "dependencies": {
    "php": "^8.2",
    "composer": {
      "new": ["package/name:^1.0"],
      "removed": []
    }
  }
}
```

### Checksum Generation

```bash
# Su repository locale prima del release
cd release-package/
tar -czf acs-v1.2.0.tar.gz acs-v1.2.0/
sha256sum acs-v1.2.0.tar.gz > checksums.txt

# Upload su GitHub Release
# - acs-v1.2.0.tar.gz
# - checksums.txt
```

---

## 🔄 Workflow Completo

### Fase 1: Check & Download (Automatico)

```bash
# Eseguito automaticamente ogni lunedì 03:00
php artisan system:check-updates --auto-stage

# Oppure manuale
php artisan system:check-updates --environment=production
```

**Output**:
```
🔍 Checking for ACS updates...

📦 Latest GitHub Release: v1.2.0
💻 Current System Version: v1.0.0

🆕 New version available: v1.2.0

┌─────────────┬──────────────────────┐
│ Field       │ Value                │
├─────────────┼──────────────────────┤
│ Release Name│ ACS v1.2.0          │
│ Tag         │ v1.2.0              │
│ Published   │ 2025-10-21 10:00    │
│ Author      │ admin               │
│ Assets      │ 2                   │
└─────────────┴──────────────────────┘

⬇️  Downloading and staging update...
[███████████████████████████] 100% - Validating package...

✅ Update staged successfully!

┌──────────────────┬────────┬──────────────────────────┐
│ Check            │ Status │ Message                  │
├──────────────────┼────────┼──────────────────────────┤
│ package_exists   │ ✓      │ Package file exists      │
│ checksum_valid   │ ✓      │ Checksum valid           │
│ extracted_files  │ ✓      │ Package extracted        │
│ disk_space       │ ✓      │ Sufficient disk space    │
└──────────────────┴────────┴──────────────────────────┘

👉 Next steps:
   1. Review the update in the admin dashboard
   2. Approve or reject the update
   3. Apply the update when ready
```

### Fase 2: Review & Approval (Manuale)

#### Via API

```bash
# 1. Lista pending updates
curl -H "X-API-KEY: your-key" \
  https://acs.example.com/api/v1/system/updates/pending

{
  "status": "success",
  "updates": [
    {
      "id": 42,
      "version": "1.2.0",
      "release_tag": "v1.2.0",
      "release_url": "https://github.com/org/acs/releases/tag/v1.2.0",
      "changelog": "## Features\n- Feature A\n- Feature B",
      "created_at": "2025-10-21T03:15:00Z",
      "approval_status": "pending"
    }
  ],
  "count": 1
}

# 2. Validate staged update
curl -H "X-API-KEY: your-key" \
  https://acs.example.com/api/v1/system/updates/42/validate

{
  "success": true,
  "version": "1.2.0",
  "checks": {
    "package_exists": { "status": "ok", "message": "Package file exists" },
    "checksum_validation": { "status": "ok", "message": "Checksum valid" },
    "disk_space": { "status": "ok", "message": "Sufficient disk space" }
  }
}

# 3a. Approve update
curl -X POST -H "X-API-KEY: your-key" \
  https://acs.example.com/api/v1/system/updates/42/approve

{
  "success": true,
  "message": "Update approved successfully",
  "version": "1.2.0",
  "approved_by": "admin",
  "approved_at": "2025-10-21T09:30:00Z"
}

# 3b. Oppure reject
curl -X POST -H "X-API-KEY: your-key" \
  -d "reason=Not ready for production" \
  https://acs.example.com/api/v1/system/updates/42/reject

# 3c. Oppure schedule
curl -X POST -H "X-API-KEY: your-key" \
  -d "scheduled_at=2025-10-22T02:00:00Z" \
  https://acs.example.com/api/v1/system/updates/42/schedule
```

### Fase 3: Application (Manuale o Scheduled)

```bash
# Apply approved update
curl -X POST -H "X-API-KEY: your-key" \
  https://acs.example.com/api/v1/system/updates/42/apply

{
  "success": true,
  "version": "1.2.0",
  "migrations_run": 3,
  "health_checks": {
    "database": { "status": "ok" },
    "cache": { "status": "ok" },
    "storage": { "status": "ok" },
    "queue": { "status": "ok" },
    "critical_tables": { "status": "ok" }
  },
  "backup_path": "/storage/backups/1.2.0-1729504800",
  "duration": "2m 15s"
}
```

**Processo Interno**:

1. ✅ Pre-flight checks
2. 📦 Create backup (`/storage/backups/`)
3. 📂 Copy files (exclude vendor/node_modules/storage)
4. 🗄️ Run migrations (transactional)
5. 🧹 Clear caches (config/route/view)
6. 🏥 Health checks (5-stage)
7. ✅ Mark as success + set current
8. ❌ **On failure**: Auto-rollback backup + migrations

---

## 🔌 API Endpoints

### Lista Pending Updates

```http
GET /api/v1/system/updates/pending?environment=production
Authorization: X-API-KEY: your-key
```

**Response**:
```json
{
  "status": "success",
  "updates": [...],
  "count": 1
}
```

### Approve Update

```http
POST /api/v1/system/updates/{id}/approve
Authorization: X-API-KEY: your-key
```

**Response**:
```json
{
  "success": true,
  "message": "Update approved successfully",
  "version": "1.2.0",
  "approved_by": "admin",
  "approved_at": "2025-10-21T09:30:00Z"
}
```

### Reject Update

```http
POST /api/v1/system/updates/{id}/reject
Authorization: X-API-KEY: your-key
Content-Type: application/json

{
  "reason": "Not ready for production"
}
```

### Schedule Update

```http
POST /api/v1/system/updates/{id}/schedule
Authorization: X-API-KEY: your-key
Content-Type: application/json

{
  "scheduled_at": "2025-10-22T02:00:00Z"
}
```

### Apply Update

```http
POST /api/v1/system/updates/{id}/apply
Authorization: X-API-KEY: your-key
```

**⚠️ ATTENZIONE**: Questo endpoint applica l'update immediatamente. Assicurarsi che:
- Update sia in stato `approved`
- Health checks siano OK
- Backup automatico funzioni correttamente

### Validate Staged Update

```http
GET /api/v1/system/updates/{id}/validate
Authorization: X-API-KEY: your-key
```

---

## 💻 CLI Commands

### Check Updates

```bash
# Check manuale con auto-staging
php artisan system:check-updates --auto-stage

# Check specifico ambiente senza auto-staging
php artisan system:check-updates --environment=staging

# Check production (default)
php artisan system:check-updates
```

### Run Migrations

```bash
# Le migrations vengono eseguite automaticamente durante apply
# Oppure manualmente:
php artisan migrate --force
```

---

## 🔒 Security & Best Practices

### 1. Checksum Validation

Ogni package viene validato con SHA256:

```php
// GitHubReleaseService
public function validateChecksum(string $filePath, string $expectedChecksum): bool
{
    $actualChecksum = hash_file('sha256', $filePath);
    return hash_equals($expectedChecksum, $actualChecksum);
}
```

### 2. Path Traversal Protection

```php
// UpdateApplicationService - exclude paths durante copy
$excludePaths = [
    'storage',
    'vendor',
    'node_modules',
    '.git',
    '.env',
    'bootstrap/cache',
];
```

### 3. Transactional Rollback

```php
// On failure: automatic rollback
private function rollback(string $backupPath, string $destination): void
{
    $this->copyDirectory($backupPath, $destination);
    Artisan::call('migrate:rollback', ['--force' => true]);
    Artisan::call('config:clear');
}
```

### 4. Health Checks

5 check obbligatori post-deployment:

1. **Database**: Connection + critical tables
2. **Cache**: Redis/Memcached connectivity
3. **Storage**: Disk space + write permissions
4. **Queue**: Laravel Horizon + Redis
5. **Critical Tables**: Row count verification

### 5. Rate Limiting

GitHub API limits:
- **Unauthenticated**: 60 req/hour
- **Authenticated**: 5000 req/hour (Replit connector)

**Raccomandazione**: Check settimanale è ampiamente sotto limite.

### 6. Access Control

API endpoints protetti da:
- `ApiKeyAuth` middleware
- Optional: RBAC (solo admin possono approve/apply)

### 7. Backup Strategy

```
storage/backups/
├── 1.2.0-1729504800/    # Version + timestamp
│   ├── app/
│   ├── config/
│   ├── database/migrations/
│   ├── routes/
│   └── backup-info.json
```

**Retention**: Configurabile (default: ultimi 10 backup)

---

## 📊 Monitoring & Logging

Tutti gli eventi vengono loggati in `storage/logs/laravel.log`:

```
[2025-10-21 03:15:00] INFO: Starting update application {"version":"1.2.0"}
[2025-10-21 03:15:05] INFO: Backup created {"path":"/storage/backups/..."}
[2025-10-21 03:15:10] INFO: Files copied successfully
[2025-10-21 03:15:15] INFO: Migrations completed {"count":3}
[2025-10-21 03:17:20] INFO: Update applied successfully {"version":"1.2.0"}
```

---

## 🆘 Troubleshooting

### Update Check Fails

```bash
# Verifica connessione GitHub
curl -I https://api.github.com/repos/{owner}/{repo}/releases/latest

# Verifica token Replit
echo $REPL_IDENTITY  # deve essere presente
```

### Download Fails

```bash
# Verifica spazio disco
df -h storage/app/

# Verifica permessi
ls -la storage/app/
```

### Checksum Mismatch

```bash
# Ricalcola checksum locale
sha256sum storage/app/updates/{version}/acs-*.tar.gz

# Confronta con GitHub release checksums.txt
```

### Apply Fails

```bash
# Check backup esistente
ls -la storage/backups/

# Check logs dettagliati
tail -f storage/logs/laravel.log

# Rollback manuale se necessario
cp -r storage/backups/{version}-{timestamp}/* .
php artisan migrate:rollback --force
```

---

## 📝 Changelog System

Ogni release su GitHub deve includere:

```markdown
## [1.2.0] - 2025-10-21

### Added
- Feature A per migliorare X
- Feature B per supportare Y

### Changed
- Modificato comportamento di Z

### Fixed
- Bug #123: Fix crash su condizione ABC
- Bug #124: Risolto memory leak

### Security
- Aggiornato dipendenza X alla v2.0 (CVE-2024-XXXX)

### Breaking Changes
- NONE (o dettagliare se presenti)
```

Questo changelog viene estratto e salvato in `system_versions.changelog` per visibilità admin.

---

## 🚀 Esempio Completo: Pubblicare Release

### Step 1: Preparare Package

```bash
cd ~/acs-project
git checkout main
git pull

# Crea directory release
mkdir -p release-package/acs-v1.2.0

# Copia file necessari
cp -r app config database/migrations routes resources public \
      release-package/acs-v1.2.0/

# Rimuovi file non necessari
rm -rf release-package/acs-v1.2.0/storage/*
rm -rf release-package/acs-v1.2.0/.git

# Crea .release-info.json
cat > release-package/acs-v1.2.0/.release-info.json <<EOF
{
  "version": "1.2.0",
  "release_date": "$(date -I)",
  "requires_migration": true
}
EOF

# Crea package
cd release-package
tar -czf acs-v1.2.0.tar.gz acs-v1.2.0/

# Genera checksum
sha256sum acs-v1.2.0.tar.gz > checksums.txt
```

### Step 2: Creare GitHub Release

```bash
# Via GitHub CLI (gh)
gh release create v1.2.0 \
  --title "ACS v1.2.0 - Feature X" \
  --notes-file CHANGELOG.md \
  acs-v1.2.0.tar.gz \
  checksums.txt

# Oppure via Web UI
# 1. Vai su https://github.com/{org}/{repo}/releases/new
# 2. Tag: v1.2.0
# 3. Title: ACS v1.2.0 - Feature X
# 4. Description: Paste CHANGELOG.md
# 5. Upload: acs-v1.2.0.tar.gz + checksums.txt
# 6. Publish release
```

### Step 3: Verificare Auto-Check

```bash
# Wait for next Monday 03:00 automatic check
# Oppure trigger manuale:
php artisan system:check-updates --auto-stage
```

### Step 4: Approve & Apply

```bash
# Via API o Dashboard
curl -X POST -H "X-API-KEY: xxx" \
  https://acs.example.com/api/v1/system/updates/42/approve

curl -X POST -H "X-API-KEY: xxx" \
  https://acs.example.com/api/v1/system/updates/42/apply
```

---

## ✅ Sistema Production-Ready

Il sistema è completo e include:

- ✅ Database migrations con environment-awareness
- ✅ GitHub API integration con Replit connector
- ✅ Download & staging automatico con validazione SHA256
- ✅ Scheduled weekly check (lunedì 03:00)
- ✅ Manual approval workflow (approve/reject/schedule)
- ✅ Transactional deployment con backup automatico
- ✅ Health checks post-deployment (5-stage)
- ✅ Auto-rollback on failure
- ✅ Complete REST API (6 endpoints)
- ✅ CLI command con progress bar
- ✅ Audit trail completo in database
- ✅ Multi-environment support (local/staging/production)

**Ready to deploy!** 🚀
