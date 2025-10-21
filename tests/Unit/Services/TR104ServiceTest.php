<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TR104Service;
use App\Models\CpeDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TR104ServiceTest extends TestCase
{
    use RefreshDatabase;

    private TR104Service $service;
    private CpeDevice $device;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TR104Service::class);
        
        $this->device = CpeDevice::factory()->create([
            'serial_number' => 'TEST-TR104-001',
            'protocol_type' => 'tr069',
        ]);
    }

    public function test_get_all_parameters_returns_bbf_compliant_structure(): void
    {
        $result = $this->service->getAllParameters($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('VoiceService', $result);
        $this->assertArrayHasKey('Device.Services.VoiceService.1.VoiceProfile.1.Enable', $result);
    }

    public function test_get_sip_profile_configuration(): void
    {
        $result = $this->service->getSipProfiles($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('profiles', $result);
        $this->assertGreaterThan(0, $result['total']);
    }

    public function test_get_codec_list(): void
    {
        $result = $this->service->getCodecList($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('codecs', $result);
        
        foreach ($result['codecs'] as $codec) {
            $this->assertArrayHasKey('name', $codec);
            $this->assertArrayHasKey('enabled', $codec);
            $this->assertArrayHasKey('priority', $codec);
        }
    }

    public function test_get_call_statistics(): void
    {
        $result = $this->service->getCallStatistics($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_calls', $result);
        $this->assertArrayHasKey('successful_calls', $result);
        $this->assertArrayHasKey('failed_calls', $result);
    }

    public function test_configure_sip_account(): void
    {
        $config = [
            'username' => 'test_user',
            'password' => 'test_pass',
            'domain' => 'sip.example.com',
            'proxy' => 'proxy.example.com',
        ];

        $result = $this->service->configureSipAccount($this->device, 1, $config);

        $this->assertTrue($result['success']);
        $this->assertEquals('SIP account configured', $result['message']);
    }

    public function test_enable_qos_for_voip(): void
    {
        $result = $this->service->enableQoS($this->device, [
            'dscp' => 46,
            'priority' => 6,
        ]);

        $this->assertTrue($result['success']);
    }

    public function test_get_emergency_calling_status(): void
    {
        $result = $this->service->getEmergencyCallingStatus($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('e911_enabled', $result);
        $this->assertIsBool($result['e911_enabled']);
    }
}
