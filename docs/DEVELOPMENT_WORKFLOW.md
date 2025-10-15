# Workflow Sviluppo: Replit ↔ Produzione ↔ Dispositivi Reali

## 🎯 Panoramica

Questo documento spiega come gestire il ciclo completo:
1. **Sviluppo su Replit** (test, modifiche)
2. **Deploy su Server Produzione** (tua macchina Linux)
3. **Test con Dispositivi Reali** (MikroTik, router, etc.)

---

## 📋 Configurazione Iniziale (Una Volta Sola)

### 1. Installa ACS su Server Produzione

```bash
# Sulla tua macchina Linux
wget https://YOUR_REPLIT_URL/deploy/install.sh
sudo bash install.sh
```

### 2. Setup SSH Key per Sync Automatico

**Su Replit:**
```bash
# Genera SSH key
ssh-keygen -t rsa -b 4096 -f ~/.ssh/id_rsa -N ""

# Mostra public key
cat ~/.ssh/id_rsa.pub
```

**Sulla tua macchina Linux:**
```bash
# Aggiungi la public key
nano ~/.ssh/authorized_keys
# Incolla la chiave pubblica generata su Replit
```

### 3. Test Connessione

**Su Replit:**
```bash
ssh root@YOUR_SERVER_IP "echo 'SSH OK'"
```

✅ Se vedi "SSH OK", sei pronto!

---

## 🔄 Workflow Quotidiano

### Scenario A: Modifiche Rapide (No Database Changes)

```bash
# 1. Fai modifiche su Replit
#    (edit files, test locally)

# 2. Sync a produzione
./deploy/sync-to-production.sh YOUR_SERVER_IP

# 3. Test con device reale
./deploy/test-remote-device.sh YOUR_SERVER_IP 192.168.1.100
```

**Tempo totale**: ~2 minuti

---

### Scenario B: Modifiche Database (Migrations)

```bash
# 1. Crea migration su Replit
php artisan make:migration add_xmpp_support

# 2. Testa localmente
php artisan migrate

# 3. Sync a produzione (include migrations)
./deploy/sync-to-production.sh YOUR_SERVER_IP

# 4. Verifica produzione
ssh root@YOUR_SERVER_IP "cd /opt/acs/app && php artisan migrate:status"
```

---

### Scenario C: Deploy Automatico via Git

**Setup (una volta):**
```bash
./deploy/setup-git-push-deploy.sh YOUR_SERVER_IP
```

**Uso quotidiano:**
```bash
# 1. Commit su Replit
git add .
git commit -m "Add XMPP transport for TR-369"
git push origin main

# 2. GitHub Actions deploya automaticamente a produzione
#    (wait ~2 minutes)

# 3. Verifica deploy
ssh root@YOUR_SERVER_IP "systemctl status acs-http"
```

---

## 🧪 Testing con Dispositivi Reali

### Test 1: MikroTik Router (TR-069)

**1. Configura MikroTik**

Sul tuo MikroTik in laboratorio:
```routeros
/tr069-client
set enabled=yes
set acs-url=https://YOUR_SERVER_IP/tr069
set username=admin
set password=admin123
set periodic-inform-enabled=yes
set periodic-inform-interval=00:01:00

# Forza connessione immediata
/tr069-client inform
```

**2. Monitora Connessione su Replit**

```bash
# Terminal 1: Laravel logs
ssh root@YOUR_SERVER_IP 'tail -f /opt/acs/app/storage/logs/laravel.log | grep TR069'

# Terminal 2: Check dashboard
# Apri browser: http://YOUR_SERVER_IP/acs/devices
```

**3. Verifica Device Registrato**

```bash
ssh root@YOUR_SERVER_IP "cd /opt/acs/app && php artisan tinker"
```

In Tinker:
```php
// Lista tutti i devices
\App\Models\Device::all();

// Cerca device per serial number
\App\Models\Device::where('serial_number', 'TEST-SN-12345')->first();

// Ultimo Inform ricevuto
\App\Models\Device::orderBy('last_inform', 'desc')->first();
```

---

### Test 2: XMPP Transport

**1. Test Script Simulazione**

Su Replit:
```bash
php test_xmpp_mikrotik.php
```

**2. Monitor Prosody su Produzione**

```bash
ssh root@YOUR_SERVER_IP 'tail -f /var/log/prosody/prosody.log'
```

**3. Test con Client XMPP Reale**

Se hai client XMPP (Pidgin, Profanity):
```
Account: mikrotik-lab@acs.local
Password: MikroTik2025!
Server: YOUR_SERVER_IP:6000
```

---

### Test 3: NAT Traversal & Pending Commands

**Scenario**: Device dietro NAT non raggiungibile via Connection Request

**1. Simula NAT Device**

Su Replit:
```bash
# Crea device di test
php artisan tinker
```

```php
$device = \App\Models\Device::create([
    'serial_number' => 'NAT-TEST-001',
    'oui' => '00:0C:42',
    'product_class' => 'MikroTik-RB4011',
    'manufacturer' => 'MikroTik',
    'model' => 'RB4011',
    'ip_address' => '192.168.1.100',
    'connection_request_url' => 'http://192.168.1.100:7547',
]);
```

**2. Invia Comando (Verrà Accodato)**

```bash
curl -X POST http://YOUR_SERVER_IP/acs/devices/$DEVICE_ID/reboot
```

**3. Monitor Pending Commands**

Apri dashboard: `http://YOUR_SERVER_IP/acs/devices/$DEVICE_ID`

Vedrai "Pending Commands (NAT Traversal)" card con comando in coda.

