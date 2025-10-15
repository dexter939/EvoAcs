<?php

return [
    'host' => env('XMPP_HOST', '127.0.0.1'),
    'port' => env('XMPP_PORT', 6000),
    'jid' => env('XMPP_JID', 'acs-server@acs.local'),
    'password' => env('XMPP_PASSWORD', 'acsadmin123'),
    
    'usp' => [
        'cpe_jid_format' => 'device-{serial}@acs.local',
        'message_type' => 'chat',
        'timeout' => 30,
    ],
];
