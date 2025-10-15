# MikroTik XMPP Test Configuration - TR-369 USP

## Configurazione Test MikroTik in Laboratorio

### 📋 Informazioni Server XMPP

| Parametro | Valore |
|-----------|--------|
| **Server XMPP** | `af033280-2a00-40b8-aa0d-5550f59fd4d2-00-6y2330nmtgv4.janeway.replit.dev` |
| **Porta** | `6000` |
| **Dominio** | `acs.local` |
| **JID ACS** | `acs-server@acs.local` |
| **JID CPE MikroTik** | `mikrotik-lab@acs.local` |
| **Password CPE** | `MikroTik2025!` |
| **Protocollo** | XMPP (non TLS per test) |

---

## 🔧 Configurazione MikroTik RouterOS

### Opzione 1: Via WebFig/WinBox GUI

**NOTA**: MikroTik RouterOS non supporta nativamente XMPP per TR-369 USP. RouterOS supporta:
- TR-069 CWMP (nativo)
- TR-369 USP via **STOMP** o **MQTT** (non XMPP)

Per testare XMPP, puoi:
1. Usare un client XMPP esterno sul MikroTik (Pidgin, Profanity)
2. Configurare TR-369 USP con MQTT (supportato nativamente)

### Opzione 2: Test XMPP con Client Esterno

Se vuoi testare la connettività XMPP dal tuo lab:

```bash
# Da un PC nel tuo lab, installa un client XMPP
# Esempio: Profanity (Linux)
apt-get install profanity

# Connetti al server
profanity
/connect mikrotik-lab@acs.local server af033280-2a00-40b8-aa0d-5550f59fd4d2-00-6y2330nmtgv4.janeway.replit.dev port 6000

# Invia messaggio di test all'ACS
/msg acs-server@acs.local Hello from MikroTik Lab!
```

### Opzione 3: Configurazione TR-069 CWMP (Supportato Nativamente)

MikroTik RouterOS supporta **TR-069 CWMP** out-of-the-box:

```routeros
# Via RouterOS CLI
/tr069-client
set enabled=yes
set acs-url=https://af033280-2a00-40b8-aa0d-5550f59fd4d2-00-6y2330nmtgv4.janeway.replit.dev:5000/tr069
set username=admin
set password=admin123
set periodic-inform-enabled=yes
set periodic-inform-interval=00:01:00

# Verifica connessione
/tr069-client print
/tr069-client inform
```

---

## 🧪 Test Connettività XMPP (da Server ACS)

### Test 1: Ping XMPP Server

```bash
# SSH nel server ACS (Replit Shell)
telnet af033280-2a00-40b8-aa0d-5550f59fd4d2-00-6y2330nmtgv4.janeway.replit.dev 6000
```

Output atteso:
```xml
<?xml version='1.0'?>
<stream:stream xmlns='jabber:client'...>
```

### Test 2: Invia Messaggio USP via PHP

Crea test script in `/tmp/test_xmpp_mikrotik.php`:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Services\XmppClientService;
use App\Services\UspXmppTransport;

// Connetti al server XMPP
$xmppClient = new XmppClientService();
$uspTransport = new UspXmppTransport($xmppClient);

// Invia messaggio di test al MikroTik
$deviceSerial = 'mikrotik-lab';
$testMessage = base64_encode('USP Test Message from ACS');

if ($uspTransport->sendUspMessage($deviceSerial, $testMessage)) {
    echo "✅ Messaggio USP inviato a mikrotik-lab@acs.local\n";
} else {
    echo "❌ Errore invio messaggio\n";
}

// Ascolta risposte (30 secondi)
echo "Listening for responses...\n";
$uspTransport->receiveUspMessages(function($message, $stanza) {
    echo "📨 Messaggio ricevuto:\n";
    echo "Payload: " . substr($message, 0, 100) . "...\n";
}, 30);
```

Esegui:
```bash
cd /home/runner/workspace
php /tmp/test_xmpp_mikrotik.php
```

---

## 📊 Verifica Stato Server XMPP

### Controlla Prosody Logs

```bash
# Nel server ACS
tail -f prosody.log
```

Cerca:
```
c2sin... Authenticated as mikrotik-lab@acs.local
```

### Test Autenticazione XMPP

```bash
# Test con openssl s_client
openssl s_client -connect af033280-2a00-40b8-aa0d-5550f59fd4d2-00-6y2330nmtgv4.janeway.replit.dev:6000 -starttls xmpp
```

---

## 🚨 Limitazioni Attuali

1. **MikroTik non supporta XMPP per TR-369 USP**
   - RouterOS 7.x supporta TR-369 USP solo via **MQTT** o **STOMP**
   - Per XMPP serve client custom o firmware modificato

2. **TLS Disabilitato (Development)**
   - Connessione in chiaro (non sicura)
   - Per production: abilitare TLS in `prosody.cfg.lua`

3. **Server Locale (Replit)**
   - Accessibile solo se Replit proxy è attivo
   - Per lab permanente: deploy su server dedicato

---

## 🔄 Alternativa: Configurazione TR-369 USP via MQTT

MikroTik RouterOS 7.x supporta nativamente TR-369 USP via **MQTT**:

```routeros
# Configura USP Controller via MQTT
/iot mqtt brokers
add name=usp-acs address=af033280-2a00-40b8-aa0d-5550f59fd4d2-00-6y2330nmtgv4.janeway.replit.dev port=1883

/iot usp
set enabled=yes controller-endpoint=mqtt://usp-acs
```

**Nota**: L'ACS attuale supporta già **MQTT per TR-369 USP** (`php-mqtt/laravel-client`).

---

## 📞 Prossimi Passi

**Per test XMPP reale con MikroTik**:
1. **Usa TR-069 CWMP** (supportato nativamente) - configurazione sopra
2. **Oppure usa TR-369 USP via MQTT** (supportato nativamente)
3. **Oppure sviluppa script custom** sul MikroTik che implementi client XMPP

**Per test immediato**:
- Usa il test script PHP sopra per simulare device
- Verifica connettività con `telnet` o `openssl`
- Controlla logs Prosody per autenticazione

---

## 🔗 Risorse

- [MikroTik TR-069 Documentation](https://help.mikrotik.com/docs/display/ROS/TR-069)
- [MikroTik IoT Package (MQTT/USP)](https://help.mikrotik.com/docs/display/ROS/IoT)
- [BBF TR-369 USP Specification](https://usp.technology/)
- Prosody Server: `prosody.cfg.lua`
- XMPP Test Script: `/tmp/test_xmpp_mikrotik.php`