**4. Simula Periodic Inform**

Quando il device fa Periodic Inform, il comando verrà eseguito automaticamente.

---

## 📊 Monitoring & Debugging

### Logs in Tempo Reale

**Tutti i logs insieme:**
```bash
ssh root@YOUR_SERVER_IP << 'EOF'
    tmux new-session -d -s acs-logs
    tmux split-window -h
    tmux split-window -v
    tmux select-pane -t 0
    tmux send-keys "tail -f /opt/acs/app/storage/logs/laravel.log" C-m
    tmux select-pane -t 1
    tmux send-keys "tail -f /var/log/nginx/access.log" C-m
    tmux select-pane -t 2
    tmux send-keys "tail -f /var/log/prosody/prosody.log" C-m
    tmux attach-session -t acs-logs
EOF
```

### Dashboard Metrics

Apri in browser:
- **Devices**: `http://YOUR_SERVER_IP/acs/devices`
- **Dashboard**: `http://YOUR_SERVER_IP/acs/dashboard`
- **Data Models**: `http://YOUR_SERVER_IP/acs/data-models`

### Health Check

```bash
ssh root@YOUR_SERVER_IP << 'EOF'
    echo "=== ACS Health Check ==="
    echo ""
    echo "Services:"
    systemctl is-active acs-http postgresql redis nginx prosody
    echo ""
    echo "Queue Workers:"
    supervisorctl status | grep acs-worker
    echo ""
    echo "Database:"
    psql -U acs_user -d acs_production -c "SELECT COUNT(*) FROM devices;"
    echo ""
    echo "Disk:"
    df -h /opt/acs
EOF
```

---

## 🚀 Script di Utilità

### Script Disponibili

| Script | Descrizione | Uso |
|--------|-------------|-----|
| `deploy/install.sh` | Installazione completa | `sudo ./deploy/install.sh` |
| `deploy/sync-to-production.sh` | Sync Replit → Produzione | `./deploy/sync-to-production.sh IP` |
| `deploy/setup-git-push-deploy.sh` | Setup auto-deploy GitHub | `./deploy/setup-git-push-deploy.sh IP REPO` |
| `deploy/test-remote-device.sh` | Test device reale | `./deploy/test-remote-device.sh IP DEVICE_IP` |
| `test_xmpp_mikrotik.php` | Test XMPP transport | `php test_xmpp_mikrotik.php` |

### Alias Utili

Aggiungi a `~/.bashrc` su Replit:

```bash
# ACS Development Shortcuts
alias acs-sync='./deploy/sync-to-production.sh YOUR_SERVER_IP'
alias acs-logs='ssh root@YOUR_SERVER_IP "tail -f /opt/acs/app/storage/logs/laravel.log"'
alias acs-deploy='ssh root@YOUR_SERVER_IP "/opt/acs/update.sh"'
alias acs-status='ssh root@YOUR_SERVER_IP "systemctl status acs-http --no-pager"'
```

Ricarica:
```bash
source ~/.bashrc
```

Ora puoi usare:
```bash
acs-sync        # Sync code
acs-logs        # View logs
acs-deploy      # Force update
acs-status      # Check status
```

---

## 🐛 Troubleshooting Comuni

### Device Non Si Connette

**Checklist:**
```bash
# 1. Device può raggiungere server?
ping YOUR_SERVER_IP

# 2. TR-069 endpoint attivo?
curl http://YOUR_SERVER_IP/tr069

# 3. Firewall permette connessioni?
ssh root@YOUR_SERVER_IP "ufw status"

# 4. Logs mostrano tentativi?
ssh root@YOUR_SERVER_IP "grep 'Inform' /opt/acs/app/storage/logs/laravel.log"
```

### Sync Fallisce

**Checklist:**
```bash
# 1. SSH funziona?
ssh root@YOUR_SERVER_IP "echo OK"

# 2. Spazio disco disponibile?
ssh root@YOUR_SERVER_IP "df -h /opt/acs"

# 3. Permessi corretti?
ssh root@YOUR_SERVER_IP "ls -la /opt/acs/app"
```

### Services Non Rispondono

```bash
# Restart completo
ssh root@YOUR_SERVER_IP << 'EOF'
    systemctl restart acs-http
    supervisorctl restart acs-worker:*
    systemctl restart nginx
    systemctl restart redis
EOF
```

---

## 📈 Best Practices

### 1. Testa Sempre Localmente Prima

```bash
# Su Replit
php artisan test
php artisan migrate:fresh --seed
# Test manuale su http://replit-domain/acs/dashboard
```

### 2. Backup Prima di Deploy Grandi

```bash
ssh root@YOUR_SERVER_IP << 'EOF'
    cd /opt/acs
    sudo -u postgres pg_dump acs_production | gzip > backups/pre_deploy_$(date +%Y%m%d).sql.gz
EOF
```

### 3. Deploy in Orari di Bassa Attività

Evita deploy quando ci sono molti device connessi (picchi di traffico).

### 4. Monitor Post-Deploy

```bash
# Dopo ogni deploy, verifica per 5 minuti
watch -n 5 'ssh root@YOUR_SERVER_IP "systemctl status acs-http --no-pager | head -10"'
```

---

## 🔗 Risorse Aggiuntive

- Install Script: `deploy/install.sh`
- Sync Script: `deploy/sync-to-production.sh`
- Test Script: `deploy/test-remote-device.sh`
- XMPP Config: `docs/MIKROTIK_XMPP_TEST_CONFIG.md`
- Production Checklist: `docs/XMPP_PRODUCTION_CHECKLIST.md`
