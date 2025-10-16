<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\CpeDevice;
use App\Models\VoiceService;
use App\Models\SipProfile;
use App\Models\VoipLine;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VoipServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_voice_service(): void
    {
        $device = CpeDevice::factory()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/voice-services", [
            'service_type' => 'SIP',
            'service_name' => 'Primary VoIP',
            'enabled' => true,
            'bound_interface' => 'WAN'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['service_type', 'service_name', 'enabled']
            ]);

        $this->assertDatabaseHas('voice_services', [
            'cpe_device_id' => $device->id,
            'service_type' => 'SIP',
            'enabled' => true
        ]);
    }

    public function test_create_sip_profile(): void
    {
        $device = CpeDevice::factory()->create();
        
        $voiceService = VoiceService::create([
            'cpe_device_id' => $device->id,
            'service_type' => 'SIP',
            'service_name' => 'Test VoIP',
            'enabled' => true
        ]);

        $response = $this->apiPost("/api/v1/voice-services/{$voiceService->id}/sip-profiles", [
            'profile_name' => 'Main SIP Profile',
            'proxy_server' => 'sip.provider.com',
            'proxy_port' => 5060,
            'registrar_server' => 'sip.provider.com',
            'registrar_port' => 5060,
            'transport_protocol' => 'UDP'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['profile_name', 'proxy_server', 'transport_protocol']
            ]);
    }

    public function test_provision_voip_line(): void
    {
        $device = CpeDevice::factory()->create();
        
        $voiceService = VoiceService::create([
            'cpe_device_id' => $device->id,
            'service_type' => 'SIP',
            'enabled' => true
        ]);

        $sipProfile = SipProfile::create([
            'voice_service_id' => $voiceService->id,
            'profile_name' => 'Test Profile',
            'proxy_server' => 'sip.test.com'
        ]);

        $response = $this->apiPost("/api/v1/voice-services/{$voiceService->id}/voip-lines", [
            'line_number' => 1,
            'sip_profile_id' => $sipProfile->id,
            'sip_uri' => 'sip:user@test.com',
            'auth_username' => 'testuser',
            'auth_password' => 'password123',
            'enabled' => true
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['line_number', 'sip_uri', 'enabled']
            ]);
    }

    public function test_get_voice_statistics(): void
    {
        $device = CpeDevice::factory()->create();
        
        VoiceService::create([
            'cpe_device_id' => $device->id,
            'service_type' => 'SIP',
            'enabled' => true
        ]);

        VoiceService::create([
            'cpe_device_id' => $device->id,
            'service_type' => 'MGCP',
            'enabled' => false
        ]);

        $response = $this->apiGet("/api/v1/voice-services/stats/overview");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_services',
                'enabled_services',
                'by_type',
                'total_lines'
            ]);
    }

    public function test_create_voice_service_validates_service_type(): void
    {
        $device = CpeDevice::factory()->create([
            'protocol_type' => 'tr069',
            'mtp_type' => null,
            'status' => 'online'
        ]);

        $response = $this->apiPost("/api/v1/devices/{$device->id}/voice-services", [
            'service_type' => 'INVALID',
            'service_name' => 'Test'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service_type']);
    }

    public function test_create_sip_profile_validates_required_fields(): void
    {
        $device = CpeDevice::factory()->create([
            'protocol_type' => 'tr069',
            'mtp_type' => null,
            'status' => 'online'
        ]);
        
        $voiceService = VoiceService::create([
            'cpe_device_id' => $device->id,
            'service_type' => 'SIP',
            'enabled' => true
        ]);

        $response = $this->apiPost("/api/v1/voice-services/{$voiceService->id}/sip-profiles", [
            'profile_name' => 'Test'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['proxy_server']);
    }

    public function test_provision_voip_line_validates_sip_profile_exists(): void
    {
        $device = CpeDevice::factory()->create([
            'protocol_type' => 'tr069',
            'mtp_type' => null,
            'status' => 'online'
        ]);
        
        $voiceService = VoiceService::create([
            'cpe_device_id' => $device->id,
            'service_type' => 'SIP',
            'enabled' => true
        ]);

        $response = $this->apiPost("/api/v1/voice-services/{$voiceService->id}/voip-lines", [
            'line_number' => 1,
            'sip_profile_id' => 99999,
            'sip_uri' => 'sip:user@test.com',
            'enabled' => true
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sip_profile_id']);
    }
}
