<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TR069Controller;
use App\Http\Controllers\UspController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AcsController;

// Home - Redirect to Dashboard
Route::get('/', function () {
    return redirect()->route('acs.dashboard');
});

// API JSON Dashboard (legacy)
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// TR-069 Endpoints (Public)
Route::post('/tr069', [TR069Controller::class, 'handleInform'])->name('tr069.inform');
Route::post('/tr069/empty', [TR069Controller::class, 'handleEmpty'])->name('tr069.empty');

// TR-369 USP Endpoints (Public)
Route::match(['get', 'post'], '/usp', [UspController::class, 'handleUspMessage'])->name('usp.message');

// ACS Web Dashboard
Route::prefix('acs')->name('acs.')->group(function () {
    Route::get('/dashboard', [AcsController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/stats-api', [AcsController::class, 'dashboardStatsApi'])->name('dashboard.stats');
    
    // Dispositivi
    Route::get('/devices', [AcsController::class, 'devices'])->name('devices');
    Route::post('/devices', [AcsController::class, 'storeDevice'])->name('devices.store');
    Route::get('/devices/{id}', [AcsController::class, 'showDevice'])->name('devices.show');
    Route::put('/devices/{id}', [AcsController::class, 'updateDevice'])->name('devices.update');
    Route::delete('/devices/{id}', [AcsController::class, 'destroyDevice'])->name('devices.destroy');
    Route::post('/devices/{id}/provision', [AcsController::class, 'provisionDevice'])->name('devices.provision');
    Route::post('/devices/{id}/reboot', [AcsController::class, 'rebootDevice'])->name('devices.reboot');
    Route::post('/devices/{id}/connection-request', [AcsController::class, 'connectionRequest'])->name('devices.connection-request');
    Route::post('/devices/{id}/diagnostics/{type}', [AcsController::class, 'runDiagnostic'])->name('devices.diagnostic');
    Route::get('/diagnostics/{id}/results', [AcsController::class, 'getDiagnosticResults'])->name('diagnostics.results');
    
    // USP Event Subscriptions
    Route::get('/devices/{id}/subscriptions', [AcsController::class, 'subscriptions'])->name('devices.subscriptions');
    Route::post('/devices/{id}/subscriptions', [AcsController::class, 'storeSubscription'])->name('devices.subscriptions.store');
    Route::delete('/devices/{id}/subscriptions/{subscriptionId}', [AcsController::class, 'destroySubscription'])->name('devices.subscriptions.destroy');
    
    // Provisioning
    Route::get('/provisioning', [AcsController::class, 'provisioning'])->name('provisioning');
    
    // Firmware
    Route::get('/firmware', [AcsController::class, 'firmware'])->name('firmware');
    Route::post('/firmware/upload', [AcsController::class, 'uploadFirmware'])->name('firmware.upload');
    Route::post('/firmware/{id}/deploy', [AcsController::class, 'deployFirmware'])->name('firmware.deploy');
    
    // Task Queue
    Route::get('/tasks', [AcsController::class, 'tasks'])->name('tasks');
    
    // Profili Configurazione CRUD
    Route::get('/profiles', [AcsController::class, 'profiles'])->name('profiles');
    Route::post('/profiles', [AcsController::class, 'storeProfile'])->name('profiles.store');
    Route::put('/profiles/{id}', [AcsController::class, 'updateProfile'])->name('profiles.update');
    Route::delete('/profiles/{id}', [AcsController::class, 'destroyProfile'])->name('profiles.destroy');
    
    // Diagnostics (TR-143)
    Route::get('/diagnostics', [AcsController::class, 'diagnostics'])->name('diagnostics');
    Route::get('/diagnostics/{id}/details', [AcsController::class, 'diagnosticDetails'])->name('diagnostics.details');
    
    // VoIP Services (TR-104)
    Route::get('/voip', [AcsController::class, 'voip'])->name('voip');
    Route::get('/voip/{deviceId}', [AcsController::class, 'voipDevice'])->name('voip.device');
    Route::post('/voip/{deviceId}/configure', [AcsController::class, 'voipConfigure'])->name('voip.configure');
    
    // Storage/NAS Services (TR-140)
    Route::get('/storage', [AcsController::class, 'storage'])->name('storage');
    Route::get('/storage/{deviceId}', [AcsController::class, 'storageDevice'])->name('storage.device');
    Route::post('/storage/{deviceId}/configure', [AcsController::class, 'storageConfigure'])->name('storage.configure');
    
    // IoT Devices (TR-181)
    Route::get('/iot', [AcsController::class, 'iot'])->name('iot');
    Route::get('/iot/{deviceId}', [AcsController::class, 'iotDevice'])->name('iot.device');
    Route::post('/iot/{deviceId}/control', [AcsController::class, 'iotControl'])->name('iot.control');
    
    // LAN Devices (TR-64)
    Route::get('/lan-devices', [AcsController::class, 'lanDevices'])->name('lan-devices');
    Route::get('/lan-devices/{deviceId}', [AcsController::class, 'lanDeviceDetail'])->name('lan-devices.detail');
    
    // Femtocell RF Management (TR-196)
    Route::get('/femtocell', [AcsController::class, 'femtocell'])->name('femtocell');
    Route::get('/femtocell/{deviceId}', [AcsController::class, 'femtocellDevice'])->name('femtocell.device');
    Route::post('/femtocell/{deviceId}/configure', [AcsController::class, 'femtocellConfigure'])->name('femtocell.configure');
    
    // STB/IPTV Services (TR-135)
    Route::get('/stb', [AcsController::class, 'stb'])->name('stb');
    Route::get('/stb/{deviceId}', [AcsController::class, 'stbDevice'])->name('stb.device');
    Route::post('/stb/{deviceId}/configure', [AcsController::class, 'stbConfigure'])->name('stb.configure');
    
    // Parameter Discovery (TR-111)
    Route::get('/parameters', [AcsController::class, 'parameters'])->name('parameters');
    Route::get('/parameters/{deviceId}', [AcsController::class, 'parametersDevice'])->name('parameters.device');
    Route::post('/parameters/{deviceId}/discover', [AcsController::class, 'parametersDiscover'])->name('parameters.discover');
    
    // Multi-tenant Customers & Services
    Route::get('/customers', [AcsController::class, 'customers'])->name('customers');
    Route::get('/customers/{customerId}', [AcsController::class, 'customerDetail'])->name('customers.detail');
    Route::post('/customers', [AcsController::class, 'storeCustomer'])->name('customers.store');
    Route::put('/customers/{customerId}', [AcsController::class, 'updateCustomer'])->name('customers.update');
    Route::delete('/customers/{customerId}', [AcsController::class, 'destroyCustomer'])->name('customers.destroy');
    
    Route::get('/services/{serviceId}', [AcsController::class, 'serviceDetail'])->name('services.detail');
    Route::post('/services', [AcsController::class, 'storeService'])->name('services.store');
    Route::put('/services/{serviceId}', [AcsController::class, 'updateService'])->name('services.update');
    Route::delete('/services/{serviceId}', [AcsController::class, 'destroyService'])->name('services.destroy');
});
