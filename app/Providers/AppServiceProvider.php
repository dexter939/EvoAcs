<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\DeviceWentOffline;
use App\Events\FirmwareDeploymentFailed;
use App\Events\DiagnosticTestFailed;
use App\Listeners\RaiseDeviceOfflineAlarm;
use App\Listeners\RaiseFirmwareFailureAlarm;
use App\Listeners\RaiseDiagnosticFailureAlarm;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(DeviceWentOffline::class, RaiseDeviceOfflineAlarm::class);
        Event::listen(FirmwareDeploymentFailed::class, RaiseFirmwareFailureAlarm::class);
        Event::listen(DiagnosticTestFailed::class, RaiseDiagnosticFailureAlarm::class);
    }
}
