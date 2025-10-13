<?php

namespace Tests\Feature\TR069;

use Tests\TestCase;
use App\Models\CpeDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class ConnectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_connection_request_sends_http_to_device(): void
    {
        Http::fake([
            'device.test:7547' => Http::response('', 200)
        ]);

        $device = CpeDevice::factory()->tr069()->online()->create([
            'connection_request_url' => 'http://device.test:7547',
            'connection_request_username' => 'admin',
            'connection_request_password' => 'password123'
        ]);

        $response = $this->apiPost("/api/v1/devices/{$device->id}/connection-request");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'status',
                    'message'
                ]
            ]);

        // Verify HTTP request was made
        Http::assertSent(function ($request) {
            return $request->url() === 'http://device.test:7547' &&
                   $request->hasHeader('Authorization');
        });
    }

    public function test_connection_request_handles_digest_authentication(): void
    {
        Http::fake([
            'device.test:7547' => Http::sequence()
                ->push('', 401, [
                    'WWW-Authenticate' => 'Digest realm="TR-069", nonce="abc123", qop="auth"'
                ])
                ->push('', 200)
        ]);

        $device = CpeDevice::factory()->tr069()->create([
            'connection_request_url' => 'http://device.test:7547',
            'connection_request_username' => 'admin',
            'connection_request_password' => 'password123',
            'status' => 'online',
            'auth_method' => 'digest'
        ]);

        $response = $this->apiPost("/api/v1/devices/{$device->id}/connection-request");

        // Should handle 401 and retry with digest auth
        $response->assertStatus(200);

        // Verify two requests were made
        Http::assertSentCount(2);

        // Verify first request has no Digest header (initial attempt)
        $requests = Http::recorded();
        $firstRequest = $requests[0][0];
        $firstAuthHeader = $firstRequest->header('Authorization')[0] ?? '';
        $this->assertFalse(str_starts_with($firstAuthHeader, 'Digest '), 'First request should not have Digest auth');

        // Verify second request has Digest Authorization (retry)
        $secondRequest = $requests[1][0];
        $secondAuthHeader = $secondRequest->header('Authorization')[0] ?? '';
        $this->assertTrue(str_starts_with($secondAuthHeader, 'Digest '), 'Second request must have Digest auth header');
    }

    public function test_connection_request_fails_for_offline_device(): void
    {
        $device = CpeDevice::factory()->tr069()->offline()->create([
            'connection_request_url' => 'http://device.test:7547'
        ]);

        $response = $this->apiPost("/api/v1/devices/{$device->id}/connection-request");

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Device must be online'
            ]);
    }

    public function test_connection_request_requires_url_configured(): void
    {
        $device = CpeDevice::factory()->tr069()->online()->create([
            'connection_request_url' => null
        ]);

        $response = $this->apiPost("/api/v1/devices/{$device->id}/connection-request");

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Connection Request URL not configured'
            ]);
    }

    public function test_connection_request_triggers_device_inform(): void
    {
        Http::fake([
            'device.test:7547' => Http::response('', 200)
        ]);

        $device = CpeDevice::factory()->tr069()->online()->create([
            'serial_number' => 'CR-TEST-001',
            'connection_request_url' => 'http://device.test:7547',
            'connection_request_username' => 'admin',
            'connection_request_password' => 'pass'
        ]);

        // Send connection request
        $crResponse = $this->apiPost("/api/v1/devices/{$device->id}/connection-request");
        $crResponse->assertStatus(200);

        // Simulate device responding with Inform
        $informSoap = $this->createTr069Inform([
            'serial_number' => 'CR-TEST-001',
            'oui' => $device->oui,
            'product_class' => $device->product_class,
            'manufacturer' => $device->manufacturer,
            'events' => ['6 CONNECTION REQUEST']
        ]);

        $informResponse = $this->postTr069Soap('/tr069', $informSoap);

        $informResponse->assertStatus(200);

        // Verify device last_inform updated
        $device->refresh();
        $this->assertNotNull($device->last_inform);
    }

    public function test_connection_request_handles_network_errors(): void
    {
        Http::fake([
            'device.test:7547' => Http::response('', 500)
        ]);

        $device = CpeDevice::factory()->tr069()->online()->create([
            'connection_request_url' => 'http://device.test:7547',
            'connection_request_username' => 'admin',
            'connection_request_password' => 'pass'
        ]);

        $response = $this->apiPost("/api/v1/devices/{$device->id}/connection-request");

        $response->assertStatus(500)
            ->assertJsonFragment([
                'success' => false
            ]);
    }

    public function test_connection_request_supports_basic_auth(): void
    {
        Http::fake([
            'device.test:7547' => Http::response('', 200)
        ]);

        $device = CpeDevice::factory()->tr069()->online()->create([
            'connection_request_url' => 'http://device.test:7547',
            'connection_request_username' => 'admin',
            'connection_request_password' => 'basicpass',
            'auth_method' => 'basic'
        ]);

        $response = $this->apiPost("/api/v1/devices/{$device->id}/connection-request");

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            $authHeader = $request->header('Authorization')[0] ?? '';
            return str_starts_with($authHeader, 'Basic ');
        });
    }
}
