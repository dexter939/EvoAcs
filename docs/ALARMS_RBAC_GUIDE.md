# Guida RBAC - Sistema Alarms & Monitoring

**Versione**: 1.0  
**Data**: Ottobre 2025  
**Per**: Team Operations  

---

## 📋 Panoramica

Il sistema Alarms & Monitoring è protetto con **RBAC (Role-Based Access Control)** carrier-grade che garantisce accesso granulare alle funzionalità di monitoraggio e gestione allarmi.

---

## 🔐 Permissions Disponibili

### 1. **alarms.view** (Lettura Allarmi)
**Descrizione**: Permette di visualizzare dashboard allarmi, statistiche e stream real-time.

**Consente accesso a**:
- ✅ Dashboard `/acs/alarms` - Visualizzazione lista allarmi
- ✅ Endpoint `/acs/alarms/stats` - Statistiche allarmi (totali, per severity, per categoria)
- ✅ Stream SSE `/acs/alarms/stream` - Notifiche real-time push

**NON consente**:
- ❌ Acknowledge allarmi
- ❌ Clear/risoluzione allarmi
- ❌ Bulk operations

**Ruoli predefiniti con questo permesso**:
- `Administrator` ✓
- `Manager` ✓
- `Operator` ✓
- `Viewer` ✓

---

### 2. **alarms.manage** (Gestione Allarmi)
**Descrizione**: Permette di gestire allarmi (acknowledge, clear, bulk operations).

**Consente accesso a**:
- ✅ Acknowledge singolo allarme `POST /acs/alarms/{id}/acknowledge`
- ✅ Clear singolo allarme `POST /acs/alarms/{id}/clear`
- ✅ Bulk acknowledge `POST /acs/alarms/bulk-acknowledge`
- ✅ Bulk clear `POST /acs/alarms/bulk-clear`

**Requisito**: Richiede anche `alarms.view` per visualizzare la dashboard.

**Ruoli predefiniti con questo permesso**:
- `Administrator` ✓
- `Manager` ✓
- `Operator` ✓

---

## 👥 Configurazione Ruoli Standard

### Administrator (Super Admin)
```
Permissions: ALL (incluso alarms.view, alarms.manage)
Accesso completo: ✓ View ✓ Manage ✓ Bulk Operations
```

### Manager
```
Permissions: alarms.view, alarms.manage, devices.*, provisioning.*, firmware.*
Accesso: ✓ View ✓ Manage ✓ Bulk Operations
```

### Operator
```
Permissions: alarms.view, alarms.manage, devices.view, provisioning.view
Accesso: ✓ View ✓ Manage ✓ Bulk Operations
```

### Viewer (Read-Only)
```
Permissions: alarms.view, devices.view, provisioning.view
Accesso: ✓ View (solo lettura)
NON può: ✗ Acknowledge ✗ Clear ✗ Bulk Operations
```

### Support
```
Permissions: nessun permesso alarms di default
Accesso: ✗ Dashboard allarmi NON accessibile
```

---

## 🛠️ Gestione Permissions

### Assegnare Permission a un Ruolo

1. **Via Web UI** (Consigliato):
   ```
   1. Login come Administrator
   2. Menu → Roles Management
   3. Click su "Edit" per il ruolo desiderato
   4. Seleziona checkbox "alarms.view" e/o "alarms.manage"
   5. Click "Save Changes"
   ```

2. **Via Database** (Solo sviluppatori):
   ```php
   $role = Role::where('name', 'Operator')->first();
   $permissions = ['alarms.view', 'alarms.manage'];
   $role->update(['permissions' => $permissions]);
   ```

### Assegnare Ruolo a un Utente

1. **Via Web UI**:
   ```
   1. Menu → Users Management
   2. Click "Edit" per l'utente desiderato
   3. Seleziona ruolo dal dropdown
   4. Click "Save Changes"
   ```

2. **Via Codice**:
   ```php
   $user = User::find($userId);
   $role = Role::where('name', 'Operator')->first();
   $user->role_id = $role->id;
   $user->save();
   ```

---

## 🔒 Comportamento Sistema RBAC

### Accesso Negato (403 Forbidden)

Quando un utente **senza permesso** tenta di accedere a un endpoint protetto:

**Browser**:
```
HTTP 403 Forbidden
Messaggio: "You do not have permission to perform this action."
```

**API/JSON**:
```json
{
  "error": "Forbidden",
  "message": "You do not have permission to perform this action."
}
```

**Security Log**:
```
[CRITICAL] Unauthorized Access Attempt
User ID: 123
Path: /acs/alarms
Reason: User lacks permission: alarms.view
IP: 192.168.1.100
Timestamp: 2025-10-20 05:46:24
```

### Accesso Consentito

Quando un utente **con permesso** accede:

**Security Log**:
```
[INFO] Alarm Acknowledged
User ID: 1 (admin@acs.local)
Alarm ID: 42
Previous Status: pending → acknowledged
IP: 192.168.1.100
Timestamp: 2025-10-20 05:46:24
```

---

## 📊 Monitoraggio Accessi

### Security Logs

Tutti gli accessi al sistema alarms sono tracciati nella tabella `security_logs`:

```sql
SELECT * FROM security_logs 
WHERE action LIKE 'alarm%' 
ORDER BY created_at DESC 
LIMIT 50;
```

**Campi utili**:
- `action`: Tipo azione (alarm_acknowledged, alarm_cleared, unauthorized_access)
- `severity`: critical, high, medium, low, info
- `user_id`: ID utente che ha eseguito l'azione
- `metadata`: JSON con dettagli (alarm_id, previous_status, new_status)
- `ip_address`: IP client
- `created_at`: Timestamp

---

## 🚨 SSE Real-Time Stream

