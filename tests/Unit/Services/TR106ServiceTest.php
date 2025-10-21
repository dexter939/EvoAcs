<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TR106Service;
use App\Models\CpeDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TR106ServiceTest extends TestCase
{
    use RefreshDatabase;

    private TR106Service $service;
    private CpeDevice $device;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TR106Service::class);
        
        $this->device = CpeDevice::factory()->create([
            'serial_number' => 'TEST-TR106-001',
            'protocol_type' => 'tr069',
        ]);
    }

    public function test_get_all_parameters_returns_bbf_compliant_structure(): void
    {
        $result = $this->service->getAllParameters($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('DataModelTemplate', $result);
    }

    public function test_list_data_model_templates(): void
    {
        $result = $this->service->listDataModelTemplates($this->device);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('templates', $result);
        $this->assertGreaterThan(0, $result['total']);
    }

    public function test_get_template_version_info(): void
    {
        $result = $this->service->getTemplateVersionInfo($this->device, 'Device:2.15');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('specification', $result);
        $this->assertArrayHasKey('date', $result);
    }

    public function test_validate_parameter_against_constraints(): void
    {
        $result = $this->service->validateParameter(
            'Device.WiFi.Radio.1.Channel',
            'unsignedInt',
            150,
            ['min' => 1, 'max' => 11]
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('exceeds maximum', $result['error']);
    }

    public function test_validate_parameter_with_valid_value(): void
    {
        $result = $this->service->validateParameter(
            'Device.WiFi.Radio.1.Channel',
            'unsignedInt',
            6,
            ['min' => 1, 'max' => 11]
        );

        $this->assertTrue($result['valid']);
    }

    public function test_export_template_as_xml(): void
    {
        $result = $this->service->exportTemplateAsXml($this->device, 'Device:2.15');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('xml', $result);
        $this->assertStringContainsString('<?xml version', $result['xml']);
    }

    public function test_import_template_from_xml(): void
    {
        $xml = '<?xml version="1.0"?><template><name>CustomTemplate</name></template>';
        
        $result = $this->service->importTemplateFromXml($this->device, $xml);

        $this->assertTrue($result['success']);
    }

    public function test_get_parameter_inheritance_chain(): void
    {
        $result = $this->service->getParameterInheritance($this->device, 'Device.WiFi.Radio.1');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('base_template', $result);
        $this->assertArrayHasKey('inherited_from', $result);
    }
}
