#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\XmppClientService;
use App\Services\UspXmppTransport;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  MikroTik XMPP Test - TR-369 USP Transport Simulator      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    echo "🔌 Connecting to XMPP server...\n";
    
    $xmppClient = new XmppClientService();
    
    if (!$xmppClient->connect()) {
        echo "❌ Failed to connect to XMPP server\n";
        exit(1);
    }
    
    echo "✅ Connected as: " . $xmppClient->getJid() . "\n";
    echo "\n";
    
    $uspTransport = new UspXmppTransport($xmppClient);
    
    echo "📤 Sending test USP message to MikroTik...\n";
    
    $deviceSerial = 'mikrotik-lab';
    
    $testUspMessage = json_encode([
        'msg_id' => uniqid('usp_test_'),
        'msg_type' => 'GET',
        'timestamp' => time(),
        'from' => 'acs-server@acs.local',
        'to' => 'device-mikrotik-lab@acs.local',
        'body' => [
            'request' => [
                'get' => [
                    'param_paths' => [
                        'Device.DeviceInfo.',
                        'Device.ManagementServer.',
                    ],
                ],
            ],
        ],
    ]);
    
    if ($uspTransport->sendUspMessage($deviceSerial, $testUspMessage)) {
        echo "✅ Test USP message sent successfully\n";
        echo "   Target: device-mikrotik-lab@acs.local\n";
        echo "   Payload size: " . strlen($testUspMessage) . " bytes\n";
    } else {
        echo "❌ Failed to send USP message\n";
    }
    
    echo "\n";
    echo "👂 Listening for responses (30 seconds)...\n";
    echo "   Press Ctrl+C to stop\n";
    echo "\n";
    
    $messageCount = 0;
    
    $uspTransport->receiveUspMessages(function($protobufMessage, $rawStanza) use (&$messageCount) {
        $messageCount++;
        
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║  📨 Message #{$messageCount} Received                      \n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        
        try {
            $decoded = json_decode($protobufMessage, true);
            
            if ($decoded) {
                echo "Message ID: " . ($decoded['msg_id'] ?? 'N/A') . "\n";
                echo "Type: " . ($decoded['msg_type'] ?? 'N/A') . "\n";
                echo "From: " . ($decoded['from'] ?? 'N/A') . "\n";
                echo "Payload:\n";
                echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "Raw payload (first 200 chars):\n";
                echo substr($protobufMessage, 0, 200) . "...\n";
            }
        } catch (Exception $e) {
            echo "Raw payload:\n";
            echo substr($protobufMessage, 0, 200) . "...\n";
        }
        
        echo "\n";
    }, 30);
    
    if ($messageCount === 0) {
        echo "⚠️  No messages received\n";
        echo "\n";
        echo "💡 To test with real MikroTik device:\n";
        echo "   1. Configure MikroTik with credentials from docs/MIKROTIK_XMPP_TEST_CONFIG.md\n";
        echo "   2. MikroTik must send XMPP stanza to: acs-server@acs.local\n";
        echo "   3. Format: <usp>base64_encoded_protobuf</usp>\n";
    } else {
        echo "✅ Received {$messageCount} message(s)\n";
    }
    
    echo "\n";
    echo "🔌 Disconnecting...\n";
    $xmppClient->disconnect();
    
    echo "✅ Test completed\n";
    echo "\n";

} catch (Exception $e) {
    echo "\n";
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
