# XMPP Transport for TR-369 USP - Production Deployment Checklist

## Current Status: ✅ Basic PoC Implementation

This is a **Proof-of-Concept** implementation providing basic XMPP transport infrastructure. Before deploying to production with carrier-grade requirements (100,000+ devices), complete the following checklist.

---

## 🔒 Security Hardening (CRITICAL)

### 1. TLS Encryption
- [ ] Generate TLS certificates (Let's Encrypt recommended for production)
- [ ] Configure Prosody with certificates:
  ```lua
  ssl = {
      key = "/path/to/privkey.pem";
      certificate = "/path/to/fullchain.pem";
  }
  ```
- [ ] Enable mandatory encryption in `prosody.cfg.lua`:
  ```lua
  c2s_require_encryption = true
  s2s_require_encryption = true
  ```
- [ ] Update `config/xmpp.php` to enable TLS:
  ```env
  XMPP_USE_TLS=true
  XMPP_VERIFY_PEER=true
  ```

### 2. Authentication
- [ ] **CRITICAL**: Change default XMPP password from `acsadmin123`
- [ ] Set strong password in `.env`:
  ```env
  XMPP_PASSWORD=<secure-random-password>
  ```
- [ ] Consider SCRAM-SHA-1/256 instead of PLAIN SASL (requires Prosody module update)
- [ ] Implement certificate-based authentication for CPE devices (advanced)

### 3. Access Control
- [ ] Verify Prosody listens only on `127.0.0.1` for internal ACS communication
- [ ] If exposing externally, configure firewall rules (port 6000)
- [ ] Disable BOSH if not needed (`modules_disabled = {"bosh"}`)
- [ ] Review Prosody security modules: `mod_blocklist`, `mod_limits`

---

## 🔌 USP Protocol Buffers Integration (FUNCTIONAL)

### 4. UspMessageService Integration
Current `UspXmppTransport` only does base64 encoding/decoding. Full integration requires:

- [ ] Import USP Protocol Buffers schema (BBF TR-369 spec)
- [ ] Generate PHP classes using `protoc` compiler:
  ```bash
  protoc --php_out=app/Proto usp-msg-1-4.proto
  ```
- [ ] Update `UspXmppTransport::sendUspMessage()` to serialize USP messages:
  ```php
  use Broadband_Forum\Usp\Msg;
  
  $msg = new Msg();
  $msg->setHeader($header);
  $msg->setBody($body);
  $protobufMessage = $msg->serializeToString();
  ```
- [ ] Update `UspXmppTransport::receiveUspMessages()` to deserialize:
  ```php
  $msg = new Msg();
  $msg->mergeFromString($protobufMessage);
  ```
- [ ] Add validation for USP message types (Get, Set, Notify, etc.)
- [ ] Integrate with existing `app/Services/UspMessageService.php`

### 5. Stanza Object Handling
- [ ] Verify `pdahal/php-xmpp` response format (string vs object)
- [ ] Update regex extraction in `extractUspPayload()` if library returns objects
- [ ] Add structured parsing for XMPP stanza attributes (from, to, id)
- [ ] Implement proper error handling for malformed stanzas

---

## ⚙️ Prosody Optimization

### 6. Carrier-Grade Tuning
- [ ] Increase file descriptor limits for 100,000+ connections:
  ```bash
  ulimit -n 1000000
  ```
- [ ] Tune Prosody memory/connection limits in config:
  ```lua
  limits = {
      c2s = { rate = "500kb/s"; burst = "5s"; };
      s2s = { rate = "1mb/s"; burst = "10s"; };
  }
  ```
- [ ] Configure persistent storage for offline messages (PostgreSQL/Redis)
- [ ] Enable clustering for horizontal scaling (mod_component)
- [ ] Set up Prosody metrics/monitoring (mod_prometheus or custom)

### 7. Resource Management
- [ ] Configure log rotation (`logrotate` for `prosody.log`)
- [ ] Monitor disk usage for `./data` directory (CPE rosters, offline messages)
- [ ] Set up automatic cleanup for stale sessions
- [ ] Implement health check endpoint for workflow monitoring

---

## 🧪 Testing & Validation

### 8. Unit Tests
- [ ] Create unit tests for `XmppClientService` (connect/disconnect/send)
- [ ] Create unit tests for `UspXmppTransport` (USP encoding/decoding)
- [ ] Mock Prosody responses for testing
- [ ] Test error scenarios (connection timeout, auth failure)

### 9. Integration Tests
- [ ] Test ACS → CPE message flow with real Prosody server
- [ ] Test CPE → ACS message flow (simulate CPE device)
- [ ] Test large USP messages (>1MB, verify 2MB stanza limit)
- [ ] Test concurrent connections (1,000+ simulated CPEs)
- [ ] Verify NAT traversal scenarios

### 10. Load Testing
- [ ] Benchmark Prosody with 10,000 concurrent connections
- [ ] Measure message throughput (messages/second)
- [ ] Profile memory usage at scale
- [ ] Test failover and reconnection logic

---

## 📊 Monitoring & Observability

### 11. Logging
- [ ] Integrate Prosody logs with Laravel logging system
- [ ] Add structured logging (JSON format) for XMPP events
- [ ] Log USP message IDs for correlation
- [ ] Set up log aggregation (ELK, Graylog, or cloud solution)

### 12. Metrics
- [ ] Track XMPP connection count (gauge)
- [ ] Track USP message send/receive rates (counter)
- [ ] Track connection errors and retries (counter)
- [ ] Monitor Prosody CPU/memory usage
- [ ] Alert on authentication failures (security)

---

## 🚀 Deployment

### 13. Workflow Configuration
- [ ] Verify Prosody workflow starts automatically on server boot
- [ ] Configure Supervisor/systemd for Prosody process management
- [ ] Set up automatic restart on crash
- [ ] Document manual start/stop procedures

### 14. Environment Variables
Ensure these are set in production `.env`:
```env
XMPP_HOST=127.0.0.1
XMPP_PORT=6000
XMPP_JID=acs-server@acs.local
XMPP_PASSWORD=<secure-password>
XMPP_USE_TLS=true
XMPP_VERIFY_PEER=true
XMPP_CERT_PATH=/path/to/cert.pem
XMPP_KEY_PATH=/path/to/key.pem
```

### 15. Documentation
- [ ] Document XMPP architecture for operations team
- [ ] Create runbook for common issues (connection failures, auth errors)
- [ ] Document CPE device JID registration process
- [ ] Update API documentation with XMPP transport option

---

## ✅ Next Steps (Immediate)

1. **Set XMPP password**: Update `.env` with `XMPP_PASSWORD`
2. **Generate TLS certs**: Use Let's Encrypt or self-signed for dev
3. **Integrate Protocol Buffers**: Complete USP message serialization
4. **Integration with UspMessageService**: Connect XMPP transport to existing USP logic
5. **Test with real CPE**: Deploy to test device and verify end-to-end flow

---

## 📝 Notes

- Current implementation is **localhost-only** (`127.0.0.1`) for security
- Port 6000 used instead of standard 5222 (Replit constraint)
- Admin telnet disabled (security hardening)
- TLS disabled by default (development mode)
- No USP Protocol Buffers serialization yet (base64 wrapper only)

**Estimated effort for production-readiness**: 3-5 days of development + testing.
