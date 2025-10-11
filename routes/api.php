<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\ProvisioningController;
use App\Http\Controllers\Api\FirmwareController;

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
});
