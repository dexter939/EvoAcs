<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\CpeDevice;
use App\Models\SmartHomeDevice;
use App\Models\IotService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IotDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_smart_home_devices(): void
    {
        $device = CpeDevice::factory()->create();
        
        SmartHomeDevice::create([
            'cpe_device_id' => $device->id,
            'device_class' => 'lighting',
            'device_name' => 'Living Room Light',
            'protocol' => 'ZigBee',
            'status' => 'online',
            'current_state' => ['on' => true, 'brightness' => 80],
            'last_seen' => now()
        ]);

        $response = $this->apiGet("/api/v1/devices/{$device->id}/smart-home-devices");

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['device_class', 'device_name', 'protocol', 'status']
            ]);
    }

    public function test_provision_smart_device(): void
    {
        $device = CpeDevice::factory()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/smart-home-devices", [
            'device_class' => 'thermostat',
            'device_name' => 'Bedroom Thermostat',
            'protocol' => 'Matter',
            'ieee_address' => '00:11:22:33:44:55:66:77',
            'capabilities' => ['temperature_control', 'humidity_sensor']
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'device' => ['device_class', 'device_name', 'protocol']
            ]);

        $this->assertDatabaseHas('smart_home_devices', [
            'cpe_device_id' => $device->id,
            'device_name' => 'Bedroom Thermostat',
            'protocol' => 'Matter'
        ]);
    }

    public function test_update_device_state(): void
    {
        $device = CpeDevice::factory()->create();
        
        $smartDevice = SmartHomeDevice::create([
            'cpe_device_id' => $device->id,
            'device_class' => 'lighting',
            'device_name' => 'Test Light',
            'protocol' => 'WiFi',
            'status' => 'online',
            'current_state' => ['on' => false],
            'last_seen' => now()
        ]);

        $response = $this->apiRequest('PATCH', "/api/v1/smart-home-devices/{$smartDevice->id}/state", [
            'state' => ['on' => true, 'brightness' => 100]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'device' => [
                    'current_state' => ['on' => true, 'brightness' => 100]
                ]
            ]);
    }

    public function test_list_iot_services(): void
    {
        $device = CpeDevice::factory()->create();
        
        IotService::create([
            'cpe_device_id' => $device->id,
            'service_type' => 'lighting_automation',
            'service_name' => 'Evening Scene',
            'enabled' => true
        ]);

        $response = $this->apiGet("/api/v1/devices/{$device->id}/iot-services");

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['service_type', 'service_name', 'enabled']
            ]);
    }

    public function test_create_iot_service(): void
    {
        $device = CpeDevice::factory()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/iot-services", [
            'service_type' => 'security_monitoring',
            'service_name' => 'Home Security',
            'automation_rules' => [
                ['trigger' => 'motion_detected', 'action' => 'send_notification']
            ]
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'service' => ['service_type', 'service_name']
            ]);
    }

    public function test_provision_smart_device_validates_protocol(): void
    {
        $device = CpeDevice::factory()->create([
            'protocol_type' => 'tr069',
            'mtp_type' => null,
            'status' => 'online'
        ]);

        $response = $this->apiPost("/api/v1/devices/{$device->id}/smart-home-devices", [
            'device_class' => 'lighting',
            'device_name' => 'Test Light',
            'protocol' => 'INVALID_PROTOCOL'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['protocol']);
    }

    protected function apiRequest($method, $uri, $data = [])
    {
        return $this->json($method, $uri, $data, $this->apiHeaders());
    }
}
