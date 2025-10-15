-- Prosody XMPP Server Configuration for TR-369 USP Transport
-- Carrier-grade setup for 100,000+ CPE devices

-- Prosody runs on localhost for ACS internal communication
daemonize = false
pidfile = "prosody.pid"

-- Modules to load
modules_enabled = {
    -- Core modules
    "roster";
    "saslauth";
    "tls";
    "dialback";
    "disco";
    "carbons";
    "pep";
    "private";
    "blocklist";
    "vcard4";
    "vcard_legacy";
    "version";
    "uptime";
    "time";
    "ping";
    "bosh";
    "posix";
    "smacks";
}

modules_disabled = {
    "admin_telnet";
    "admin_adhoc";
}

-- Logging configuration
log = {
    info = "prosody.log";
    error = "prosody.log";
}

-- Networking (Replit: porta 6000 invece di 5222 standard)
interfaces = { "0.0.0.0" }
c2s_ports = { 6000 }
s2s_ports = { 6001 }

-- BOSH (HTTP Binding) for web clients
bosh_ports = { 6002 }
consider_bosh_secure = false

-- Security and authentication
authentication = "internal_plain"
allow_registration = false

-- NOTE: TLS disabled for development. For production:
-- 1. Generate TLS certificates (Let's Encrypt or self-signed)
-- 2. Set c2s_require_encryption = true
-- 3. Set s2s_require_encryption = true
-- 4. Configure ssl certificates below
c2s_require_encryption = false
s2s_require_encryption = false
s2s_secure_auth = false

-- Storage (usa directory locale)
data_path = "data"
storage = "internal"
default_storage = "internal"

-- Limits for carrier-grade scalability
limits = {
    c2s = {
        rate = "100kb/s";
        burst = "2s";
    };
    s2s = {
        rate = "300kb/s";
        burst = "3s";
    };
}

-- USP CPE devices
VirtualHost "acs.local"
    enabled = true
    
    -- Admin JID for ACS server
    admins = { "acs-server@acs.local" }
    
    -- Allow large stanzas for USP Protocol Buffers
    c2s_stanza_size_limit = 1024 * 1024 * 2  -- 2 MB for USP messages
    s2s_stanza_size_limit = 1024 * 1024 * 2  -- 2 MB for USP messages

-- Component for external service connection (if needed for USP gateway)
-- Component "usp.acs.local"
--     component_secret = "secret123"

-- SSL/TLS certificates (self-signed for development, real certs for production)
-- ssl = {
--     key = "/path/to/key.pem";
--     certificate = "/path/to/cert.pem";
-- }

-- Telnet admin console disabled for security
-- For production monitoring, use mod_admin_shell with proper authentication
console_enabled = false
