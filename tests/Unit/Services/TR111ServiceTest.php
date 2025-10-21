<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TR111Service;
use App\Models\CpeDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TR111ServiceTest extends TestCase
{
    use RefreshDatabase;

    private TR111Service $service;
    private CpeDevice $device;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TR111Service::class);
        
        $this->device = CpeDevice::factory()->create([
            'serial_number' => 'TEST-TR111-001',
            'protocol_type' => 'tr069',
        ]);
    }

    public function test_get_all_parameters_returns_bbf_compliant_structure(): void
    {
        $result = $this->service->getAllParameters($this->device);

        $this->assertIsArray($result);
        
        $tr111Params = array_filter(array_keys($result), function($key) {
            return str_contains($key, 'ProximityDetection') || str_contains($key, 'Device.UPnP.') || str_contains($key, 'Device.LLDP.');
        });
        
        $this->assertNotEmpty($tr111Params, 'Should have TR-111 Proximity parameters');
    }

    public function test_discover_devices_via_upnp(): void
    {
        $result = $this->service->discoverDevices($this->device, 'upnp');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('devices', $result);
        $this->assertArrayHasKey('protocol', $result);
        $this->assertEquals('upnp', $result['protocol']);
    }

    public function test_discover_devices_via_lldp(): void
    {
        $result = $this->service->discoverDevices($this->device, 'lldp');

        $this->assertIsArray($result);
        $this->assertEquals('lldp', $result['protocol']);
    }

    public function test_discover_devices_via_mdns(): void
    {
        $result = $this->service->discoverDevices($this->device, 'mdns');

        $this->assertIsArray($result);
        $this->assertEquals('mdns', $result['protocol']);
    }

    public function test_get_network_topology(): void
    {
        $result = $this->service->getNetworkTopology($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('device_count', $result);
        $this->assertArrayHasKey('topology_tree', $result);
    }

    public function test_configure_proximity_detection(): void
    {
        $config = [
            'enabled' => true,
            'protocols' => ['upnp', 'lldp'],
            'scan_interval' => 60,
        ];

        $result = $this->service->configureProximityDetection($this->device, $config);

        $this->assertTrue($result['success']);
    }

    public function test_get_proximity_events(): void
    {
        $result = $this->service->getProximityEvents($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('events', $result);
    }

    public function test_trigger_device_scan(): void
    {
        $result = $this->service->triggerDeviceScan($this->device);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('scan_id', $result);
    }
}
