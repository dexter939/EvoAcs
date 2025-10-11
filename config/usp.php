<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | USP Controller Endpoint ID
    |--------------------------------------------------------------------------
    |
    | The endpoint ID used by the ACS as USP Controller.
    | This must be unique across all USP endpoints in the network.
    |
    */
    
    'controller_endpoint_id' => env('USP_CONTROLLER_ENDPOINT_ID', 'proto::acs-controller'),
    
    /*
    |--------------------------------------------------------------------------
    | USP MQTT Topics
    |--------------------------------------------------------------------------
    |
    | Topic structure for USP MQTT communication:
    | - Controller receives from devices: usp/controller/{controller-id}/{agent-id}
    | - Devices receive from controller: usp/agent/{agent-id}/request
    |
    */
    
    'mqtt' => [
        'controller_topic_pattern' => env('USP_MQTT_CONTROLLER_TOPIC', 'usp/controller'),
        'agent_topic_pattern' => env('USP_MQTT_AGENT_TOPIC', 'usp/agent'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | USP Default Values
    |--------------------------------------------------------------------------
    |
    | Default values for USP device auto-registration
    |
    */
    
    'defaults' => [
        'oui' => env('USP_DEFAULT_OUI', '000000'),
        'product_class' => env('USP_DEFAULT_PRODUCT_CLASS', 'USP Device'),
    ],
    
];
