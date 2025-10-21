<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TR140Service;
use App\Models\CpeDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TR140ServiceTest extends TestCase
{
    use RefreshDatabase;

    private TR140Service $service;
    private CpeDevice $device;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TR140Service::class);
        
        $this->device = CpeDevice::factory()->create([
            'serial_number' => 'TEST-TR140-001',
            'protocol_type' => 'tr069',
        ]);
    }

    public function test_get_all_parameters_returns_bbf_compliant_structure(): void
    {
        $result = $this->service->getAllParameters($this->device);

        $this->assertIsArray($result);
        
        $tr140Params = array_filter(array_keys($result), function($key) {
            return str_contains($key, 'StorageService') || str_contains($key, 'Device.Services.StorageService.');
        });
        
        $this->assertNotEmpty($tr140Params, 'Should have TR-140 Storage parameters');
    }

    public function test_configure_smb_share(): void
    {
        $config = [
            'name' => 'SharedFolder',
            'path' => '/storage/share1',
            'enabled' => true,
        ];

        $result = $this->service->configureSmbShare($this->device, $config);

        $this->assertTrue($result['success']);
    }

    public function test_configure_nfs_export(): void
    {
        $config = [
            'path' => '/storage/nfs_export',
            'allowed_clients' => '192.168.1.0/24',
        ];

        $result = $this->service->configureNfsExport($this->device, $config);

        $this->assertTrue($result['success']);
    }

    public function test_set_storage_quota(): void
    {
        $result = $this->service->setStorageQuota($this->device, 'user1', 10 * 1024 * 1024);

        $this->assertTrue($result['success']);
    }

    public function test_get_storage_capacity(): void
    {
        $result = $this->service->getStorageCapacity($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('used', $result);
        $this->assertArrayHasKey('available', $result);
    }

    public function test_configure_raid(): void
    {
        $config = [
            'level' => 'RAID1',
            'disks' => ['/dev/sda', '/dev/sdb'],
        ];

        $result = $this->service->configureRaid($this->device, $config);

        $this->assertTrue($result['success']);
    }

    public function test_get_smart_disk_status(): void
    {
        $result = $this->service->getSmartDiskStatus($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('disks', $result);
    }

    public function test_schedule_backup(): void
    {
        $config = [
            'enabled' => true,
            'schedule' => '0 2 * * *',
            'destination' => '/backup',
        ];

        $result = $this->service->scheduleBackup($this->device, $config);

        $this->assertTrue($result['success']);
    }
}
