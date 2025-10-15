<?php

return [
    'host' => env('XMPP_HOST', '127.0.0.1'),
    'port' => env('XMPP_PORT', 6000),
    'jid' => env('XMPP_JID', 'acs-server@acs.local'),
    
    'password' => env('XMPP_PASSWORD'),
    
    'security' => [
        'use_tls' => env('XMPP_USE_TLS', false),
        'verify_peer' => env('XMPP_VERIFY_PEER', false),
        'cert_path' => env('XMPP_CERT_PATH'),
        'key_path' => env('XMPP_KEY_PATH'),
    ],
    
    'usp' => [
        'cpe_jid_format' => 'device-{serial}@acs.local',
        'message_type' => 'chat',
        'timeout' => 30,
    ],
];
