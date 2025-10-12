<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\CpeDevice;
use App\Models\StbService;
use App\Models\StreamingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StbServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_provision_stb_service(): void
    {
        $device = CpeDevice::factory()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/stb-services", [
            'service_type' => 'IPTV',
            'frontend_type' => 'IP',
            'streaming_protocol' => 'HLS',
            'server_url' => 'http://iptv.example.com',
            'server_port' => 8080,
            'channel_list' => [
                ['id' => 1, 'name' => 'Channel 1', 'url' => 'http://stream1.m3u8'],
                ['id' => 2, 'name' => 'Channel 2', 'url' => 'http://stream2.m3u8']
            ]
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'service' => ['service_type', 'frontend_type', 'streaming_protocol']
            ]);

        $this->assertDatabaseHas('stb_services', [
            'cpe_device_id' => $device->id,
            'service_type' => 'IPTV',
            'streaming_protocol' => 'HLS'
        ]);
    }

    public function test_provision_validates_required_fields(): void
    {
        $device = CpeDevice::factory()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/stb-services", [
            'service_type' => 'IPTV'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frontend_type', 'streaming_protocol']);
    }

    public function test_start_streaming_session(): void
    {
        $device = CpeDevice::factory()->create();
        
        $service = StbService::create([
            'cpe_device_id' => $device->id,
            'service_type' => 'IPTV',
            'frontend_type' => 'IP',
            'streaming_protocol' => 'RTSP',
            'enabled' => true
        ]);

        $response = $this->apiPost("/api/v1/stb-services/{$service->id}/sessions", [
            'channel_name' => 'HBO',
            'content_url' => 'rtsp://server.com/hbo'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'session' => ['session_id', 'channel_name', 'status', 'started_at']
            ]);

        $this->assertDatabaseHas('streaming_sessions', [
            'stb_service_id' => $service->id,
            'channel_name' => 'HBO',
            'status' => 'active'
        ]);
    }

    public function test_update_session_qos(): void
    {
        $device = CpeDevice::factory()->create();
        
        $service = StbService::create([
            'cpe_device_id' => $device->id,
            'service_type' => 'IPTV',
            'frontend_type' => 'DVB-T',
            'streaming_protocol' => 'RTP',
            'enabled' => true
        ]);

        $session = StreamingSession::create([
            'stb_service_id' => $service->id,
            'session_id' => 'test-session-' . uniqid(),
            'channel_name' => 'Test Channel',
            'status' => 'active',
            'started_at' => now()
        ]);

        $response = $this->apiRequest('PATCH', "/api/v1/streaming-sessions/{$session->id}/qos", [
            'bitrate' => 5000,
            'packet_loss' => 0,
            'jitter' => 2.5
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'session' => ['bitrate', 'packet_loss', 'jitter']
            ]);

        $this->assertDatabaseHas('streaming_sessions', [
            'id' => $session->id,
            'bitrate' => 5000,
            'packet_loss' => 0
        ]);
    }

    public function test_list_active_sessions(): void
    {
        $device = CpeDevice::factory()->create();
        
        $service = StbService::create([
            'cpe_device_id' => $device->id,
            'service_type' => 'VoD',
            'frontend_type' => 'IP',
            'streaming_protocol' => 'DASH',
            'enabled' => true
        ]);

        StreamingSession::create([
            'stb_service_id' => $service->id,
            'session_id' => 'active-1',
            'status' => 'active',
            'started_at' => now()
        ]);

        StreamingSession::create([
            'stb_service_id' => $service->id,
            'session_id' => 'stopped-1',
            'status' => 'stopped',
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour()
        ]);

        $sessions = StreamingSession::where('stb_service_id', $service->id)
            ->where('status', 'active')
            ->get();

        $this->assertCount(1, $sessions);
    }

    protected function apiRequest($method, $uri, $data = [])
    {
        return $this->json($method, $uri, $data, $this->apiHeaders());
    }
}
