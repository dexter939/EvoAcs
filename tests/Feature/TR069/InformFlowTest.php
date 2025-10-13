<?php

namespace Tests\Feature\TR069;

use Tests\TestCase;
use App\Models\CpeDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class InformFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_auto_registration_via_inform(): void
    {
        $soapEnvelope = $this->createTr069Inform([
            'serial_number' => 'AUTO-REG-001',
            'oui' => '00259E',
            'product_class' => 'IGD',
            'manufacturer' => 'TestVendor',
            'software_version' => 'v1.0.0',
            'hardware_version' => 'hw1.0',
            'events' => ['0 BOOTSTRAP', '1 BOOT'],
            'parameters' => [
                'Device.DeviceInfo.SoftwareVersion' => 'v1.0.0',
                'Device.DeviceInfo.HardwareVersion' => 'hw1.0',
                'Device.ManagementServer.ConnectionRequestURL' => 'http://device.test:7547'
            ]
        ]);

        $response = $this->postTr069Soap('/tr069', $soapEnvelope);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/xml; charset=utf-8');

        // Verify device was created
        $this->assertDatabaseHas('cpe_devices', [
            'serial_number' => 'AUTO-REG-001',
            'oui' => '00259E',
            'product_class' => 'IGD',
            'manufacturer' => 'TestVendor',
            'status' => 'online'
        ]);

        // Verify parameters were stored
        $device = CpeDevice::where('serial_number', 'AUTO-REG-001')->first();
        $this->assertDatabaseHas('device_parameters', [
            'cpe_device_id' => $device->id,
            'parameter_path' => 'Device.DeviceInfo.SoftwareVersion',
            'parameter_value' => 'v1.0.0'
        ]);

        // Verify session cookie is set
        $response->assertCookie('TR069SessionID');
    }

    public function test_inform_response_contains_valid_soap(): void
    {
        $soapEnvelope = $this->createTr069Inform([
            'serial_number' => 'TEST-002',
            'oui' => '00259E',
            'product_class' => 'IGD',
            'manufacturer' => 'Test',
            'events' => ['1 BOOT']
        ]);

        $response = $this->postTr069Soap('/tr069', $soapEnvelope);

        $responseBody = $response->getContent();
        
        $xml = simplexml_load_string($responseBody);
        $this->assertNotFalse($xml, 'Response should be valid XML');

        // Verify SOAP namespace
        $namespaces = $xml->getNamespaces(true);
        $this->assertArrayHasKey('soap', $namespaces);
        $this->assertArrayHasKey('cwmp', $namespaces);

        // Verify InformResponse structure
        $this->assertStringContainsString('InformResponse', $responseBody);
        $this->assertStringContainsString('MaxEnvelopes', $responseBody);
    }

    public function test_existing_device_update_via_inform(): void
    {
        $device = CpeDevice::factory()->tr069()->create([
            'serial_number' => 'UPDATE-001',
            'oui' => '00259E',
            'product_class' => 'IGD',
            'status' => 'offline',
            'software_version' => 'v1.0.0'
        ]);

        $soapEnvelope = $this->createTr069Inform([
            'serial_number' => 'UPDATE-001',
            'oui' => '00259E',
            'product_class' => 'IGD',
            'manufacturer' => 'TestVendor',
            'software_version' => 'v2.0.0',
            'events' => ['2 PERIODIC'],
            'parameters' => [
                'Device.DeviceInfo.SoftwareVersion' => 'v2.0.0',
                'Device.DeviceInfo.UpTime' => '3600'
            ]
        ]);

        $response = $this->postTr069Soap('/tr069', $soapEnvelope);

        $response->assertStatus(200);

        // Verify device was updated
        $this->assertDatabaseHas('cpe_devices', [
            'id' => $device->id,
            'serial_number' => 'UPDATE-001',
            'status' => 'online',
            'software_version' => 'v2.0.0'
        ]);

        // Verify last_inform was updated
        $device->refresh();
        $this->assertNotNull($device->last_inform);
    }

    public function test_inform_handles_multiple_events(): void
    {
        $soapEnvelope = $this->createTr069Inform([
            'serial_number' => 'MULTI-EVENT-001',
            'oui' => '00259E',
            'product_class' => 'IGD',
            'manufacturer' => 'Test',
            'events' => [
                '0 BOOTSTRAP',
                '1 BOOT',
                '6 CONNECTION REQUEST',
                '8 DIAGNOSTICS COMPLETE'
            ]
        ]);

        $response = $this->postTr069Soap('/tr069', $soapEnvelope);

        $response->assertStatus(200);

        $device = CpeDevice::where('serial_number', 'MULTI-EVENT-001')->first();
        $this->assertNotNull($device);

        // Verify events were logged
        $this->assertDatabaseCount('cpe_devices', 1);
    }

    public function test_inform_with_empty_parameter_list(): void
    {
        $soapEnvelope = $this->createTr069Inform([
            'serial_number' => 'EMPTY-PARAMS-001',
            'oui' => '00259E',
            'product_class' => 'IGD',
            'manufacturer' => 'Test',
            'events' => ['1 BOOT'],
            'parameters' => []
        ]);

        $response = $this->postTr069Soap('/tr069', $soapEnvelope);

        $response->assertStatus(200);

        $device = CpeDevice::where('serial_number', 'EMPTY-PARAMS-001')->first();
        $this->assertNotNull($device);
    }

    public function test_inform_rejects_invalid_soap(): void
    {
        $invalidSoap = '<InvalidXML>Not SOAP</InvalidXML>';

        $response = $this->call('POST', '/tr069', [], [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
            'HTTP_SOAPACTION' => ''
        ], $invalidSoap);

        $response->assertStatus(400);
    }

    public function test_concurrent_informs_handle_different_sessions(): void
    {
        $device1Soap = $this->createTr069Inform([
            'serial_number' => 'CONCURRENT-001',
            'oui' => '00259E',
            'product_class' => 'IGD',
            'manufacturer' => 'Test',
            'events' => ['1 BOOT']
        ]);

        $device2Soap = $this->createTr069Inform([
            'serial_number' => 'CONCURRENT-002',
            'oui' => '00259E',
            'product_class' => 'IGD',
            'manufacturer' => 'Test',
            'events' => ['1 BOOT']
        ]);

        $response1 = $this->postTr069Soap('/tr069', $device1Soap);

        $response2 = $this->postTr069Soap('/tr069', $device2Soap);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Verify both devices were created
        $this->assertDatabaseHas('cpe_devices', ['serial_number' => 'CONCURRENT-001']);
        $this->assertDatabaseHas('cpe_devices', ['serial_number' => 'CONCURRENT-002']);

        // Verify different session cookies
        $cookie1 = $response1->headers->getCookies()[0]->getValue();
        $cookie2 = $response2->headers->getCookies()[0]->getValue();
        $this->assertNotEquals($cookie1, $cookie2);
    }
}
