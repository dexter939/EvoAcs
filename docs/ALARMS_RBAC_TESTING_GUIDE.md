# Guida Testing RBAC - Sistema Alarms & Monitoring

**Versione**: 1.0  
**Data**: Ottobre 2025  
**Target**: Testing & QA Team  

---

## 🎯 Obiettivo Testing

Validare che il sistema Alarms & Monitoring implementi correttamente:
- ✅ RBAC enforcement (Role-Based Access Control)
- ✅ SSE real-time stream con carrier-grade reliability
- ✅ Bulk operations con security logging
- ✅ Dashboard UI responsiva con filtri e statistiche

---

## 📋 Pre-requisiti

### 1. Database Setup
```bash
# Verifica allarmi di test presenti
php artisan tinker
>>> Alarm::count();  # Deve essere > 0 (6 allarmi test creati)
```

### 2. Utenti Test Disponibili

**Administrator** (Full Access):
```
Email: admin@acs.local
Password: password
Permissions: alarms.view + alarms.manage
```

**Viewer** (Read-Only) - DA CREARE:
```bash
# Crea utente Viewer tramite Users Management UI
Email: viewer@acs.local
Role: Viewer (solo alarms.view)
```

**Support** (No Access) - DA CREARE:
```bash
# Crea utente Support tramite Users Management UI
Email: support@acs.local
Role: Support (nessun permesso alarms)
```

---

## 🧪 Test Case 1: Administrator (Full Access)

### Setup
- Login: `admin@acs.local` / `password`

### Test Steps

#### 1.1 Dashboard Access ✅
```
1. Login come Administrator
2. Navigate: Menu → Alarms & Monitoring
3. EXPECTED: Dashboard allarmi caricata correttamente
4. VERIFY: 
   - Statistics cards mostrano conteggi corretti
   - DataTable mostra 6 allarmi test
   - Filtri severity/category/status funzionanti
```

#### 1.2 SSE Real-Time Stream ✅
```
1. Apri DevTools → Console
2. VERIFY Console Output:
   ✓ "SSE Connection established"
   ✓ "SSE Connected: true"
   ✓ Heartbeat ping ogni 30 secondi
3. VERIFY Network Tab:
   ✓ Request a /acs/alarms/stream
   ✓ Status: 200 OK
   ✓ Type: text/event-stream
   ✓ Connection: keep-alive
```

#### 1.3 Single Acknowledge ✅
```
1. Click pulsante "Acknowledge" su allarme "Device Offline"
2. EXPECTED:
   ✓ Alert success: "Alarm acknowledged successfully"
   ✓ Row allarme aggiornata (badge "Acknowledged")
   ✓ Statistics cards aggiornate
3. VERIFY Database:
   SELECT acknowledged_by, acknowledged_at 
   FROM alarms WHERE id = <alarm_id>;
   # Deve mostrare user_id = 1 (admin) e timestamp
```

#### 1.4 Single Clear ✅
```
1. Click pulsante "Clear" su allarme "Configuration Sync Failed"
2. EXPECTED:
   ✓ Alert success: "Alarm cleared successfully"
   ✓ Row allarme rimossa dalla vista (filter default: active)
   ✓ Statistics cards aggiornate
3. VERIFY Database:
   SELECT status, cleared_at 
   FROM alarms WHERE id = <alarm_id>;
   # Deve mostrare status = 'cleared' e timestamp
```

#### 1.5 Bulk Acknowledge ✅
```
1. Seleziona checkbox su 2-3 allarmi
2. Click "Bulk Acknowledge"
3. EXPECTED:
   ✓ Confirm modal appare
   ✓ Click "Confirm" → Success message
   ✓ Tutti allarmi selezionati acknowledged
   ✓ Statistics cards aggiornate
```

