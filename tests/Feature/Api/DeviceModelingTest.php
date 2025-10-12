<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\CpeDevice;
use App\Models\DeviceCapability;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeviceModelingTest extends TestCase
{
    use RefreshDatabase;

    public function test_discover_parameters_creates_capabilities(): void
    {
        $device = CpeDevice::factory()->online()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/discover-parameters", [
            'root_path' => 'Device.',
            'next_level' => false
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'discovered_count'
            ]);
    }

    public function test_get_capabilities_returns_parameters(): void
    {
        $device = CpeDevice::factory()->create();
        
        DeviceCapability::create([
            'cpe_device_id' => $device->id,
            'parameter_path' => 'Device.DeviceInfo.',
            'parameter_type' => 'object',
            'writable' => false,
            'value_type' => null
        ]);

        $response = $this->apiGet("/api/v1/devices/{$device->id}/capabilities");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['parameter_path', 'parameter_type', 'writable']
                ]
            ]);
    }

    public function test_get_capabilities_with_filtering(): void
    {
        $device = CpeDevice::factory()->create();
        
        DeviceCapability::create([
            'cpe_device_id' => $device->id,
            'parameter_path' => 'Device.WiFi.SSID.1.Enable',
            'parameter_type' => 'parameter',
            'writable' => true,
            'value_type' => 'boolean'
        ]);

        $response = $this->apiGet("/api/v1/devices/{$device->id}/capabilities?root_path=Device.WiFi.");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_get_stats_returns_summary(): void
    {
        $device = CpeDevice::factory()->create();
        
        DeviceCapability::create([
            'cpe_device_id' => $device->id,
            'parameter_path' => 'Device.DeviceInfo.Manufacturer',
            'parameter_type' => 'parameter',
            'writable' => false,
            'value_type' => 'string'
        ]);

        $response = $this->apiGet("/api/v1/devices/{$device->id}/capabilities/stats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_capabilities',
                'by_type',
                'writable_count',
                'readonly_count'
            ]);
    }

    public function test_get_capability_by_path(): void
    {
        $device = CpeDevice::factory()->create();
        
        DeviceCapability::create([
            'cpe_device_id' => $device->id,
            'parameter_path' => 'Device.DeviceInfo.ModelName',
            'parameter_type' => 'parameter',
            'writable' => false,
            'value_type' => 'string'
        ]);

        $response = $this->apiGet("/api/v1/devices/{$device->id}/capabilities/path?path=Device.DeviceInfo.ModelName");

        $response->assertStatus(200)
            ->assertJson([
                'parameter_path' => 'Device.DeviceInfo.ModelName'
            ]);
    }
}
