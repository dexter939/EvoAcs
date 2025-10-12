<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\CpeDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_devices_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/devices');

        $response->assertStatus(401);
    }

    public function test_list_devices_returns_paginated_results(): void
    {
        CpeDevice::factory()->count(15)->create();

        $response = $this->apiGet('/api/v1/devices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'serial_number',
                        'oui',
                        'product_class',
                        'manufacturer',
                        'status',
                        'protocol_type',
                        'last_inform'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'total',
                    'per_page'
                ]
            ]);

        $this->assertCount(10, $response->json('data')); // Default pagination
    }

    public function test_get_device_by_id_returns_device_details(): void
    {
        $device = CpeDevice::factory()->create([
            'serial_number' => 'TEST-DEVICE-001',
            'protocol_type' => 'tr069',
            'status' => 'online'
        ]);

        $response = $this->apiGet("/api/v1/devices/{$device->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $device->id,
                    'serial_number' => 'TEST-DEVICE-001',
                    'protocol_type' => 'tr069',
                    'status' => 'online'
                ]
            ]);
    }

    public function test_get_nonexistent_device_returns_404(): void
    {
        $response = $this->apiGet('/api/v1/devices/99999');

        $response->assertStatus(404);
    }

    public function test_create_device_with_valid_data(): void
    {
        $deviceData = [
            'serial_number' => 'NEW-DEVICE-123',
            'oui' => '00259E',
            'product_class' => 'IGD',
            'manufacturer' => 'Test Manufacturer',
            'protocol_type' => 'tr069',
            'status' => 'offline'
        ];

        $response = $this->apiPost('/api/v1/devices', $deviceData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'serial_number' => 'NEW-DEVICE-123',
                'oui' => '00259E'
            ]);

        $this->assertDatabaseHas('cpe_devices', [
            'serial_number' => 'NEW-DEVICE-123',
            'oui' => '00259E'
        ]);
    }

    public function test_create_device_validates_required_fields(): void
    {
        $response = $this->apiPost('/api/v1/devices', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['serial_number']);
    }

    public function test_create_device_prevents_duplicate_serial_number(): void
    {
        CpeDevice::factory()->create(['serial_number' => 'DUPLICATE-001']);

        $response = $this->apiPost('/api/v1/devices', [
            'serial_number' => 'DUPLICATE-001',
            'oui' => '00259E',
            'product_class' => 'IGD',
            'manufacturer' => 'Test'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['serial_number']);
    }

    public function test_update_device_modifies_existing_device(): void
    {
        $device = CpeDevice::factory()->create([
            'status' => 'offline',
            'manufacturer' => 'Old Manufacturer'
        ]);

        $response = $this->apiPut("/api/v1/devices/{$device->id}", [
            'status' => 'online',
            'manufacturer' => 'New Manufacturer'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => 'online',
                'manufacturer' => 'New Manufacturer'
            ]);

        $this->assertDatabaseHas('cpe_devices', [
            'id' => $device->id,
            'status' => 'online',
            'manufacturer' => 'New Manufacturer'
        ]);
    }

    public function test_delete_device_soft_deletes_device(): void
    {
        $device = CpeDevice::factory()->create();

        $response = $this->apiDelete("/api/v1/devices/{$device->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('cpe_devices', ['id' => $device->id]);
    }

    public function test_filter_devices_by_status(): void
    {
        CpeDevice::factory()->count(5)->create(['status' => 'online']);
        CpeDevice::factory()->count(3)->create(['status' => 'offline']);

        $response = $this->apiGet('/api/v1/devices?status=online');

        $response->assertStatus(200);
        
        $devices = $response->json('data');
        $this->assertCount(5, $devices);
        
        foreach ($devices as $device) {
            $this->assertEquals('online', $device['status']);
        }
    }

    public function test_filter_devices_by_protocol_type(): void
    {
        CpeDevice::factory()->count(7)->tr069()->create();
        CpeDevice::factory()->count(4)->tr369()->create();

        $response = $this->apiGet('/api/v1/devices?protocol_type=tr369');

        $response->assertStatus(200);
        
        $devices = $response->json('data');
        $this->assertCount(4, $devices);
        
        foreach ($devices as $device) {
            $this->assertEquals('tr369', $device['protocol_type']);
        }
    }

    public function test_search_devices_by_serial_number(): void
    {
        CpeDevice::factory()->create(['serial_number' => 'SEARCH-TEST-001']);
        CpeDevice::factory()->create(['serial_number' => 'SEARCH-TEST-002']);
        CpeDevice::factory()->create(['serial_number' => 'OTHER-123']);

        $response = $this->apiGet('/api/v1/devices?search=SEARCH-TEST');

        $response->assertStatus(200);
        
        $devices = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($devices));
    }
}