#### 1.6 Bulk Clear ✅
```
1. Seleziona checkbox su 2-3 allarmi
2. Click "Bulk Clear"
3. EXPECTED:
   ✓ Confirm modal appare
   ✓ Click "Confirm" → Success message
   ✓ Tutti allarmi selezionati cleared (rimossi da vista)
```

#### 1.7 Security Logging ✅
```
# Verifica che tutte le azioni siano logate
SELECT action, user_id, metadata, created_at
FROM security_logs
WHERE action LIKE 'alarm_%'
ORDER BY created_at DESC
LIMIT 10;

EXPECTED Entries:
- alarm_acknowledged (user_id: 1)
- alarm_cleared (user_id: 1)
- alarm_bulk_acknowledged (user_id: 1)
- alarm_bulk_cleared (user_id: 1)
```

---

## 🧪 Test Case 2: Viewer Role (Read-Only)

### Setup
```
1. Users Management → Create User
   Email: viewer@acs.local
   Password: password
   Role: Viewer

2. Roles Management → Edit "Viewer"
   Permissions: ✓ alarms.view (NO alarms.manage)
```

### Test Steps

#### 2.1 Dashboard Access ✅
```
1. Logout admin
2. Login: viewer@acs.local / password
3. Navigate: Menu → Alarms & Monitoring
4. EXPECTED: Dashboard allarmi accessibile
5. VERIFY:
   ✓ Statistics cards visibili
   ✓ DataTable allarmi visibile
   ✓ Filtri funzionanti
   ✓ SSE stream connesso (Console: "SSE Connected: true")
```

#### 2.2 Acknowledge DENIED ❌
```
1. Click pulsante "Acknowledge" su qualsiasi allarme
2. EXPECTED:
   ✗ HTTP 403 Forbidden
   ✗ Alert error: "You do not have permission to perform this action"
3. VERIFY Console/Network:
   Status: 403 Forbidden
   Response: {"error": "Forbidden", "message": "..."}
```

#### 2.3 Clear DENIED ❌
```
1. Click pulsante "Clear" su qualsiasi allarme
2. EXPECTED:
   ✗ HTTP 403 Forbidden
   ✗ Alert error: "You do not have permission..."
```

#### 2.4 Bulk Operations DENIED ❌
```
1. Seleziona checkbox allarmi
2. Click "Bulk Acknowledge" o "Bulk Clear"
3. EXPECTED:
   ✗ HTTP 403 Forbidden per ogni richiesta
```

#### 2.5 Security Log Unauthorized Access ✅
```
SELECT action, severity, user_id, metadata
FROM security_logs
WHERE action = 'unauthorized_access'
  AND metadata->>'path' LIKE '%alarm%'
ORDER BY created_at DESC;

EXPECTED:
- Entries con severity = 'critical'
- Metadata contiene user_id del Viewer
- Reason: "User lacks permission: alarms.manage"
```

---

## 🧪 Test Case 3: Support Role (No Access)

### Setup
```
1. Users Management → Create User
   Email: support@acs.local
   Password: password
   Role: Support (NO alarms permissions)
```

### Test Steps

#### 3.1 Dashboard Access DENIED ❌
```
1. Logout
2. Login: support@acs.local / password
3. Navigate: /acs/alarms (manual URL o menu)
4. EXPECTED:
   ✗ HTTP 403 Forbidden
   ✗ Browser mostra: "403 | You do not have permission to perform this action"
   ✗ Redirect a dashboard principale o error page
```

#### 3.2 Stats Endpoint DENIED ❌
```
1. Tentativo accesso diretto: /acs/alarms/stats
2. EXPECTED:
   ✗ HTTP 403 Forbidden
```

#### 3.3 SSE Stream DENIED ❌
```
1. Tentativo accesso diretto: /acs/alarms/stream
2. EXPECTED:
   ✗ HTTP 403 Forbidden
   ✗ SSE connection NON stabilita
```