### Requisiti Permission
Per ricevere **notifiche real-time** via Server-Sent Events (SSE):

```
Permission richiesta: alarms.view
Endpoint: /acs/alarms/stream
```

### Caratteristiche SSE Stream

**Carrier-Grade Features**:
- ✅ **Heartbeat**: Ping ogni 30 secondi per mantenere connessione alive
- ✅ **Auto-reconnect**: Reconnessione automatica con exponential backoff
- ✅ **RBAC Protection**: Solo utenti con `alarms.view` possono connettersi
- ✅ **Multi-client**: Supporta migliaia di connessioni SSE simultanee

**Eventi Inviati**:
```javascript
// Nuovo allarme
{
  type: "new",
  alarm: {
    id: 123,
    severity: "critical",
    category: "device",
    message: "Device offline",
    device_id: "CPE-001",
    status: "pending",
    created_at: "2025-10-20T05:46:24Z"
  }
}

// Allarme acknowledged
{
  type: "acknowledged",
  alarm_id: 123,
  acknowledged_by: "admin@acs.local"
}

// Allarme cleared
{
  type: "cleared",
  alarm_id: 123,
  cleared_by: "operator@acs.local"
}
```

---

## 🧪 Testing RBAC

### Test Case 1: Viewer Role (Solo Lettura)

**Setup**:
```
User: viewer@acs.local
Role: Viewer
Permissions: alarms.view (NO alarms.manage)
```

**Test**:
1. ✅ Login → Dashboard Alarms accessibile
2. ✅ Visualizza lista allarmi
3. ✅ Visualizza statistics cards
4. ✅ SSE stream funzionante (notifiche real-time)
5. ❌ Click "Acknowledge" → 403 Forbidden
6. ❌ Click "Clear" → 403 Forbidden
7. ❌ Bulk operations → 403 Forbidden

### Test Case 2: Operator Role (Gestione Completa)

**Setup**:
```
User: operator@acs.local
Role: Operator
Permissions: alarms.view + alarms.manage
```

**Test**:
1. ✅ Login → Dashboard Alarms accessibile
2. ✅ Visualizza lista allarmi
3. ✅ Click "Acknowledge" → Success (200 OK)
4. ✅ Click "Clear" → Success (200 OK)
5. ✅ Bulk acknowledge → Success
6. ✅ Bulk clear → Success
7. ✅ Security log registrato correttamente

### Test Case 3: Support Role (Accesso Negato)

**Setup**:
```
User: support@acs.local
Role: Support
Permissions: NESSUN permesso alarms
```

**Test**:
1. ✅ Login successful
2. ❌ Navigate to /acs/alarms → 403 Forbidden
3. ❌ Accesso SSE stream → 403 Forbidden
4. ✅ Security log: "Unauthorized Access Attempt"

---

## 🔧 Troubleshooting

### Problema: Utente non vede Dashboard Alarms

**Diagnostica**:
```sql
-- Verifica ruolo e permissions utente
SELECT u.email, r.name as role_name, r.permissions
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
WHERE u.email = 'user@acs.local';
```

**Soluzione**:
```
1. Verifica che l'utente abbia un ruolo assegnato
2. Verifica che il ruolo includa "alarms.view" nelle permissions
3. Se manca, aggiungi permission via Roles Management UI
```

### Problema: SSE Stream non riceve notifiche

**Diagnostica**:
```
1. Apri DevTools → Network
2. Verifica connessione a /acs/alarms/stream
3. Status deve essere "200 OK" con "text/event-stream"
4. Console deve mostrare "SSE Connected: true"
```

**Possibili cause**:
- ❌ Permission `alarms.view` mancante → 403 Forbidden
- ❌ Firewall/proxy blocca SSE → Timeout
- ❌ Browser incompatibile → Usa browser moderno (Chrome, Firefox, Edge)

### Problema: Bulk Operations falliscono

**Diagnostica**:
```javascript
// Console Browser → Network → Response
{
  "error": "Forbidden",
  "message": "You do not have permission to perform this action."
}
```

**Soluzione**:
```
User necessita permission: alarms.manage
Aggiungi via Roles Management → Edit Role → Checkbox "alarms.manage"
```

---

## 📈 Best Practices

### 1. Principio Least Privilege
- Assegna **SOLO** le permissions necessarie per il ruolo
- Viewer role per utenti che devono solo monitorare
- Operator role per team NOC/operations
- Administrator solo per senior staff

### 2. Audit Regolari
```sql
-- Review security logs settimanalmente
SELECT action, COUNT(*) as count, severity
FROM security_logs
WHERE created_at > NOW() - INTERVAL '7 days'
  AND action LIKE 'alarm%'
GROUP BY action, severity
ORDER BY count DESC;
```

### 3. Rotazione Credenziali
- Cambia password ogni 90 giorni
- Usa password complesse (min 12 caratteri)
- Abilita 2FA per Administrator roles

### 4. Monitoring SSE
```sql
-- Monitora connessioni SSE attive
-- (via application metrics/logging)
SELECT COUNT(DISTINCT user_id) as active_sse_connections
FROM application_metrics
WHERE metric_name = 'sse_alarm_stream_active'
  AND timestamp > NOW() - INTERVAL '5 minutes';
```

---

## 📞 Supporto

**Domande RBAC**: Team Security  
**Problemi SSE**: Team DevOps  
**Feature Requests**: Product Manager  

**Security Incident**: Contattare **IMMEDIATAMENTE** Security Team se:
- Tentativi accesso non autorizzato ripetuti
- Modifiche permissions non autorizzate
- Anomalie nei security logs

---

**Ultimo Aggiornamento**: Ottobre 2025  
**Versione Documento**: 1.0  
**Sistema**: ACS Carrier-Grade v11.0
