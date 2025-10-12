<?php

namespace Tests\Unit\Services;

use App\Services\TR069Service;
use PHPUnit\Framework\TestCase;

class TR069ServiceTest extends TestCase
{
    private TR069Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TR069Service();
    }

    public function test_parse_inform_extracts_device_id_correctly(): void
    {
        $soapXml = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Body>
        <cwmp:Inform>
            <cwmp:DeviceId>
                <Manufacturer>Technicolor</Manufacturer>
                <OUI>00259E</OUI>
                <ProductClass>IGD</ProductClass>
                <SerialNumber>TEST123456</SerialNumber>
            </cwmp:DeviceId>
            <Event>
                <EventStruct>
                    <EventCode>0 BOOTSTRAP</EventCode>
                    <CommandKey></CommandKey>
                </EventStruct>
            </Event>
            <ParameterList></ParameterList>
        </cwmp:Inform>
    </soap:Body>
</soap:Envelope>';

        $xml = simplexml_load_string($soapXml);
        $result = $this->service->parseInform($xml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('device_id', $result);
        $this->assertEquals('TEST123456', $result['device_id']['serial_number']);
        $this->assertEquals('00259E', $result['device_id']['oui']);
        $this->assertEquals('IGD', $result['device_id']['product_class']);
        $this->assertEquals('Technicolor', $result['device_id']['manufacturer']);
    }

    public function test_parse_inform_extracts_events_correctly(): void
    {
        $soapXml = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Body>
        <cwmp:Inform>
            <cwmp:DeviceId>
                <Manufacturer>Test</Manufacturer>
                <OUI>000000</OUI>
                <ProductClass>IGD</ProductClass>
                <SerialNumber>SN123</SerialNumber>
            </cwmp:DeviceId>
            <Event>
                <EventStruct>
                    <EventCode>0 BOOTSTRAP</EventCode>
                    <CommandKey></CommandKey>
                </EventStruct>
                <EventStruct>
                    <EventCode>1 BOOT</EventCode>
                    <CommandKey></CommandKey>
                </EventStruct>
                <EventStruct>
                    <EventCode>6 CONNECTION REQUEST</EventCode>
                    <CommandKey></CommandKey>
                </EventStruct>
            </Event>
            <ParameterList></ParameterList>
        </cwmp:Inform>
    </soap:Body>
</soap:Envelope>';

        $xml = simplexml_load_string($soapXml);
        $result = $this->service->parseInform($xml);

        $this->assertArrayHasKey('events', $result);
        $this->assertCount(3, $result['events']);
        $this->assertContains('0 BOOTSTRAP', $result['events']);
        $this->assertContains('1 BOOT', $result['events']);
        $this->assertContains('6 CONNECTION REQUEST', $result['events']);
    }

    public function test_parse_inform_extracts_parameters_correctly(): void
    {
        $soapXml = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Body>
        <cwmp:Inform>
            <cwmp:DeviceId>
                <Manufacturer>Test</Manufacturer>
                <OUI>000000</OUI>
                <ProductClass>IGD</ProductClass>
                <SerialNumber>SN123</SerialNumber>
            </cwmp:DeviceId>
            <Event></Event>
            <ParameterList>
                <ParameterValueStruct>
                    <Name>Device.DeviceInfo.SoftwareVersion</Name>
                    <Value>v1.2.3</Value>
                </ParameterValueStruct>
                <ParameterValueStruct>
                    <Name>Device.ManagementServer.PeriodicInformInterval</Name>
                    <Value>300</Value>
                </ParameterValueStruct>
            </ParameterList>
        </cwmp:Inform>
    </soap:Body>
</soap:Envelope>';

        $xml = simplexml_load_string($soapXml);
        $result = $this->service->parseInform($xml);

        $this->assertArrayHasKey('parameters', $result);
        $this->assertEquals('v1.2.3', $result['parameters']['Device.DeviceInfo.SoftwareVersion']);
        $this->assertEquals('300', $result['parameters']['Device.ManagementServer.PeriodicInformInterval']);
    }

    public function test_generate_inform_response_returns_valid_soap(): void
    {
        $response = $this->service->generateInformResponse(1);

        $this->assertStringContainsString('<?xml version="1.0"', $response);
        $this->assertStringContainsString('soap:Envelope', $response);
        $this->assertStringContainsString('cwmp:InformResponse', $response);
        $this->assertStringContainsString('MaxEnvelopes', $response);

        // Verify it's valid XML
        $xml = simplexml_load_string($response);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
    }

    public function test_generate_get_parameter_values_request_creates_valid_soap(): void
    {
        $parameters = [
            'Device.DeviceInfo.SoftwareVersion',
            'Device.WiFi.SSID.1.SSID',
        ];

        $request = $this->service->generateGetParameterValuesRequest($parameters);

        $this->assertStringContainsString('<?xml version="1.0"', $request);
        $this->assertStringContainsString('cwmp:GetParameterValues', $request);
        $this->assertStringContainsString('Device.DeviceInfo.SoftwareVersion', $request);
        $this->assertStringContainsString('Device.WiFi.SSID.1.SSID', $request);

        // Verify it's valid XML
        $xml = simplexml_load_string($request);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
    }

    public function test_generate_set_parameter_values_request_creates_valid_soap(): void
    {
        $parameters = [
            'Device.ManagementServer.PeriodicInformInterval' => '600',
            'Device.WiFi.SSID.1.SSID' => 'TestNetwork'
        ];

        $request = $this->service->generateSetParameterValuesRequest($parameters);

        $this->assertStringContainsString('<?xml version="1.0"', $request);
        $this->assertStringContainsString('cwmp:SetParameterValues', $request);
        $this->assertStringContainsString('Device.ManagementServer.PeriodicInformInterval', $request);
        $this->assertStringContainsString('600', $request);
        $this->assertStringContainsString('Device.WiFi.SSID.1.SSID', $request);
        $this->assertStringContainsString('TestNetwork', $request);

        // Verify it's valid XML
        $xml = simplexml_load_string($request);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
    }

    public function test_generate_reboot_request_creates_valid_soap(): void
    {
        $request = $this->service->generateRebootRequest();

        $this->assertStringContainsString('<?xml version="1.0"', $request);
        $this->assertStringContainsString('cwmp:Reboot', $request);
        $this->assertStringContainsString('CommandKey', $request);

        // Verify it's valid XML
        $xml = simplexml_load_string($request);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
    }

    public function test_generate_download_request_creates_valid_soap(): void
    {
        $url = 'https://firmware.example.com/update.bin';
        $fileType = '1 Firmware Upgrade Image';
        $fileSize = 10485760;
        $messageId = 1;
        $commandKey = 'Download_Test';

        $request = $this->service->generateDownloadRequest($url, $fileType, $fileSize, $messageId, $commandKey);

        $this->assertStringContainsString('<?xml version="1.0"', $request);
        $this->assertStringContainsString('cwmp:Download', $request);
        $this->assertStringContainsString('cwmp:Download', $request);
        $this->assertStringContainsString($url, $request);
        $this->assertStringContainsString($fileType, $request);

        // Verify it's valid XML
        $xml = simplexml_load_string($request);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
    }

    public function test_parse_inform_handles_empty_parameter_list(): void
    {
        $soapXml = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Body>
        <cwmp:Inform>
            <cwmp:DeviceId>
                <Manufacturer>Test</Manufacturer>
                <OUI>000000</OUI>
                <ProductClass>IGD</ProductClass>
                <SerialNumber>SN123</SerialNumber>
            </cwmp:DeviceId>
            <Event></Event>
            <ParameterList></ParameterList>
        </cwmp:Inform>
    </soap:Body>
</soap:Envelope>';

        $xml = simplexml_load_string($soapXml);
        $result = $this->service->parseInform($xml);

        $this->assertArrayHasKey('parameters', $result);
        $this->assertIsArray($result['parameters']);
        $this->assertEmpty($result['parameters']);
    }

    public function test_parse_inform_handles_missing_device_id_fields(): void
    {
        $soapXml = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Body>
        <cwmp:Inform>
            <cwmp:DeviceId>
                <SerialNumber>PARTIAL123</SerialNumber>
            </cwmp:DeviceId>
            <Event></Event>
            <ParameterList></ParameterList>
        </cwmp:Inform>
    </soap:Body>
</soap:Envelope>';

        $xml = simplexml_load_string($soapXml);
        $result = $this->service->parseInform($xml);

        $this->assertEquals('PARTIAL123', $result['device_id']['serial_number']);
        $this->assertEquals('', $result['device_id']['oui']);
        $this->assertEquals('', $result['device_id']['product_class']);
        $this->assertEquals('', $result['device_id']['manufacturer']);
    }

    public function test_parse_inform_handles_namespaced_events_and_parameters(): void
    {
        $soapXml = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Body>
        <cwmp:Inform>
            <cwmp:DeviceId>
                <cwmp:Manufacturer>Namespaced</cwmp:Manufacturer>
                <cwmp:OUI>AABBCC</cwmp:OUI>
                <cwmp:ProductClass>CPE</cwmp:ProductClass>
                <cwmp:SerialNumber>NS12345</cwmp:SerialNumber>
            </cwmp:DeviceId>
            <cwmp:Event>
                <cwmp:EventStruct>
                    <cwmp:EventCode>2 PERIODIC</cwmp:EventCode>
                    <cwmp:CommandKey></cwmp:CommandKey>
                </cwmp:EventStruct>
            </cwmp:Event>
            <cwmp:ParameterList>
                <cwmp:ParameterValueStruct>
                    <cwmp:Name>Device.DeviceInfo.HardwareVersion</cwmp:Name>
                    <cwmp:Value>HW1.0</cwmp:Value>
                </cwmp:ParameterValueStruct>
            </cwmp:ParameterList>
        </cwmp:Inform>
    </soap:Body>
</soap:Envelope>';

        $xml = simplexml_load_string($soapXml);
        $result = $this->service->parseInform($xml);

        // Verifica DeviceId con namespace cwmp completo (production critical)
        // Verify DeviceId with full cwmp namespace (production critical)
        $this->assertArrayHasKey('device_id', $result);
        $this->assertEquals('NS12345', $result['device_id']['serial_number']);
        $this->assertEquals('AABBCC', $result['device_id']['oui']);
        $this->assertEquals('CPE', $result['device_id']['product_class']);
        $this->assertEquals('Namespaced', $result['device_id']['manufacturer']);

        // Verifica eventi con namespace cwmp
        // Verify events with cwmp namespace
        $this->assertArrayHasKey('events', $result);
        $this->assertCount(1, $result['events']);
        $this->assertContains('2 PERIODIC', $result['events']);
        
        // Verifica parametri con namespace cwmp
        // Verify parameters with cwmp namespace
        $this->assertArrayHasKey('parameters', $result);
        $this->assertEquals('HW1.0', $result['parameters']['Device.DeviceInfo.HardwareVersion']);
    }
}