#### 3.4 Security Logging ✅
```
SELECT action, severity, metadata
FROM security_logs
WHERE user_id = <support_user_id>
  AND action = 'unauthorized_access'
ORDER BY created_at DESC;

EXPECTED:
- Multiple entries per tutti i tentativi di accesso
- Severity: critical
- Metadata: {"path": "/acs/alarms", "reason": "User lacks permission: alarms.view"}
```

---

## 🔍 Test Case 4: SSE Stream Reliability

### Test Steps

#### 4.1 Heartbeat Mechanism ✅
```
1. Login come Administrator
2. Dashboard Alarms → Apri DevTools Console
3. Wait 60 seconds
4. VERIFY Console Output:
   ✓ "SSE Heartbeat ping" ogni ~30 secondi
   ✓ Connection mantiene alive (no disconnect)
```

#### 4.2 Auto-Reconnect ✅
```
1. Dashboard Alarms aperta
2. Server Restart: php artisan serve (restart workflow)
3. Wait 5-10 seconds
4. VERIFY Console:
   ✓ "SSE Connection lost, attempting to reconnect..."
   ✓ "SSE Reconnecting (attempt 1)..."
   ✓ "SSE Connection established" (dopo restart)
   ✓ Dashboard continua a funzionare normalmente
```

#### 4.3 Multi-Client Support ✅
```
1. Apri 3 browser windows/tabs
2. Login come Administrator in tutte
3. Navigate to Alarms Dashboard in tutte
4. VERIFY:
   ✓ Tutte le tab connesse a SSE stream
   ✓ Console mostra "SSE Connected: true" in tutte
5. Tab 1: Acknowledge un allarme
6. VERIFY Tabs 2-3:
   ✓ Ricevono evento SSE real-time
   ✓ Row allarme aggiornata automaticamente
   ✓ Statistics cards aggiornate
```

#### 4.4 Network Resilience ✅
```
1. Dashboard aperta con SSE attivo
2. DevTools → Network Tab → Throttling: "Slow 3G"
3. Wait 60 seconds
4. VERIFY:
   ✓ Connection mantiene alive (heartbeat continua)
   ✓ No errori in console
5. Remove Throttling
6. VERIFY:
   ✓ Stream continua normalmente
```

---

## 📊 Test Case 5: Performance & Scalability

### 5.1 Large Dataset ✅
```
# Crea 100 allarmi di test
php artisan tinker
>>> factory(Alarm::class, 100)->create();

1. Dashboard Alarms → Load
2. VERIFY:
   ✓ Page load < 2 seconds
   ✓ DataTable rendering performant
   ✓ Filtri responsive
   ✓ Statistics calculation rapida
```

### 5.2 Bulk Operations Scale ✅
```
1. Seleziona 50 allarmi
2. Bulk Acknowledge
3. VERIFY:
   ✓ Operation completa < 5 secondi
   ✓ UI mostra progress indicator
   ✓ Success message con count corretto
   ✓ Tutte rows aggiornate
```

### 5.3 SSE Load Test ✅
```
# Apri 10 browser tabs simultanee
1. Login in tutte (Administrator)
2. Navigate to Alarms in tutte
3. VERIFY:
   ✓ Tutte connesse a SSE (10 connessioni)
   ✓ Server CPU/Memory stabile
   ✓ No memory leaks
4. Tab 1: Create new alarm (simulate)
5. VERIFY Tabs 2-10:
   ✓ Tutte ricevono notifica real-time
   ✓ No lag o delay significativo (<1s)
```

---

## 🛡️ Test Case 6: Security Validation

### 6.1 CSRF Protection ✅
```
1. Dashboard Alarms aperta
2. DevTools → Console → Execute:
   fetch('/acs/alarms/1/acknowledge', {method: 'POST'})
3. EXPECTED:
   ✗ 419 CSRF Token Mismatch (se no CSRF token)
   # Laravel CSRF protection attiva
```

