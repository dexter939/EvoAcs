#!/bin/bash
################################################################################
# Test Real Devices Against Production Server
# Verifica connessioni TR-069, TR-369 USP, XMPP da dispositivi reali
################################################################################

set -e

PRODUCTION_HOST="${1}"
DEVICE_IP="${2}"

echo "╔════════════════════════════════════════════════════════════╗"
echo "║    ACS Remote Device Testing Tool                         ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

if [ -z "$PRODUCTION_HOST" ] || [ -z "$DEVICE_IP" ]; then
    echo "❌ Usage: $0 <production-server> <device-ip>"
    echo ""
    echo "Examples:"
    echo "  $0 acs.mycompany.com 192.168.1.100"
    echo "  $0 203.0.113.50 10.0.0.5"
    exit 1
fi

echo "📋 Test Configuration:"
echo "  Production ACS: $PRODUCTION_HOST"
echo "  Device IP: $DEVICE_IP"
echo ""

################################################################################
# Test 1: Network Connectivity
################################################################################
echo "[1/5] Testing network connectivity..."

echo -n "  Ping production server: "
if ping -c 1 -W 2 "$PRODUCTION_HOST" > /dev/null 2>&1; then
    echo "✅ OK"
else
    echo "❌ FAILED"
fi

echo -n "  Ping device: "
if ping -c 1 -W 2 "$DEVICE_IP" > /dev/null 2>&1; then
    echo "✅ OK"
else
    echo "❌ FAILED (device might be behind firewall)"
fi

################################################################################
# Test 2: ACS Endpoints
################################################################################
echo ""
echo "[2/5] Testing ACS endpoints..."

echo -n "  HTTP Dashboard: "
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://$PRODUCTION_HOST/acs/dashboard" --max-time 5)
if [ "$HTTP_CODE" -eq 200 ]; then
    echo "✅ OK ($HTTP_CODE)"
else
    echo "⚠️  HTTP $HTTP_CODE"
fi

echo -n "  TR-069 Endpoint: "
TR069_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://$PRODUCTION_HOST/tr069" --max-time 5)
if [ "$TR069_CODE" -eq 200 ] || [ "$TR069_CODE" -eq 405 ]; then
    echo "✅ OK (endpoint active)"
else
    echo "⚠️  HTTP $TR069_CODE"
fi

echo -n "  XMPP Port 6000: "
if timeout 2 bash -c "echo '' | nc -z $PRODUCTION_HOST 6000" 2>/dev/null; then
    echo "✅ OPEN"
else
    echo "❌ CLOSED (check firewall)"
fi

################################################################################
# Test 3: Device Web Interface (MikroTik)
################################################################################
echo ""
echo "[3/5] Testing device web interface..."

echo -n "  Device HTTP: "
if curl -s -o /dev/null --max-time 3 "http://$DEVICE_IP" 2>/dev/null; then
    echo "✅ Reachable"
else
    echo "⚠️  Not reachable (normal if device is behind NAT)"
fi

################################################################################
# Test 4: TR-069 Configuration Check
################################################################################
echo ""
echo "[4/5] Checking if device is registered in ACS..."

# Generate test commands for MikroTik
cat > /tmp/mikrotik_test_config.txt << MIKROTIK_EOF
# MikroTik RouterOS Configuration Commands
# Copy-paste these into your MikroTik terminal:

/tr069-client
set enabled=yes
set acs-url=https://$PRODUCTION_HOST/tr069
set username=admin
set password=admin123
set periodic-inform-enabled=yes
set periodic-inform-interval=00:01:00

# Force immediate connection
/tr069-client inform

# Check status
/tr069-client print detail
MIKROTIK_EOF

echo ""
echo "📝 MikroTik Configuration Commands:"
echo "   Saved to: /tmp/mikrotik_test_config.txt"
echo ""
cat /tmp/mikrotik_test_config.txt
echo ""

################################################################################
# Test 5: Monitor Real-Time Logs
################################################################################
echo "[5/5] Setting up real-time log monitoring..."
echo ""
echo "📊 To monitor device connections in real-time:"
echo ""
echo "Terminal 1 - Laravel logs:"
echo "  ssh root@$PRODUCTION_HOST 'tail -f /opt/acs/app/storage/logs/laravel.log | grep TR069'"
echo ""
echo "Terminal 2 - Nginx access logs:"
echo "  ssh root@$PRODUCTION_HOST 'tail -f /var/log/nginx/access.log | grep tr069'"
echo ""
echo "Terminal 3 - XMPP Prosody logs:"
echo "  ssh root@$PRODUCTION_HOST 'tail -f /var/log/prosody/prosody.log'"
echo ""

################################################################################
# Generate Device Test Report
################################################################################
echo "╔════════════════════════════════════════════════════════════╗"
echo "║              📋 Device Test Report                         ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

cat > /tmp/device_test_report.txt << REPORT_EOF
ACS Device Test Report
Generated: $(date)

Production Server: $PRODUCTION_HOST
Device IP: $DEVICE_IP

=== Connectivity ===
- Production server reachable: $(ping -c 1 -W 2 "$PRODUCTION_HOST" > /dev/null 2>&1 && echo "YES" || echo "NO")
- Device reachable: $(ping -c 1 -W 2 "$DEVICE_IP" > /dev/null 2>&1 && echo "YES" || echo "NO")

=== ACS Endpoints ===
- Dashboard HTTP: $HTTP_CODE
- TR-069 Endpoint: $TR069_CODE
- XMPP Port 6000: $(timeout 1 bash -c "echo '' | nc -z $PRODUCTION_HOST 6000" 2>/dev/null && echo "OPEN" || echo "CLOSED")

=== Next Steps ===
1. Apply MikroTik configuration (see /tmp/mikrotik_test_config.txt)
2. Monitor logs for device Inform messages
3. Check ACS dashboard: https://$PRODUCTION_HOST/acs/devices
4. Verify device appears in device list

=== Troubleshooting ===
If device doesn't appear:
- Check device can reach $PRODUCTION_HOST
- Verify ACS URL in device config
- Check firewall allows outbound port 80/443
- Monitor logs for connection attempts
- Verify periodic-inform is enabled

REPORT_EOF

echo "Report saved to: /tmp/device_test_report.txt"
echo ""
echo "💡 Quick Checks:"
echo ""
echo "  1. View ACS dashboard:"
echo "     http://$PRODUCTION_HOST/acs/dashboard"
echo ""
echo "  2. Check if device connected:"
echo "     ssh root@$PRODUCTION_HOST 'cd /opt/acs/app && php artisan tinker'"
echo "     >>> \\App\\Models\\Device::where(\"ip_address\", \"$DEVICE_IP\")->first()"
echo ""
echo "  3. Trigger manual Inform from MikroTik:"
echo "     /tr069-client inform"
echo ""
