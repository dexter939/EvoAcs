<?php

namespace Tests\Feature\TR069;

use Tests\TestCase;
use App\Models\CpeDevice;
use App\Models\ProvisioningTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class ParameterOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected CpeDevice $device;
    protected string $sessionCookie;
    protected \App\Models\Tr069Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        // Create device and establish session
        $this->device = CpeDevice::factory()->tr069()->online()->create([
            'serial_number' => 'PARAM-TEST-001',
            'connection_request_url' => 'http://device.test:7547'
        ]);

        // Create session directly for response tests (cookie handling workaround)
        $sessionManager = new \App\Services\TR069SessionManager();
        $this->session = $sessionManager->createSession($this->device, '192.168.1.100');
        $this->sessionCookie = $this->session->cookie;
    }

    public function test_get_parameter_values_request_in_response(): void
    {
        Queue::fake();

        // Create a pending GetParameterValues task
        $task = ProvisioningTask::create([
            'cpe_device_id' => $this->device->id,
            'task_type' => 'get_parameters',
            'status' => 'pending',
            'task_data' => [
                'parameters' => [
                    'Device.DeviceInfo.SoftwareVersion',
                    'Device.WiFi.SSID.1.SSID'
                ]
            ]
        ]);

        // Send Inform to trigger task processing
        $informSoap = $this->createTr069Inform([
            'serial_number' => 'PARAM-TEST-001',
            'oui' => $this->device->oui,
            'product_class' => $this->device->product_class,
            'manufacturer' => $this->device->manufacturer,
            'events' => ['6 CONNECTION REQUEST']
        ]);

        $response = $this->postTr069Soap('/tr069', $informSoap);

        $responseBody = $response->getContent();

        // Verify GetParameterValues request is in response
        $this->assertStringContainsString('GetParameterValues', $responseBody);
        $this->assertStringContainsString('Device.DeviceInfo.SoftwareVersion', $responseBody);
        $this->assertStringContainsString('Device.WiFi.SSID.1.SSID', $responseBody);

        // Verify task status updated
        $task->refresh();
        $this->assertEquals('processing', $task->status);
    }

    public function test_set_parameter_values_request_in_response(): void
    {
        $task = ProvisioningTask::create([
            'cpe_device_id' => $this->device->id,
            'task_type' => 'set_parameters',
            'status' => 'pending',
            'task_data' => [
                'parameters' => [
                    'Device.ManagementServer.PeriodicInformInterval' => '600',
                    'Device.WiFi.SSID.1.SSID' => 'NewNetwork'
                ]
            ]
        ]);

        $informSoap = $this->createTr069Inform([
            'serial_number' => 'PARAM-TEST-001',
            'oui' => $this->device->oui,
            'product_class' => $this->device->product_class,
            'manufacturer' => $this->device->manufacturer,
            'events' => ['6 CONNECTION REQUEST']
        ]);

        $response = $this->postTr069Soap('/tr069', $informSoap);

        $responseBody = $response->getContent();

        // Verify SetParameterValues request
        $this->assertStringContainsString('SetParameterValues', $responseBody);
        $this->assertStringContainsString('Device.ManagementServer.PeriodicInformInterval', $responseBody);
        $this->assertStringContainsString('600', $responseBody);
        $this->assertStringContainsString('NewNetwork', $responseBody);

        $task->refresh();
        $this->assertEquals('processing', $task->status);
    }

    public function test_get_parameter_values_response_updates_database(): void
    {
        // Simulate GetParameterValuesResponse from device (include DeviceId for session correlation)
        $getResponseSoap = $this->createTr069SoapEnvelope('
            <cwmp:GetParameterValuesResponse>
                <cwmp:DeviceId>
                    <Manufacturer>' . $this->device->manufacturer . '</Manufacturer>
                    <OUI>' . $this->device->oui . '</OUI>
                    <ProductClass>' . $this->device->product_class . '</ProductClass>
                    <SerialNumber>' . $this->device->serial_number . '</SerialNumber>
                </cwmp:DeviceId>
                <ParameterList>
                    <ParameterValueStruct>
                        <Name>Device.DeviceInfo.SoftwareVersion</Name>
                        <Value xsi:type="xsd:string">v2.5.0</Value>
                    </ParameterValueStruct>
                    <ParameterValueStruct>
                        <Name>Device.WiFi.SSID.1.SSID</Name>
                        <Value xsi:type="xsd:string">MyNetwork</Value>
                    </ParameterValueStruct>
                    <ParameterValueStruct>
                        <Name>Device.DeviceInfo.UpTime</Name>
                        <Value xsi:type="xsd:string">7200</Value>
                    </ParameterValueStruct>
                </ParameterList>
            </cwmp:GetParameterValuesResponse>
        ');

        $response = $this->postTr069Soap('/tr069', $getResponseSoap);

        $response->assertStatus(200);

        // Verify parameters were stored
        $this->assertDatabaseHas('device_parameters', [
            'cpe_device_id' => $this->device->id,
            'parameter_path' => 'Device.DeviceInfo.SoftwareVersion',
            'parameter_value' => 'v2.5.0'
        ]);

        $this->assertDatabaseHas('device_parameters', [
            'cpe_device_id' => $this->device->id,
            'parameter_path' => 'Device.WiFi.SSID.1.SSID',
            'parameter_value' => 'MyNetwork'
        ]);
    }

    public function test_set_parameter_values_response_completes_task(): void
    {
        $task = ProvisioningTask::create([
            'cpe_device_id' => $this->device->id,
            'task_type' => 'set_parameters',
            'status' => 'processing',
            'task_data' => [
                'parameters' => ['Device.Test' => 'value']
            ]
        ]);

        // Simulate SetParameterValuesResponse from device (include DeviceId for session correlation)
        $setResponseSoap = $this->createTr069SoapEnvelope('
            <cwmp:SetParameterValuesResponse>
                <cwmp:DeviceId>
                    <Manufacturer>' . $this->device->manufacturer . '</Manufacturer>
                    <OUI>' . $this->device->oui . '</OUI>
                    <ProductClass>' . $this->device->product_class . '</ProductClass>
                    <SerialNumber>' . $this->device->serial_number . '</SerialNumber>
                </cwmp:DeviceId>
                <Status>0</Status>
            </cwmp:SetParameterValuesResponse>
        ');

        $response = $this->postTr069Soap('/tr069', $setResponseSoap);

        $response->assertStatus(200);

        // Verify task completed
        $task->refresh();
        $this->assertEquals('completed', $task->status);
    }

    public function test_reboot_request_in_response(): void
    {
        $task = ProvisioningTask::create([
            'cpe_device_id' => $this->device->id,
            'task_type' => 'reboot',
            'status' => 'pending',
            'task_data' => []
        ]);

        $informSoap = $this->createTr069Inform([
            'serial_number' => 'PARAM-TEST-001',
            'oui' => $this->device->oui,
            'product_class' => $this->device->product_class,
            'manufacturer' => $this->device->manufacturer,
            'events' => ['6 CONNECTION REQUEST']
        ]);

        $response = $this->postTr069Soap('/tr069', $informSoap);

        $responseBody = $response->getContent();

        // Verify Reboot command
        $this->assertStringContainsString('Reboot', $responseBody);

        $task->refresh();
        $this->assertEquals('processing', $task->status);
    }

    public function test_download_request_for_firmware_upgrade(): void
    {
        $task = ProvisioningTask::create([
            'cpe_device_id' => $this->device->id,
            'task_type' => 'download',
            'status' => 'pending',
            'task_data' => [
                'url' => 'https://firmware.test/update.bin',
                'file_type' => '1 Firmware Upgrade Image',
                'file_size' => 10485760
            ]
        ]);

        $informSoap = $this->createTr069Inform([
            'serial_number' => 'PARAM-TEST-001',
            'oui' => $this->device->oui,
            'product_class' => $this->device->product_class,
            'manufacturer' => $this->device->manufacturer,
            'events' => ['6 CONNECTION REQUEST']
        ]);

        $response = $this->postTr069Soap('/tr069', $informSoap);

        $responseBody = $response->getContent();

        // Verify Download request
        $this->assertStringContainsString('Download', $responseBody);
        $this->assertStringContainsString('https://firmware.test/update.bin', $responseBody);
        $this->assertStringContainsString('1 Firmware Upgrade Image', $responseBody);

        $task->refresh();
        $this->assertEquals('processing', $task->status);
    }

    public function test_transfer_complete_updates_firmware_status(): void
    {
        // Create firmware deployment task
        $task = ProvisioningTask::create([
            'cpe_device_id' => $this->device->id,
            'task_type' => 'download',
            'status' => 'processing',
            'task_data' => [
                'command_key' => 'Download_FW_001',
                'url' => 'https://firmware.test/update.bin',
                'file_type' => '1 Firmware Upgrade Image'
            ]
        ]);

        // Simulate TransferComplete from device after download (include DeviceId for session correlation)
        $transferCompleteSoap = $this->createTr069SoapEnvelope('
            <cwmp:TransferComplete>
                <cwmp:DeviceId>
                    <Manufacturer>' . $this->device->manufacturer . '</Manufacturer>
                    <OUI>' . $this->device->oui . '</OUI>
                    <ProductClass>' . $this->device->product_class . '</ProductClass>
                    <SerialNumber>' . $this->device->serial_number . '</SerialNumber>
                </cwmp:DeviceId>
                <CommandKey>Download_FW_001</CommandKey>
                <FaultStruct>
                    <FaultCode>0</FaultCode>
                    <FaultString>Success</FaultString>
                </FaultStruct>
                <StartTime>2025-01-15T10:00:00Z</StartTime>
                <CompleteTime>2025-01-15T10:05:00Z</CompleteTime>
            </cwmp:TransferComplete>
        ');

        $response = $this->postTr069Soap('/tr069', $transferCompleteSoap);

        $response->assertStatus(200);

        // Verify TransferCompleteResponse
        $this->assertStringContainsString('TransferCompleteResponse', $response->getContent());

        // Verify task status updated to completed
        $task->refresh();
        $this->assertEquals('completed', $task->status);

        // Verify result stored
        $result = json_decode($task->result, true);
        $this->assertEquals('0', $result['fault_code']);
        $this->assertEquals('Success', $result['fault_string']);
    }
}
