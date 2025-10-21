<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FemtocellManagementService;
use App\Models\CpeDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FemtocellManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private FemtocellManagementService $service;
    private CpeDevice $device;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FemtocellManagementService::class);
        
        $this->device = CpeDevice::factory()->create([
            'serial_number' => 'TEST-TR262-001',
            'protocol_type' => 'tr069',
        ]);
    }

    public function test_get_all_parameters_returns_bbf_compliant_structure(): void
    {
        $result = $this->service->getAllParameters($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('FAP', $result);
        $this->assertArrayHasKey('Device.FAP.GPS.ScanOnBoot', $result);
    }

    public function test_configure_son_self_config(): void
    {
        $config = [
            'enabled' => true,
            'pci_selection' => 'auto',
        ];

        $result = $this->service->configureSonSelfConfig($this->device, $config);

        $this->assertTrue($result['success']);
    }

    public function test_configure_son_self_optimization(): void
    {
        $config = [
            'enabled' => true,
            'optimization_interval' => 300,
        ];

        $result = $this->service->configureSonSelfOptimization($this->device, $config);

        $this->assertTrue($result['success']);
    }

    public function test_get_icic_configuration(): void
    {
        $result = $this->service->getIcicConfiguration($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('enabled', $result);
        $this->assertArrayHasKey('power_control', $result);
    }

    public function test_get_handover_parameters(): void
    {
        $result = $this->service->getHandoverParameters($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('a3_offset', $result);
        $this->assertArrayHasKey('hysteresis', $result);
    }

    public function test_get_performance_kpis(): void
    {
        $result = $this->service->getPerformanceKpis($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('throughput', $result);
        $this->assertArrayHasKey('connected_ues', $result);
        $this->assertArrayHasKey('drop_rate', $result);
    }

    public function test_configure_lte_cell(): void
    {
        $config = [
            'earfcn' => 1850,
            'bandwidth' => '20MHz',
            'pci' => 150,
        ];

        $result = $this->service->configureLteCell($this->device, $config);

        $this->assertTrue($result['success']);
    }

    public function test_get_neighbor_cell_list(): void
    {
        $result = $this->service->getNeighborCellList($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('neighbors', $result);
    }

    public function test_trigger_son_optimization(): void
    {
        $result = $this->service->triggerSonOptimization($this->device);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('optimization_id', $result);
    }
}
