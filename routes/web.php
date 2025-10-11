<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TR069Controller;
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

// ACS Web Dashboard
Route::prefix('acs')->name('acs.')->group(function () {
    Route::get('/dashboard', [AcsController::class, 'dashboard'])->name('dashboard');
    Route::get('/devices', [AcsController::class, 'devices'])->name('devices');
    Route::get('/provisioning', [AcsController::class, 'provisioning'])->name('provisioning');
    Route::get('/firmware', [AcsController::class, 'firmware'])->name('firmware');
    Route::get('/tasks', [AcsController::class, 'tasks'])->name('tasks');
    Route::get('/profiles', [AcsController::class, 'profiles'])->name('profiles');
});
