<?php

namespace App\Listeners;

use App\Events\FirmwareDeploymentFailed;
use App\Services\AlarmService;

class RaiseFirmwareFailureAlarm
{
    public function __construct(
        private AlarmService $alarmService
    ) {}

    public function handle(FirmwareDeploymentFailed $event): void
    {
        $deviceSerial = $event->deployment->cpeDevice?->serial_number ?? 'Unknown Device';
        
        $this->alarmService->raiseAlarm([
            'device_id' => $event->deployment->cpe_device_id,
            'alarm_type' => 'firmware_deployment_failed',
            'severity' => 'major',
            'category' => 'firmware',
            'title' => "Firmware Deployment Failed: {$deviceSerial}",
            'description' => "Firmware deployment #{$event->deployment->id} failed: {$event->errorMessage}",
            'metadata' => [
                'deployment_id' => $event->deployment->id,
                'firmware_version' => $event->deployment->firmware_version,
                'error_message' => $event->errorMessage,
            ],
        ]);
    }
}
