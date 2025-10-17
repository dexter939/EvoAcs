<?php

namespace App\Listeners;

use App\Events\DeviceWentOffline;
use App\Services\AlarmService;

class RaiseDeviceOfflineAlarm
{
    public function __construct(
        private AlarmService $alarmService
    ) {}

    public function handle(DeviceWentOffline $event): void
    {
        $this->alarmService->autoRaiseDeviceOfflineAlarm($event->device);
    }
}
