<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\FirmwareVersion;
use App\Models\CpeDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FirmwareManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_list_firmware_versions(): void
    {
        FirmwareVersion::factory()->count(5)->create();

        $response = $this->apiGet('/api/v1/firmware');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'version',
                        'model',
                        'file_size',
                        'checksum',
                        'is_active',
                        'release_date'
                    ]
                ]
            ]);
    }

    public function test_get_firmware_version_details(): void
    {
        $firmware = FirmwareVersion::factory()->create([
            'version' => 'v2.5.0',
            'model' => 'IGD-2000'
        ]);

        $response = $this->apiGet("/api/v1/firmware/{$firmware->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'version' => 'v2.5.0',
                'model' => 'IGD-2000'
            ]);
    }

    public function test_upload_firmware_file(): void
    {
        $file = UploadedFile::fake()->create('firmware.bin', 5000); // 5MB

        $response = $this->apiPost('/api/v1/firmware', [
            'version' => 'v3.0.0',
            'model' => 'IGD-3000',
            'file' => $file,
            'description' => 'New firmware release'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'version',
                    'model',
                    'file_path',
                    'checksum'
                ]
            ]);

        $this->assertDatabaseHas('firmware_versions', [
            'version' => 'v3.0.0',
            'model' => 'IGD-3000'
        ]);

        Storage::disk('local')->assertExists('firmware/' . $file->hashName());
    }

    public function test_upload_firmware_validates_file_type(): void
    {
        $file = UploadedFile::fake()->create('firmware.txt', 100);

        $response = $this->apiPost('/api/v1/firmware', [
            'version' => 'v3.0.0',
            'model' => 'IGD-3000',
            'file' => $file
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_update_firmware_version_metadata(): void
    {
        $firmware = FirmwareVersion::factory()->create([
            'is_active' => false
        ]);

        $response = $this->apiPut("/api/v1/firmware/{$firmware->id}", [
            'is_active' => true,
            'description' => 'Updated description'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'is_active' => true
            ]);

        $this->assertDatabaseHas('firmware_versions', [
            'id' => $firmware->id,
            'is_active' => true
        ]);
    }

    public function test_delete_firmware_version(): void
    {
        $firmware = FirmwareVersion::factory()->create();

        $response = $this->apiDelete("/api/v1/firmware/{$firmware->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('firmware_versions', [
            'id' => $firmware->id
        ]);
    }

    public function test_deploy_firmware_to_device(): void
    {
        $device = CpeDevice::factory()->online()->create([
            'model_name' => 'IGD-2000'
        ]);

        $firmware = FirmwareVersion::factory()->active()->create([
            'model' => 'IGD-2000',
            'version' => 'v2.0.0'
        ]);

        $response = $this->apiPost("/api/v1/firmware/{$firmware->id}/deploy", [
            'device_ids' => [$device->id]
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'deployment_id',
                    'devices_count',
                    'status'
                ]
            ]);

        $this->assertDatabaseHas('firmware_deployments', [
            'firmware_version_id' => $firmware->id,
            'cpe_device_id' => $device->id
        ]);
    }

    public function test_deploy_firmware_validates_device_model_compatibility(): void
    {
        $device = CpeDevice::factory()->create([
            'model_name' => 'IGD-1000'
        ]);

        $firmware = FirmwareVersion::factory()->create([
            'model' => 'IGD-2000', // Different model
            'version' => 'v2.0.0'
        ]);

        $response = $this->apiPost("/api/v1/firmware/{$firmware->id}/deploy", [
            'device_ids' => [$device->id]
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Firmware model does not match device model'
            ]);
    }

    public function test_deploy_firmware_requires_online_devices(): void
    {
        $device = CpeDevice::factory()->offline()->create([
            'model_name' => 'IGD-2000'
        ]);

        $firmware = FirmwareVersion::factory()->create([
            'model' => 'IGD-2000'
        ]);

        $response = $this->apiPost("/api/v1/firmware/{$firmware->id}/deploy", [
            'device_ids' => [$device->id]
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'All devices must be online for deployment'
            ]);
    }

    public function test_filter_firmware_by_model(): void
    {
        FirmwareVersion::factory()->count(3)->create(['model' => 'IGD-2000']);
        FirmwareVersion::factory()->count(2)->create(['model' => 'IGD-3000']);

        $response = $this->apiGet('/api/v1/firmware?model=IGD-2000');

        $response->assertStatus(200);
        
        $firmwares = $response->json('data');
        $this->assertCount(3, $firmwares);
        
        foreach ($firmwares as $firmware) {
            $this->assertEquals('IGD-2000', $firmware['model']);
        }
    }

    public function test_list_active_firmware_versions_only(): void
    {
        FirmwareVersion::factory()->count(5)->active()->create();
        FirmwareVersion::factory()->count(3)->create(['is_active' => false]);

        $response = $this->apiGet('/api/v1/firmware?is_active=true');

        $response->assertStatus(200);
        
        $firmwares = $response->json('data');
        $this->assertCount(5, $firmwares);
    }
}
