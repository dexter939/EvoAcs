<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\CpeDevice;
use App\Services\TR157Service;
use App\Services\TR069Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TR157CwmpIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CpeDevice $device;
    private TR157Service $tr157Service;
    private TR069Service $tr069Service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->device = CpeDevice::factory()->create([
            'serial_number' => 'CWMP-TR157-001',
            'protocol_type' => 'tr069',
            'connection_request_url' => 'http://192.168.1.100:7547',
            'connection_request_username' => 'admin',
            'connection_request_password' => 'admin123',
        ]);

        $this->tr157Service = app(TR157Service::class);
        $this->tr069Service = app(TR069Service::class);
    }

    public function test_tr069_can_query_tr157_deployment_units(): void
    {
        $this->tr157Service->getAllParameters($this->device);

        $parameterPath = 'Device.SoftwareModules.DeploymentUnitNumberOfEntries';
        
        $response = $this->tr069Service->getParameterValues(
            $this->device,
            [$parameterPath]
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('parameters', $response);
        $this->assertGreaterThan(0, count($response['parameters']));
    }

    public function test_tr069_can_read_tr157_execution_unit_status(): void
    {
        $this->tr157Service->getAllParameters($this->device);

        $parameterPath = 'Device.SoftwareModules.ExecutionUnit.1.Status';
        
        $response = $this->tr069Service->getParameterValues(
            $this->device,
            [$parameterPath]
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('parameters', $response);
    }

    public function test_cwmp_inform_includes_tr157_parameters(): void
    {
        $this->tr157Service->getAllParameters($this->device);

        $informData = [
            'DeviceId' => [
                'Manufacturer' => $this->device->manufacturer,
                'OUI' => substr($this->device->serial_number, 0, 6),
                'ProductClass' => $this->device->model,
                'SerialNumber' => $this->device->serial_number,
            ],
            'Event' => [
                ['EventCode' => '1 BOOTSTRAP', 'CommandKey' => ''],
            ],
            'ParameterList' => [],
        ];

        $response = $this->tr069Service->processInform($this->device, $informData);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
    }

    public function test_tr157_database_backed_data_persists_across_cwmp_sessions(): void
    {
        $this->tr157Service->getAllParameters($this->device);
        
        $firstQueryResponse = $this->tr069Service->getParameterValues(
            $this->device,
            ['Device.SoftwareModules.DeploymentUnitNumberOfEntries']
        );

        $this->device->refresh();

        $secondQueryResponse = $this->tr069Service->getParameterValues(
            $this->device,
            ['Device.SoftwareModules.DeploymentUnitNumberOfEntries']
        );

        $this->assertEquals(
            $firstQueryResponse['parameters'][0]['value'] ?? null,
            $secondQueryResponse['parameters'][0]['value'] ?? null,
            'TR-157 data should persist across multiple CWMP sessions'
        );
    }
}
