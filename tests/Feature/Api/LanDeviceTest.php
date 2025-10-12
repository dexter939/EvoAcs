<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\CpeDevice;
use App\Models\LanDevice;
use App\Services\UpnpDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class LanDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_lan_devices(): void
    {
        $device = CpeDevice::factory()->create();
        
        LanDevice::create([
            'cpe_device_id' => $device->id,
            'usn' => 'uuid:12345678-1234-1234-1234-123456789012',
            'device_type' => 'urn:schemas-upnp-org:device:MediaRenderer:1',
            'friendly_name' => 'Test Device',
            'status' => 'active',
            'last_seen' => now()
        ]);

        $response = $this->apiGet("/api/v1/devices/{$device->id}/lan-devices");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['usn', 'device_type', 'friendly_name', 'status']
                ]
            ]);
    }

    public function test_process_ssdp_announcement(): void
    {
        $device = CpeDevice::factory()->create();

        $usn = 'uuid:' . uniqid();
        $mockLanDevice = new LanDevice([
            'cpe_device_id' => $device->id,
            'usn' => $usn,
            'location' => 'http://192.168.1.100:1900/description.xml',
            'device_type' => 'urn:schemas-upnp-org:device:MediaRenderer:1',
            'status' => 'active'
        ]);
        $mockLanDevice->id = 1;

        $mock = Mockery::mock(UpnpDiscoveryService::class);
        $mock->shouldReceive('processSsdpAnnouncement')
            ->once()
            ->andReturn($mockLanDevice);

        $this->app->instance(UpnpDiscoveryService::class, $mock);

        $response = $this->apiPost("/api/v1/devices/{$device->id}/lan-devices/ssdp", [
            'usn' => $usn,
            'location' => 'http://192.168.1.100:1900/description.xml',
            'nt' => 'urn:schemas-upnp-org:device:MediaRenderer:1'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'lan_device' => ['usn', 'location', 'device_type']
            ]);
    }

    public function test_ssdp_announcement_validates_usn(): void
    {
        $device = CpeDevice::factory()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/lan-devices/ssdp", [
            'location' => 'http://192.168.1.100:1900/description.xml'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['usn']);
    }

    public function test_invoke_soap_action(): void
    {
        $device = CpeDevice::factory()->create();
        
        $lanDevice = LanDevice::create([
            'cpe_device_id' => $device->id,
            'usn' => 'uuid:test-device',
            'location' => 'http://192.168.1.100:49152/description.xml',
            'services' => [
                [
                    'serviceType' => 'urn:schemas-upnp-org:service:DeviceInfo:1',
                    'controlURL' => '/upnp/control/deviceinfo1'
                ]
            ],
            'status' => 'active',
            'last_seen' => now()
        ]);

        $mockResult = [
            'Status' => 'OK',
            'Info' => 'Device information retrieved'
        ];

        $mock = Mockery::mock(UpnpDiscoveryService::class);
        $mock->shouldReceive('invokeSoapAction')
            ->once()
            ->andReturn($mockResult);

        $this->app->instance(UpnpDiscoveryService::class, $mock);

        $response = $this->apiPost("/api/v1/lan-devices/{$lanDevice->id}/soap-action", [
            'service_type' => 'urn:schemas-upnp-org:service:DeviceInfo:1',
            'action' => 'GetInfo',
            'arguments' => []
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'result'
            ]);
    }
}