### 6.2 Direct API Access ❌
```
1. Logout
2. curl -X POST http://localhost:5000/acs/alarms/1/acknowledge
3. EXPECTED:
   ✗ HTTP 401 Unauthorized (redirect to login)
```

### 6.3 Permission Tampering ❌
```
1. Login come Viewer
2. DevTools → Application → Cookies
3. Modify cookie (attempt role escalation)
4. Try to acknowledge alarm
5. EXPECTED:
   ✗ HTTP 403 Forbidden
   ✗ Server-side validation previene escalation
   ✗ Security log registra tentativo
```

### 6.4 SQL Injection Protection ✅
```
1. Dashboard Alarms → Search/Filter
2. Input: ' OR 1=1--
3. EXPECTED:
   ✓ No SQL errors
   ✓ Laravel Eloquent protegge da injection
   ✓ Results filtrati correttamente (escaped)
```

---

## 📈 Success Criteria

### ✅ PASS Conditions
- [ ] Administrator: Full access (view + manage)
- [ ] Viewer: Read-only (view only, manage denied 403)
- [ ] Support: No access (all endpoints 403)
- [ ] SSE stream: Connected, heartbeat, auto-reconnect
- [ ] Bulk operations: Funzionanti con progress feedback
- [ ] Security logs: Tutte azioni registrate
- [ ] Performance: Page load < 2s, bulk ops < 5s
- [ ] Multi-client SSE: 10+ tabs simultanee senza lag

### ❌ FAIL Conditions
- Permission bypass (utente senza permesso accede)
- SSE disconnects senza auto-reconnect
- Bulk operations timeout o errori
- Security logs mancanti
- Performance degradation (>5s load time)
- Memory leaks con SSE connections

---

## 🔧 Debugging Tools

### Browser Console Checks
```javascript
// Verifica SSE connection status
window.sseConnected  // true/false

// Monitora eventi SSE
// Console mostrerà tutti gli eventi incoming
```

### Database Queries
```sql
-- Allarmi attivi
SELECT COUNT(*) FROM alarms WHERE status = 'active';

-- User permissions
SELECT u.email, r.name, p.slug
FROM users u
JOIN user_role ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
JOIN role_permission rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE u.email = 'viewer@acs.local';

-- Security logs recenti
SELECT action, severity, metadata->>'alarm_id' as alarm_id, created_at
FROM security_logs
WHERE action LIKE 'alarm%'
ORDER BY created_at DESC
LIMIT 20;
```

### Server Logs
```bash
# Monitora log Laravel real-time
tail -f storage/logs/laravel.log

# Grep errori RBAC
grep -i "permission\|forbidden\|unauthorized" storage/logs/laravel.log

# SSE connections
grep -i "sse\|stream" storage/logs/laravel.log
```

---

## 📞 Report Issues

Se un test fallisce:

1. **Screenshot** del problema
2. **Browser Console** output (copy full log)
3. **Network Tab** request/response (HAR export)
4. **Database State** (query results)
5. **Security Logs** (related entries)

Invia a: QA Team / DevOps

---

## ✅ Sign-Off Checklist

Dopo aver completato tutti i test:

- [ ] Test Case 1: Administrator (7/7 passed)
- [ ] Test Case 2: Viewer (5/5 passed)
- [ ] Test Case 3: Support (4/4 passed)
- [ ] Test Case 4: SSE Reliability (4/4 passed)
- [ ] Test Case 5: Performance (3/3 passed)
- [ ] Test Case 6: Security (4/4 passed)
- [ ] Tutti security logs verificati
- [ ] No memory leaks o performance issues
- [ ] Documentation aggiornata

**Tester Name**: _____________  
**Date**: _____________  
**Signature**: _____________  

**Status**: ☐ PASS  ☐ FAIL  ☐ PARTIAL  

---

**Ultimo Aggiornamento**: Ottobre 2025  
**Versione**: 1.0  
**Sistema**: ACS Carrier-Grade v11.0
