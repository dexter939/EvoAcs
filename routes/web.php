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
    Route::get('/devices/{id}', [AcsController::class, 'showDevice'])->name('devices.show');
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
});
