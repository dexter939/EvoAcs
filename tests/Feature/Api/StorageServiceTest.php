<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\CpeDevice;
use App\Models\StorageService;
use App\Models\LogicalVolume;
use App\Models\FileServer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StorageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_storage_service(): void
    {
        $device = CpeDevice::factory()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/storage-services", [
            'service_name' => 'Main NAS',
            'storage_type' => 'NAS',
            'enabled' => true,
            'total_capacity_mb' => 500000
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['service_name', 'storage_type', 'total_capacity_mb']
            ]);

        $this->assertDatabaseHas('storage_services', [
            'cpe_device_id' => $device->id,
            'service_name' => 'Main NAS',
            'storage_type' => 'NAS'
        ]);
    }

    public function test_create_logical_volume(): void
    {
        $device = CpeDevice::factory()->create();
        
        $storageService = StorageService::create([
            'cpe_device_id' => $device->id,
            'service_name' => 'Test Storage',
            'storage_type' => 'NAS',
            'enabled' => true,
            'total_capacity_mb' => 1000000
        ]);

        $response = $this->apiPost("/api/v1/storage-services/{$storageService->id}/volumes", [
            'volume_name' => 'Data Volume',
            'filesystem_type' => 'ext4',
            'capacity_mb' => 250000,
            'raid_level' => 'RAID5',
            'encryption_enabled' => true
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['volume_name', 'filesystem_type', 'raid_level']
            ]);

        $this->assertDatabaseHas('logical_volumes', [
            'storage_service_id' => $storageService->id,
            'volume_name' => 'Data Volume',
            'raid_level' => 'RAID5'
        ]);
    }

    public function test_create_file_server(): void
    {
        $device = CpeDevice::factory()->create();
        
        $storageService = StorageService::create([
            'cpe_device_id' => $device->id,
            'service_name' => 'Test Storage',
            'storage_type' => 'NAS',
            'enabled' => true
        ]);

        $response = $this->apiPost("/api/v1/storage-services/{$storageService->id}/file-servers", [
            'server_name' => 'SMB Share',
            'protocol' => 'SMB',
            'enabled' => true,
            'share_path' => '/mnt/data',
            'access_control' => ['user1', 'user2']
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['server_name', 'protocol', 'share_path']
            ]);

        $this->assertDatabaseHas('file_servers', [
            'storage_service_id' => $storageService->id,
            'server_name' => 'SMB Share',
            'protocol' => 'SMB'
        ]);
    }

    public function test_storage_statistics(): void
    {
        $device = CpeDevice::factory()->create();
        
        StorageService::create([
            'cpe_device_id' => $device->id,
            'service_name' => 'NAS 1',
            'storage_type' => 'NAS',
            'enabled' => true,
            'total_capacity_mb' => 500000,
            'used_capacity_mb' => 250000
        ]);

        StorageService::create([
            'cpe_device_id' => $device->id,
            'service_name' => 'NAS 2',
            'storage_type' => 'NAS',
            'enabled' => true,
            'total_capacity_mb' => 1000000,
            'used_capacity_mb' => 500000
        ]);

        $response = $this->apiGet("/api/v1/storage-services/stats/overview");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_services',
                'total_capacity_mb',
                'total_used_mb',
                'usage_percentage'
            ]);
    }

    public function test_volume_validates_capacity(): void
    {
        $device = CpeDevice::factory()->create();
        
        $storageService = StorageService::create([
            'cpe_device_id' => $device->id,
            'service_name' => 'Test',
            'storage_type' => 'NAS',
            'enabled' => true,
            'total_capacity_mb' => 100000
        ]);

        $response = $this->apiPost("/api/v1/storage-services/{$storageService->id}/volumes", [
            'volume_name' => 'Too Large',
            'filesystem_type' => 'ext4',
            'capacity_mb' => 200000
        ]);

        $response->assertStatus(422);
    }

    public function test_file_server_validates_protocol(): void
    {
        $device = CpeDevice::factory()->create([
            'protocol_type' => 'tr069',
            'mtp_type' => null,
            'status' => 'online'
        ]);
        
        $storageService = StorageService::create([
            'cpe_device_id' => $device->id,
            'service_name' => 'Test',
            'storage_type' => 'NAS',
            'enabled' => true
        ]);

        $response = $this->apiPost("/api/v1/storage-services/{$storageService->id}/file-servers", [
            'server_name' => 'Test Server',
            'protocol' => 'INVALID',
            'enabled' => true
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['protocol']);
    }
}
