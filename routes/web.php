<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TR069Controller;

Route::get('/', function () {
    return ['status' => 'ACS Server Running', 'version' => '1.0.0', 'protocols' => ['TR-069', 'TR-181', 'TR-369']];
});

Route::post('/tr069', [TR069Controller::class, 'handleInform'])->name('tr069.inform');
Route::post('/tr069/empty', [TR069Controller::class, 'handleEmpty'])->name('tr069.empty');
