<?php

namespace App\Services;

use App\Models\CpeDevice;
use App\Models\SmartHomeDevice;
use App\Models\IotService;

class IotProvisioningService
{
    public function provisionSmartDevice(CpeDevice $cpeDevice, array $deviceData): SmartHomeDevice
    {
        return $cpeDevice->smartHomeDevices()->create([
            'device_class' => $deviceData['device_class'],
            'device_name' => $deviceData['device_name'],
            'protocol' => $deviceData['protocol'],
            'ieee_address' => $deviceData['ieee_address'] ?? null,
            'manufacturer' => $deviceData['manufacturer'] ?? null,
            'model' => $deviceData['model'] ?? null,
            'firmware_version' => $deviceData['firmware_version'] ?? null,
            'status' => $deviceData['status'] ?? 'online',
            'capabilities' => $deviceData['capabilities'] ?? [],
            'current_state' => $deviceData['current_state'] ?? [],
            'configuration' => $deviceData['configuration'] ?? [],
            'last_seen' => now()
        ]);
    }

    public function createIotService(CpeDevice $cpeDevice, array $serviceData): IotService
    {
        return $cpeDevice->iotServices()->create([
            'service_type' => $serviceData['service_type'],
            'service_name' => $serviceData['service_name'],
            'enabled' => $serviceData['enabled'] ?? true,
            'linked_devices' => $serviceData['linked_devices'] ?? [],
            'automation_rules' => $serviceData['automation_rules'] ?? [],
            'schedule' => $serviceData['schedule'] ?? [],
            'statistics' => []
        ]);
    }

    public function updateDeviceState(SmartHomeDevice $device, array $state): SmartHomeDevice
    {
        $device->update([
            'current_state' => array_merge($device->current_state ?? [], $state),
            'last_seen' => now()
        ]);
        return $device->fresh();
    }

    public function executeAutomation(IotService $service): array
    {
        $results = [];
        foreach ($service->automation_rules ?? [] as $rule) {
            if ($this->evaluateCondition($rule['condition'] ?? [])) {
                $results[] = $this->executeAction($rule['action'] ?? []);
            }
        }
        return $results;
    }

    private function evaluateCondition(array $condition): bool
    {
        return true; // Simplified for now
    }

    private function executeAction(array $action): array
    {
        return ['executed' => true, 'action' => $action];
    }
}
