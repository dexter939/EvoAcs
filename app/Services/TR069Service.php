<?php

namespace App\Services;

use App\Models\CpeDevice;
use App\Models\DeviceParameter;
use App\Models\ProvisioningTask;
use Carbon\Carbon;

class TR069Service
{
    public function parseInform($xml)
    {
        $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('cwmp', 'urn:dslforum-org:cwmp-1-0');
        
        $deviceId = $xml->xpath('//cwmp:DeviceId')[0] ?? null;
        $eventCodes = $xml->xpath('//cwmp:EventStruct/cwmp:EventCode') ?? [];
        $parameterList = $xml->xpath('//cwmp:ParameterValueStruct') ?? [];
        
        $data = [
            'device_id' => [],
            'events' => [],
            'parameters' => []
        ];
        
        if ($deviceId) {
            $data['device_id'] = [
                'serial_number' => (string)($deviceId->SerialNumber ?? ''),
                'oui' => (string)($deviceId->OUI ?? ''),
                'product_class' => (string)($deviceId->ProductClass ?? ''),
                'manufacturer' => (string)($deviceId->Manufacturer ?? '')
            ];
        }
        
        foreach ($eventCodes as $event) {
            $data['events'][] = (string)$event;
        }
        
        foreach ($parameterList as $param) {
            $name = (string)($param->Name ?? '');
            $value = (string)($param->Value ?? '');
            if ($name) {
                $data['parameters'][$name] = $value;
            }
        }
        
        return $data;
    }
    
    public function generateInformResponse($maxEnvelopes = 1)
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Header>
        <cwmp:ID soap:mustUnderstand="1">' . uniqid() . '</cwmp:ID>
    </soap:Header>
    <soap:Body>
        <cwmp:InformResponse>
            <MaxEnvelopes>' . $maxEnvelopes . '</MaxEnvelopes>
        </cwmp:InformResponse>
    </soap:Body>
</soap:Envelope>';
    }
    
    public function generateGetParameterValuesRequest($parameters)
    {
        $paramList = '';
        foreach ($parameters as $param) {
            $paramList .= '<string>' . htmlspecialchars($param) . '</string>';
        }
        
        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Header>
        <cwmp:ID soap:mustUnderstand="1">' . uniqid() . '</cwmp:ID>
    </soap:Header>
    <soap:Body>
        <cwmp:GetParameterValues>
            <ParameterNames soap:arrayType="xsd:string[' . count($parameters) . ']">
                ' . $paramList . '
            </ParameterNames>
        </cwmp:GetParameterValues>
    </soap:Body>
</soap:Envelope>';
    }
    
    public function generateSetParameterValuesRequest($parameters)
    {
        $paramList = '';
        foreach ($parameters as $name => $value) {
            $paramList .= '<ParameterValueStruct>
                <Name>' . htmlspecialchars($name) . '</Name>
                <Value>' . htmlspecialchars($value) . '</Value>
            </ParameterValueStruct>';
        }
        
        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Header>
        <cwmp:ID soap:mustUnderstand="1">' . uniqid() . '</cwmp:ID>
    </soap:Header>
    <soap:Body>
        <cwmp:SetParameterValues>
            <ParameterList soap:arrayType="cwmp:ParameterValueStruct[' . count($parameters) . ']">
                ' . $paramList . '
            </ParameterList>
            <ParameterKey></ParameterKey>
        </cwmp:SetParameterValues>
    </soap:Body>
</soap:Envelope>';
    }
    
    public function generateRebootRequest()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Header>
        <cwmp:ID soap:mustUnderstand="1">' . uniqid() . '</cwmp:ID>
    </soap:Header>
    <soap:Body>
        <cwmp:Reboot>
            <CommandKey>Reboot_' . time() . '</CommandKey>
        </cwmp:Reboot>
    </soap:Body>
</soap:Envelope>';
    }
    
    public function generateDownloadRequest($url, $fileType = '1 Firmware Upgrade Image', $fileSize = 0)
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Header>
        <cwmp:ID soap:mustUnderstand="1">' . uniqid() . '</cwmp:ID>
    </soap:Header>
    <soap:Body>
        <cwmp:Download>
            <CommandKey>Download_' . time() . '</CommandKey>
            <FileType>' . htmlspecialchars($fileType) . '</FileType>
            <URL>' . htmlspecialchars($url) . '</URL>
            <Username></Username>
            <Password></Password>
            <FileSize>' . $fileSize . '</FileSize>
            <TargetFileName></TargetFileName>
            <DelaySeconds>0</DelaySeconds>
            <SuccessURL></SuccessURL>
            <FailureURL></FailureURL>
        </cwmp:Download>
    </soap:Body>
</soap:Envelope>';
    }
}
