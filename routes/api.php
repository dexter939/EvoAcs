<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\ProvisioningController;
use App\Http\Controllers\Api\FirmwareController;
use App\Http\Controllers\Api\DiagnosticsController;
use App\Http\Controllers\Api\UspController;
use App\Http\Controllers\Api\VoiceServiceController;
use App\Http\Controllers\Api\StorageServiceController;
use App\Http\Controllers\Api\ParameterDiscoveryController;
use App\Http\Controllers\Api\LanDeviceController;
use App\Http\Controllers\Api\IotDeviceController;
use App\Http\Controllers\Api\FemtocellController;
use App\Http\Controllers\Api\StbServiceController;

Route::prefix('v1')->middleware(\App\Http\Middleware\ApiKeyAuth::class)->group(function () {
    Route::apiResource('devices', DeviceController::class);
    Route::post('devices/{device}/provision', [ProvisioningController::class, 'provisionDevice']);
    Route::post('devices/{device}/parameters/get', [ProvisioningController::class, 'getParameters']);
    Route::post('devices/{device}/parameters/set', [ProvisioningController::class, 'setParameters']);
    Route::post('devices/{device}/reboot', [ProvisioningController::class, 'rebootDevice']);
    Route::post('devices/{device}/connection-request', [ProvisioningController::class, 'connectionRequest']);
    
    Route::apiResource('firmware', FirmwareController::class);
    Route::post('firmware/{firmware}/deploy', [FirmwareController::class, 'deploy']);
    
    Route::get('tasks', [ProvisioningController::class, 'listTasks']);
    Route::get('tasks/{task}', [ProvisioningController::class, 'getTask']);
    
    // Diagnostics TR-143
    Route::post('devices/{device}/diagnostics/ping', [DiagnosticsController::class, 'ping']);
    Route::post('devices/{device}/diagnostics/traceroute', [DiagnosticsController::class, 'traceroute']);
    Route::post('devices/{device}/diagnostics/download', [DiagnosticsController::class, 'download']);
    Route::post('devices/{device}/diagnostics/upload', [DiagnosticsController::class, 'upload']);
    Route::post('devices/{device}/diagnostics/udpecho', [DiagnosticsController::class, 'udpEcho']);
    Route::get('devices/{device}/diagnostics', [DiagnosticsController::class, 'listDeviceDiagnostics']);
    Route::get('diagnostics', [DiagnosticsController::class, 'index']);
    Route::get('diagnostics/{diagnostic}', [DiagnosticsController::class, 'getResults']);
    
    // USP TR-369 Operations
    Route::post('usp/devices/{device}/get-params', [UspController::class, 'getParameters']);
    Route::post('usp/devices/{device}/set-params', [UspController::class, 'setParameters']);
    Route::post('usp/devices/{device}/operate', [UspController::class, 'operate']);
    Route::post('usp/devices/{device}/add-object', [UspController::class, 'addObject']);
    Route::post('usp/devices/{device}/delete-object', [UspController::class, 'deleteObject']);
    Route::post('usp/devices/{device}/reboot', [UspController::class, 'reboot']);
    
    // USP TR-369 Event Subscriptions
    Route::post('usp/devices/{device}/subscribe', [UspController::class, 'createSubscription']);
    Route::get('usp/devices/{device}/subscriptions', [UspController::class, 'listSubscriptions']);
    Route::delete('usp/devices/{device}/subscriptions/{subscription}', [UspController::class, 'deleteSubscription']);
    
    // TR-104 VoIP Service Management
    Route::apiResource('voice-services', VoiceServiceController::class);
    Route::post('voice-services/{service}/provision', [VoiceServiceController::class, 'provisionService']);
    Route::post('voice-services/{service}/sip-profiles', [VoiceServiceController::class, 'createSipProfile']);
    Route::post('sip-profiles/{profile}/voip-lines', [VoiceServiceController::class, 'createVoipLine']);
    Route::get('voice-services/stats/overview', [VoiceServiceController::class, 'getStatistics']);
    
    // TR-140 Storage Service Management
    Route::apiResource('storage-services', StorageServiceController::class);
    Route::post('storage-services/{service}/provision', [StorageServiceController::class, 'provisionService']);
    Route::post('storage-services/{service}/volumes', [StorageServiceController::class, 'createVolume']);
    Route::post('storage-services/{service}/file-servers', [StorageServiceController::class, 'createFileServer']);
    Route::get('storage-services/stats/overview', [StorageServiceController::class, 'getStatistics']);
    
    // TR-111 Parameter Discovery
    Route::post('devices/{device}/discover-parameters', [ParameterDiscoveryController::class, 'discoverParameters']);
    Route::get('devices/{device}/capabilities', [ParameterDiscoveryController::class, 'getCapabilities']);
    Route::get('devices/{device}/capabilities/stats', [ParameterDiscoveryController::class, 'getStats']);
    Route::get('devices/{device}/capabilities/path', [ParameterDiscoveryController::class, 'getCapabilityByPath']);
    
    // TR-64 LAN-Side Configuration
    Route::get('devices/{device}/lan-devices', [LanDeviceController::class, 'index']);
    Route::post('devices/{device}/lan-devices/ssdp', [LanDeviceController::class, 'processSsdpAnnouncement']);
    Route::post('lan-devices/{lanDevice}/soap-action', [LanDeviceController::class, 'invokeSoapAction']);
    
    // TR-181 IoT Extension
    Route::get('devices/{device}/smart-home-devices', [IotDeviceController::class, 'listDevices']);
    Route::post('devices/{device}/smart-home-devices', [IotDeviceController::class, 'provisionDevice']);
    Route::patch('smart-home-devices/{smartDevice}/state', [IotDeviceController::class, 'updateState']);
    Route::get('devices/{device}/iot-services', [IotDeviceController::class, 'listServices']);
    Route::post('devices/{device}/iot-services', [IotDeviceController::class, 'createService']);
    
    // TR-196 Femtocell
    Route::post('devices/{device}/femtocell/configure', [FemtocellController::class, 'configure']);
    Route::post('femtocell-configs/{config}/neighbor-cells', [FemtocellController::class, 'addNeighborCell']);
    Route::post('femtocell-configs/{config}/scan', [FemtocellController::class, 'scanEnvironment']);
    
    // TR-135 STB/IPTV
    Route::post('devices/{device}/stb-services', [StbServiceController::class, 'provisionService']);
    Route::post('stb-services/{service}/sessions', [StbServiceController::class, 'startSession']);
    Route::patch('streaming-sessions/{session}/qos', [StbServiceController::class, 'updateQos']);
});
