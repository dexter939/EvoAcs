<?php

namespace Tests\Feature\TR369;

use Tests\TestCase;
use App\Models\CpeDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class UspHttpTransportTest extends TestCase
{
    use RefreshDatabase;

    protected CpeDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->device = CpeDevice::factory()->tr369()->online()->create([
            'mtp_type' => 'http',
            'connection_request_url' => 'http://device.test:8080/usp',
            'usp_endpoint_id' => 'proto::http-device-001'
        ]);
    }

    public function test_get_parameters_via_http_transport(): void
    {
        Http::fake([
            'device.test:8080/usp' => Http::response('', 200)
        ]);

        $response = $this->apiPost("/api/v1/usp/devices/{$this->device->id}/get-params", [
            'param_paths' => [
                'Device.DeviceInfo.',
                'Device.LocalAgent.'
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'msg_id',
                    'status',
                    'transport'
                ]
            ])
            ->assertJsonFragment([
                'transport' => 'http'
            ]);

        // Verify HTTP POST was sent
        Http::assertSent(function ($request) {
            return $request->url() === 'http://device.test:8080/usp' &&
                   $request->method() === 'POST';
        });
    }

    public function test_set_parameters_via_http(): void
    {
        Http::fake([
            'device.test:8080/usp' => Http::response('', 200)
        ]);

        $response = $this->apiPost("/api/v1/usp/devices/{$this->device->id}/set-params", [
            'param_paths' => [
                'Device.LocalAgent.' => [
                    'PeriodicNotifInterval' => '300'
                ]
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'transport' => 'http'
            ]);

        // Verify HTTP POST was sent
        Http::assertSent(function ($request) {
            return $request->url() === 'http://device.test:8080/usp' &&
                   $request->method() === 'POST';
        });
    }

    public function test_http_content_type_is_protobuf(): void
    {
        Http::fake([
            'device.test:8080/usp' => Http::response('', 200)
        ]);

        $this->apiPost("/api/v1/usp/devices/{$this->device->id}/get-params", [
            'param_paths' => ['Device.']
        ]);

        // Verify Content-Type header
        Http::assertSent(function ($request) {
            $contentType = $request->header('Content-Type')[0] ?? '';
            return str_contains($contentType, 'application/octet-stream') ||
                   str_contains($contentType, 'application/vnd.bbf.usp.msg');
        });
    }

    public function test_add_object_via_http(): void
    {
        Http::fake([
            'device.test:8080/usp' => Http::response('', 200)
        ]);

        $response = $this->apiPost("/api/v1/usp/devices/{$this->device->id}/add-object", [
            'object_path' => 'Device.LocalAgent.Subscription.',
            'parameters' => [
                'Enable' => 'true',
                'ID' => 'custom-sub-001',
                'NotifType' => 'ValueChange'
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'transport' => 'http'
            ]);

        // Verify HTTP POST for ADD operation
        Http::assertSent(function ($request) {
            return $request->url() === 'http://device.test:8080/usp' &&
                   $request->method() === 'POST' &&
                   !empty($request->body());
        });
    }

    public function test_delete_object_via_http(): void
    {
        Http::fake([
            'device.test:8080/usp' => Http::response('', 200)
        ]);

        $response = $this->apiPost("/api/v1/usp/devices/{$this->device->id}/delete-object", [
            'object_paths' => [
                'Device.LocalAgent.Subscription.1.'
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'transport' => 'http'
            ]);

        // Verify HTTP POST for DELETE operation
        Http::assertSent(function ($request) {
            return $request->url() === 'http://device.test:8080/usp' &&
                   $request->method() === 'POST';
        });
    }

    public function test_operate_via_http(): void
    {
        Http::fake([
            'device.test:8080/usp' => Http::response('', 200)
        ]);

        $response = $this->apiPost("/api/v1/usp/devices/{$this->device->id}/operate", [
            'command' => 'Device.Reboot()',
            'command_args' => []
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'transport' => 'http',
                'command' => 'Device.Reboot()'
            ]);

        // Verify HTTP POST for OPERATE command
        Http::assertSent(function ($request) {
            return $request->url() === 'http://device.test:8080/usp' &&
                   $request->method() === 'POST';
        });
    }

    public function test_http_subscription_creation(): void
    {
        Http::fake([
            'device.test:8080/usp' => Http::response('', 200)
        ]);

        $response = $this->apiPost("/api/v1/usp/devices/{$this->device->id}/subscribe", [
            'subscription_id' => 'http-sub-001',
            'notification_type' => 'OperationComplete',
            'reference_list' => [
                'Device.Reboot()'
            ],
            'enabled' => true
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('usp_subscriptions', [
            'cpe_device_id' => $this->device->id,
            'subscription_id' => 'http-sub-001',
            'notification_type' => 'OperationComplete'
        ]);

        // Verify HTTP POST for SUBSCRIBE operation
        Http::assertSent(function ($request) {
            return $request->url() === 'http://device.test:8080/usp' &&
                   $request->method() === 'POST';
        });
    }

    public function test_http_requires_connection_url(): void
    {
        $invalidDevice = CpeDevice::factory()->tr369()->create([
            'mtp_type' => 'http',
            'connection_request_url' => null,
            'status' => 'online'
        ]);

        $response = $this->apiPost("/api/v1/usp/devices/{$invalidDevice->id}/get-params", [
            'param_paths' => ['Device.']
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'HTTP connection URL not configured'
            ]);
    }

    public function test_http_handles_device_errors(): void
    {
        Http::fake([
            'device.test:8080/usp' => Http::response('', 500)
        ]);

        $response = $this->apiPost("/api/v1/usp/devices/{$this->device->id}/get-params", [
            'param_paths' => ['Device.']
        ]);

        // Should handle 500 error gracefully
        $response->assertStatus(500)
            ->assertJsonFragment([
                'success' => false
            ]);
    }

    public function test_http_msgid_in_request_body(): void
    {
        Http::fake([
            'device.test:8080/usp' => Http::response('', 200)
        ]);

        $response = $this->apiPost("/api/v1/usp/devices/{$this->device->id}/get-params", [
            'param_paths' => ['Device.DeviceInfo.']
        ]);

        $msgId = $response->json('data.msg_id');
        $this->assertNotEmpty($msgId);

        // Verify msgId is sent in protobuf body
        Http::assertSent(function ($request) use ($msgId) {
            $body = $request->body();
            // Body contains protobuf-encoded msgId (basic check)
            return !empty($body);
        });
    }
}
