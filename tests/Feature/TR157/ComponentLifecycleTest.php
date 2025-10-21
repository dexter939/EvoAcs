<?php

namespace Tests\Feature\TR157;

use Tests\TestCase;
use App\Models\CpeDevice;
use App\Models\DeploymentUnit;
use App\Models\ExecutionUnit;
use App\Services\TR157Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ComponentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private CpeDevice $device;
    private TR157Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->device = CpeDevice::factory()->create([
            'serial_number' => 'E2E-TR157-DEVICE-001',
            'protocol_type' => 'tr069',
            'manufacturer' => 'TestVendor',
            'model' => 'TR157-Test',
        ]);

        $this->service = app(TR157Service::class);
    }

    public function test_end_to_end_deployment_unit_creation_and_retrieval(): void
    {
        $deploymentUnit = DeploymentUnit::create([
            'cpe_device_id' => $this->device->id,
            'name' => 'E2E-TestApp',
            'status' => 'Installed',
            'resolved' => true,
            'url' => 'http://repo.example.com/e2e-testapp-1.0.0.pkg',
            'vendor' => 'E2E-Vendor',
            'version' => '1.0.0',
            'description' => 'End-to-end test deployment unit',
        ]);

        $this->assertDatabaseHas('deployment_units', [
            'name' => 'E2E-TestApp',
            'cpe_device_id' => $this->device->id,
            'status' => 'Installed',
        ]);

        $parameters = $this->service->getAllParameters($this->device);
        
        $this->assertArrayHasKey('SoftwareModules', $parameters);
        $this->assertGreaterThan(0, $parameters['Device.SoftwareModules.DeploymentUnitNumberOfEntries']['value']);
    }

    public function test_end_to_end_execution_unit_lifecycle(): void
    {
        $deploymentUnit = DeploymentUnit::create([
            'cpe_device_id' => $this->device->id,
            'name' => 'E2E-BackendService',
            'status' => 'Installed',
            'resolved' => true,
            'vendor' => 'E2E-Vendor',
            'version' => '2.0.0',
        ]);

        $executionUnit = ExecutionUnit::create([
            'cpe_device_id' => $this->device->id,
            'deployment_unit_id' => $deploymentUnit->id,
            'name' => 'E2E-ServiceProcess',
            'status' => 'Running',
            'requested_state' => 'Active',
            'execution_fault_code' => 'NoFault',
            'vendor' => 'E2E-Vendor',
            'version' => '2.0.0',
            'run_level' => 3,
            'auto_start' => true,
        ]);

        $this->assertDatabaseHas('execution_units', [
            'name' => 'E2E-ServiceProcess',
            'status' => 'Running',
            'deployment_unit_id' => $deploymentUnit->id,
        ]);

        $this->assertEquals($deploymentUnit->id, $executionUnit->deploymentUnit->id);
        $this->assertEquals($this->device->id, $executionUnit->cpeDevice->id);
    }

    public function test_end_to_end_auto_seeding_workflow(): void
    {
        $initialDuCount = DeploymentUnit::where('cpe_device_id', $this->device->id)->count();
        $initialEuCount = ExecutionUnit::where('cpe_device_id', $this->device->id)->count();

        $this->assertEquals(0, $initialDuCount);
        $this->assertEquals(0, $initialEuCount);

        $parameters = $this->service->getAllParameters($this->device);

        $finalDuCount = DeploymentUnit::where('cpe_device_id', $this->device->id)->count();
        $finalEuCount = ExecutionUnit::where('cpe_device_id', $this->device->id)->count();

        $this->assertGreaterThan(0, $finalDuCount, 'Auto-seeding should create deployment units');
        $this->assertGreaterThan(0, $finalEuCount, 'Auto-seeding should create execution units');
        
        $this->assertEquals(
            $finalDuCount,
            $parameters['Device.SoftwareModules.DeploymentUnitNumberOfEntries']['value']
        );

        $this->assertEquals(
            $finalEuCount,
            $parameters['Device.SoftwareModules.ExecutionUnitNumberOfEntries']['value']
        );
    }

    public function test_end_to_end_cascade_delete_on_device_removal(): void
    {
        $this->service->getAllParameters($this->device);

        $duCount = DeploymentUnit::where('cpe_device_id', $this->device->id)->count();
        $euCount = ExecutionUnit::where('cpe_device_id', $this->device->id)->count();

        $this->assertGreaterThan(0, $duCount);
        $this->assertGreaterThan(0, $euCount);

        $deviceId = $this->device->id;
        $this->device->delete();

        $this->assertEquals(0, DeploymentUnit::where('cpe_device_id', $deviceId)->count());
        $this->assertEquals(0, ExecutionUnit::where('cpe_device_id', $deviceId)->count());
    }

    public function test_end_to_end_multiple_devices_isolated_components(): void
    {
        $device2 = CpeDevice::factory()->create([
            'serial_number' => 'E2E-TR157-DEVICE-002',
            'protocol_type' => 'tr069',
        ]);

        $this->service->getAllParameters($this->device);
        $this->service->getAllParameters($device2);

        $device1Components = DeploymentUnit::where('cpe_device_id', $this->device->id)->get();
        $device2Components = DeploymentUnit::where('cpe_device_id', $device2->id)->get();

        $this->assertGreaterThan(0, $device1Components->count());
        $this->assertGreaterThan(0, $device2Components->count());

        $device1Uuids = $device1Components->pluck('uuid')->toArray();
        $device2Uuids = $device2Components->pluck('uuid')->toArray();

        $this->assertEmpty(array_intersect($device1Uuids, $device2Uuids), 'UUIDs must be unique across devices');
    }
}
