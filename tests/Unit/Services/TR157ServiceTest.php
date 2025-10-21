<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TR157Service;
use App\Models\CpeDevice;
use App\Models\DeploymentUnit;
use App\Models\ExecutionUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TR157ServiceTest extends TestCase
{
    use RefreshDatabase;

    private TR157Service $service;
    private CpeDevice $device;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TR157Service::class);
        
        $this->device = CpeDevice::factory()->create([
            'serial_number' => 'TEST-TR157-001',
            'protocol_type' => 'tr069',
        ]);
    }

    public function test_get_all_parameters_returns_bbf_compliant_structure(): void
    {
        $result = $this->service->getAllParameters($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('SoftwareModules', $result);
        $this->assertArrayHasKey('Device.SoftwareModules.DeploymentUnitNumberOfEntries', $result);
    }

    public function test_auto_seeds_deployment_units_for_new_device(): void
    {
        $this->assertEquals(0, DeploymentUnit::where('cpe_device_id', $this->device->id)->count());

        $this->service->getAllParameters($this->device);

        $this->assertGreaterThan(0, DeploymentUnit::where('cpe_device_id', $this->device->id)->count());
    }

    public function test_auto_seeds_execution_units_for_new_device(): void
    {
        $this->assertEquals(0, ExecutionUnit::where('cpe_device_id', $this->device->id)->count());

        $this->service->getAllParameters($this->device);

        $this->assertGreaterThan(0, ExecutionUnit::where('cpe_device_id', $this->device->id)->count());
    }

    public function test_list_deployment_units_returns_database_data(): void
    {
        DeploymentUnit::create([
            'cpe_device_id' => $this->device->id,
            'name' => 'TestDeploymentUnit',
            'status' => 'Installed',
            'resolved' => true,
            'vendor' => 'TestVendor',
            'version' => '1.0.0',
        ]);

        $result = $this->service->listDeploymentUnits($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(1, $result['total']);
        $this->assertArrayHasKey('deployment_units', $result);
        $this->assertEquals('TestDeploymentUnit', $result['deployment_units'][0]['name']);
    }

    public function test_list_execution_units_returns_database_data(): void
    {
        $du = DeploymentUnit::create([
            'cpe_device_id' => $this->device->id,
            'name' => 'TestDU',
            'status' => 'Installed',
            'resolved' => true,
            'vendor' => 'TestVendor',
            'version' => '1.0.0',
        ]);

        ExecutionUnit::create([
            'cpe_device_id' => $this->device->id,
            'deployment_unit_id' => $du->id,
            'name' => 'TestExecutionUnit',
            'status' => 'Running',
            'requested_state' => 'Active',
            'execution_fault_code' => 'NoFault',
            'vendor' => 'TestVendor',
            'version' => '1.0.0',
        ]);

        $result = $this->service->listExecutionUnits($this->device);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('TestExecutionUnit', $result['execution_units'][0]['name']);
    }

    public function test_deployment_units_have_unique_identifiers(): void
    {
        $this->service->getAllParameters($this->device);

        $units = DeploymentUnit::where('cpe_device_id', $this->device->id)->get();

        $uuids = $units->pluck('uuid')->toArray();
        $duids = $units->pluck('duid')->toArray();

        $this->assertEquals(count($uuids), count(array_unique($uuids)));
        $this->assertEquals(count($duids), count(array_unique($duids)));
    }

    public function test_execution_units_reference_deployment_units(): void
    {
        $this->service->getAllParameters($this->device);

        $executionUnits = ExecutionUnit::where('cpe_device_id', $this->device->id)->get();

        foreach ($executionUnits as $eu) {
            $this->assertNotNull($eu->deployment_unit_id);
            $this->assertInstanceOf(DeploymentUnit::class, $eu->deploymentUnit);
        }
    }

    public function test_no_duplicate_seeding_on_multiple_calls(): void
    {
        $this->service->getAllParameters($this->device);
        $firstCount = DeploymentUnit::where('cpe_device_id', $this->device->id)->count();

        $this->service->getAllParameters($this->device);
        $secondCount = DeploymentUnit::where('cpe_device_id', $this->device->id)->count();

        $this->assertEquals($firstCount, $secondCount);
    }
}
