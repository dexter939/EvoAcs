<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TR135Service;
use App\Models\CpeDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TR135ServiceTest extends TestCase
{
    use RefreshDatabase;

    private TR135Service $service;
    private CpeDevice $device;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TR135Service::class);
        
        $this->device = CpeDevice::factory()->create([
            'serial_number' => 'TEST-TR135-001',
            'protocol_type' => 'tr069',
        ]);
    }

    public function test_get_all_parameters_returns_bbf_compliant_structure(): void
    {
        $result = $this->service->getAllParameters($this->device);

        $this->assertIsArray($result);
        
        $tr135Params = array_filter(array_keys($result), function($key) {
            return str_contains($key, 'STBService') || str_contains($key, 'Device.Services.STBService.');
        });
        
        $this->assertNotEmpty($tr135Params, 'Should have TR-135 STB parameters');
    }

    public function test_get_epg_configuration(): void
    {
        $result = $this->service->getEpgConfiguration($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('enabled', $result);
        $this->assertArrayHasKey('update_interval', $result);
    }

    public function test_get_pvr_storage_info(): void
    {
        $result = $this->service->getPvrStorageInfo($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_capacity', $result);
        $this->assertArrayHasKey('used_space', $result);
        $this->assertArrayHasKey('available_space', $result);
    }

    public function test_configure_conditional_access(): void
    {
        $config = [
            'cas_enabled' => true,
            'drm_system' => 'Widevine',
        ];

        $result = $this->service->configureConditionalAccess($this->device, $config);

        $this->assertTrue($result['success']);
    }

    public function test_get_channel_list(): void
    {
        $result = $this->service->getChannelList($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('channels', $result);
    }

    public function test_configure_multi_screen(): void
    {
        $result = $this->service->configureMultiScreen($this->device, [
            'enabled' => true,
            'max_screens' => 4,
        ]);

        $this->assertTrue($result['success']);
    }

    public function test_get_content_delivery_stats(): void
    {
        $result = $this->service->getContentDeliveryStats($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('cdn_enabled', $result);
        $this->assertArrayHasKey('buffer_health', $result);
    }
}
