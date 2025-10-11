<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\ProvisioningController;
use App\Http\Controllers\Api\FirmwareController;
use App\Http\Controllers\Api\DiagnosticsController;
use App\Http\Controllers\Api\UspController;

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
});
