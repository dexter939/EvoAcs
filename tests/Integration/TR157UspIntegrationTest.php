<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\CpeDevice;
use App\Services\TR157Service;
use App\Services\UspMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TR157UspIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CpeDevice $device;
    private TR157Service $tr157Service;
    private UspMessageService $uspService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->device = CpeDevice::factory()->create([
            'serial_number' => 'USP-TR157-001',
            'protocol_type' => 'tr369',
            'endpoint_id' => 'proto::USP-TR157-001',
            'mtp_type' => 'mqtt',
        ]);

        $this->tr157Service = app(TR157Service::class);
        $this->uspService = app(UspMessageService::class);
    }

    public function test_usp_get_request_retrieves_tr157_deployment_units(): void
    {
        $this->tr157Service->getAllParameters($this->device);

        $paths = [
            'Device.SoftwareModules.DeploymentUnit.',
        ];

        $response = $this->uspService->createGetRequest($this->device, $paths);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('header', $response);
        $this->assertArrayHasKey('body', $response);
    }

    public function test_usp_get_instances_returns_tr157_execution_units(): void
    {
        $this->tr157Service->getAllParameters($this->device);

        $path = 'Device.SoftwareModules.ExecutionUnit.';

        $response = $this->uspService->createGetInstancesRequest($this->device, $path);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('header', $response);
    }

    public function test_tr157_components_accessible_via_usp_data_model(): void
    {
        $this->tr157Service->getAllParameters($this->device);

        $tr157Parameters = $this->tr157Service->getAllParameters($this->device);

        $this->assertArrayHasKey('Device.SoftwareModules.DeploymentUnitNumberOfEntries', $tr157Parameters);
        $this->assertArrayHasKey('Device.SoftwareModules.ExecutionUnitNumberOfEntries', $tr157Parameters);

        $deploymentUnitCount = $tr157Parameters['Device.SoftwareModules.DeploymentUnitNumberOfEntries']['value'];
        $executionUnitCount = $tr157Parameters['Device.SoftwareModules.ExecutionUnitNumberOfEntries']['value'];

        $this->assertGreaterThan(0, $deploymentUnitCount);
        $this->assertGreaterThan(0, $executionUnitCount);
    }

    public function test_usp_notification_can_include_tr157_component_events(): void
    {
        $this->tr157Service->getAllParameters($this->device);

        $notificationData = [
            'subscription_id' => 'sub-tr157-components',
            'send_resp' => true,
            'obj_path' => 'Device.SoftwareModules.DeploymentUnit.1',
            'event_name' => 'ObjectCreated',
        ];

        $response = $this->uspService->createNotifyRequest($this->device, $notificationData);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('header', $response);
    }

    public function test_tr157_database_data_consistent_across_usp_and_cwmp(): void
    {
        $this->tr157Service->getAllParameters($this->device);

        $tr157Params = $this->tr157Service->getAllParameters($this->device);
        $deploymentUnitCount = $tr157Params['Device.SoftwareModules.DeploymentUnitNumberOfEntries']['value'];

        $dbDeploymentUnitCount = \App\Models\DeploymentUnit::where('cpe_device_id', $this->device->id)->count();

        $this->assertEquals(
            $deploymentUnitCount,
            $dbDeploymentUnitCount,
            'TR-157 parameter count must match database records for USP/CWMP consistency'
        );
    }
}
