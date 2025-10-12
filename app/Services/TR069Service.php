<?php

namespace App\Services;

use App\Models\CpeDevice;
use App\Models\DeviceParameter;
use App\Models\ProvisioningTask;
use Carbon\Carbon;

/**
 * TR069Service - Servizio per generazione messaggi SOAP TR-069 (CWMP)
 * TR069Service - Service for generating TR-069 (CWMP) SOAP messages
 * 
 * Questo servizio fornisce metodi per creare richieste SOAP conformi allo standard TR-069
 * per comunicare con dispositivi CPE
 * 
 * This service provides methods to create SOAP requests compliant with TR-069 standard
 * to communicate with CPE devices
 * 
 * Standard supportati / Supported standards:
 * - TR-069 Amendment 5 (CWMP)
 * - TR-181 Issue 2 (Device Data Model)
 */
class TR069Service
{
    /**
     * Parsa messaggio Inform dal dispositivo CPE
     * Parse Inform message from CPE device
     * 
     * Estrae da messaggio SOAP Inform:
     * - DeviceId (SerialNumber, OUI, ProductClass, Manufacturer)
     * - Eventi (EventCode)
     * - Parametri dispositivo (ParameterValueStruct)
     * 
     * Extracts from SOAP Inform message:
     * - DeviceId (SerialNumber, OUI, ProductClass, Manufacturer)
     * - Events (EventCode)
     * - Device parameters (ParameterValueStruct)
     * 
     * @param \SimpleXMLElement $xml Messaggio SOAP Inform parsato / Parsed SOAP Inform message
     * @return array Dati estratti (device_id, events, parameters) / Extracted data
     */
    public function parseInform($xml)
    {
        // Registra namespace SOAP e CWMP per XPath
        // Register SOAP and CWMP namespaces for XPath
        $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('cwmp', 'urn:dslforum-org:cwmp-1-0');
        
        $deviceId = $xml->xpath('//cwmp:DeviceId')[0] ?? null;
        $eventCodes = $xml->xpath('//EventStruct/EventCode') ?? [];
        $parameterList = $xml->xpath('//cwmp:ParameterValueStruct') ?? [];
        
        $data = [
            'device_id' => [],
            'events' => [],
            'parameters' => []
        ];
        
        // Estrae informazioni DeviceId
        // Extract DeviceId information
        if ($deviceId) {
            $data['device_id'] = [
                'serial_number' => (string)($deviceId->SerialNumber ?? ''),
                'oui' => (string)($deviceId->OUI ?? ''),
                'product_class' => (string)($deviceId->ProductClass ?? ''),
                'manufacturer' => (string)($deviceId->Manufacturer ?? '')
            ];
        }
        
        // Estrae event codes (es. "0 BOOTSTRAP", "1 BOOT", "6 CONNECTION REQUEST")
        // Extract event codes (e.g. "0 BOOTSTRAP", "1 BOOT", "6 CONNECTION REQUEST")
        foreach ($eventCodes as $event) {
            $data['events'][] = (string)$event;
        }
        
        // Estrae parametri TR-181 del dispositivo
        // Extract TR-181 device parameters
        foreach ($parameterList as $param) {
            $name = (string)($param->Name ?? '');
            $value = (string)($param->Value ?? '');
            if ($name) {
                $data['parameters'][$name] = $value;
            }
        }
        
        return $data;
    }
    
    /**
     * Genera risposta SOAP InformResponse
     * Generate SOAP InformResponse
     * 
     * @param int $maxEnvelopes Numero max envelope per sessione / Max envelopes per session
     * @return string Messaggio SOAP XML / SOAP XML message
     */
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
    
    /**
     * Genera richiesta SOAP GetParameterValues
     * Generate SOAP GetParameterValues request
     * 
     * Richiede lettura parametri TR-181 dal dispositivo
     * Requests reading TR-181 parameters from device
     * 
     * @param array $parameters Array di percorsi parametri TR-181 / Array of TR-181 parameter paths
     * @return string Messaggio SOAP XML / SOAP XML message
     */
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
    
    /**
     * Genera richiesta SOAP SetParameterValues
     * Generate SOAP SetParameterValues request
     * 
     * Imposta parametri TR-181 sul dispositivo
     * Sets TR-181 parameters on device
     * 
     * @param array $parameters Array associativo [path => value] / Associative array [path => value]
     * @return string Messaggio SOAP XML / SOAP XML message
     */
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
    
    /**
     * Genera richiesta SOAP Reboot
     * Generate SOAP Reboot request
     * 
     * Riavvia dispositivo CPE remotamente
     * Remotely reboots CPE device
     * 
     * @return string Messaggio SOAP XML / SOAP XML message
     */
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
    
    /**
     * Genera richiesta SOAP Download per firmware
     * Generate SOAP Download request for firmware
     * 
     * Istruisce dispositivo CPE a scaricare file (firmware, configurazione, etc.)
     * Instructs CPE device to download file (firmware, configuration, etc.)
     * 
     * @param string $url URL pubblico file da scaricare / Public URL of file to download
     * @param string $fileType Tipo file TR-069 (default "1 Firmware Upgrade Image") / TR-069 file type
     * @param int $fileSize Dimensione file in bytes / File size in bytes
     * @param int $messageId Message ID SOAP per sessione / SOAP message ID for session
     * @param string $commandKey Command Key custom per correlazione (opzionale) / Custom command key for correlation
     * @return string Messaggio SOAP XML / SOAP XML message
     */
    public function generateDownloadRequest($url, $fileType = '1 Firmware Upgrade Image', $fileSize = 0, $messageId = 1, $commandKey = '')
    {
        // Usa CommandKey custom se fornito, altrimenti genera automatico
        // Use custom CommandKey if provided, otherwise generate automatic
        $cmdKey = $commandKey ?: 'Download_' . time();
        
        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Header>
        <cwmp:ID soap:mustUnderstand="1">' . $messageId . '</cwmp:ID>
    </soap:Header>
    <soap:Body>
        <cwmp:Download>
            <CommandKey>' . htmlspecialchars($cmdKey) . '</CommandKey>
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

    /**
     * Genera richiesta SOAP per avviare IPPing Diagnostics (TR-143)
     * Generate SOAP request to start IPPing Diagnostics (TR-143)
     * 
     * Test di ping verso un host specifico per verificare connettività
     * Ping test to specific host to verify connectivity
     * 
     * Standard TR-143: Device.IP.Diagnostics.IPPing.*
     * 
     * @param string $host Host target (IP o hostname) / Target host (IP or hostname)
     * @param int $numberOfRepetitions Numero pacchetti ping (default 4) / Number of ping packets
     * @param int $timeout Timeout in millisecondi (default 1000) / Timeout in milliseconds
     * @param int $dataBlockSize Dimensione pacchetto in bytes (default 64) / Packet size in bytes
     * @return string Messaggio SOAP XML / SOAP XML message
     */
    public function generateIPPingDiagnosticsRequest($host, $numberOfRepetitions = 4, $timeout = 1000, $dataBlockSize = 64)
    {
        $parameters = [
            'Device.IP.Diagnostics.IPPing.DiagnosticsState' => 'Requested',
            'Device.IP.Diagnostics.IPPing.Host' => $host,
            'Device.IP.Diagnostics.IPPing.NumberOfRepetitions' => $numberOfRepetitions,
            'Device.IP.Diagnostics.IPPing.Timeout' => $timeout,
            'Device.IP.Diagnostics.IPPing.DataBlockSize' => $dataBlockSize
        ];

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
            <ParameterKey>IPPing_' . time() . '</ParameterKey>
        </cwmp:SetParameterValues>
    </soap:Body>
</soap:Envelope>';
    }

    /**
     * Genera richiesta SOAP per avviare TraceRoute Diagnostics (TR-143)
     * Generate SOAP request to start TraceRoute Diagnostics (TR-143)
     * 
     * Test traceroute per visualizzare percorso pacchetti verso host
     * Traceroute test to visualize packet path to host
     * 
     * Standard TR-143: Device.IP.Diagnostics.TraceRoute.*
     * 
     * @param string $host Host target (IP o hostname) / Target host (IP or hostname)
     * @param int $numberOfTries Tentativi per hop (default 3) / Attempts per hop
     * @param int $timeout Timeout in millisecondi (default 5000) / Timeout in milliseconds
     * @param int $dataBlockSize Dimensione pacchetto in bytes (default 38) / Packet size in bytes
     * @param int $maxHopCount Numero massimo hop (default 30) / Maximum hop count
     * @return string Messaggio SOAP XML / SOAP XML message
     */
    public function generateTraceRouteDiagnosticsRequest($host, $numberOfTries = 3, $timeout = 5000, $dataBlockSize = 38, $maxHopCount = 30)
    {
        $parameters = [
            'Device.IP.Diagnostics.TraceRoute.DiagnosticsState' => 'Requested',
            'Device.IP.Diagnostics.TraceRoute.Host' => $host,
            'Device.IP.Diagnostics.TraceRoute.NumberOfTries' => $numberOfTries,
            'Device.IP.Diagnostics.TraceRoute.Timeout' => $timeout,
            'Device.IP.Diagnostics.TraceRoute.DataBlockSize' => $dataBlockSize,
            'Device.IP.Diagnostics.TraceRoute.MaxHopCount' => $maxHopCount
        ];

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
            <ParameterKey>TraceRoute_' . time() . '</ParameterKey>
        </cwmp:SetParameterValues>
    </soap:Body>
</soap:Envelope>';
    }

    /**
     * Genera richiesta SOAP per avviare Download Diagnostics (Speed Test Download)
     * Generate SOAP request to start Download Diagnostics (Speed Test Download)
     * 
     * Test velocità download da URL specificato
     * Download speed test from specified URL
     * 
     * Standard TR-143: Device.IP.Diagnostics.DownloadDiagnostics.*
     * 
     * @param string $downloadUrl URL server test download / Download test server URL
     * @param int $testFileLength Dimensione file test in bytes (opzionale) / Test file size in bytes (optional)
     * @return string Messaggio SOAP XML / SOAP XML message
     */
    public function generateDownloadDiagnosticsRequest($downloadUrl, $testFileLength = 0)
    {
        $parameters = [
            'Device.IP.Diagnostics.DownloadDiagnostics.DiagnosticsState' => 'Requested',
            'Device.IP.Diagnostics.DownloadDiagnostics.DownloadURL' => $downloadUrl
        ];

        if ($testFileLength > 0) {
            $parameters['Device.IP.Diagnostics.DownloadDiagnostics.TestFileLength'] = $testFileLength;
        }

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
            <ParameterKey>DownloadDiag_' . time() . '</ParameterKey>
        </cwmp:SetParameterValues>
    </soap:Body>
</soap:Envelope>';
    }

    /**
     * Genera richiesta SOAP per avviare Upload Diagnostics (Speed Test Upload)
     * Generate SOAP request to start Upload Diagnostics (Speed Test Upload)
     * 
     * Test velocità upload verso URL specificato
     * Upload speed test to specified URL
     * 
     * Standard TR-143: Device.IP.Diagnostics.UploadDiagnostics.*
     * 
     * @param string $uploadUrl URL server test upload / Upload test server URL
     * @param int $testFileLength Dimensione file test in bytes (default 1MB) / Test file size in bytes
     * @return string Messaggio SOAP XML / SOAP XML message
     */
    public function generateUploadDiagnosticsRequest($uploadUrl, $testFileLength = 1048576)
    {
        $parameters = [
            'Device.IP.Diagnostics.UploadDiagnostics.DiagnosticsState' => 'Requested',
            'Device.IP.Diagnostics.UploadDiagnostics.UploadURL' => $uploadUrl,
            'Device.IP.Diagnostics.UploadDiagnostics.TestFileLength' => $testFileLength
        ];

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
            <ParameterKey>UploadDiag_' . time() . '</ParameterKey>
        </cwmp:SetParameterValues>
    </soap:Body>
</soap:Envelope>';
    }

    /**
     * Genera richiesta SOAP per leggere risultati diagnostica
     * Generate SOAP request to read diagnostic results
     * 
     * Dopo che diagnostica è completata (DiagnosticsState=Complete), legge risultati
     * After diagnostic is complete (DiagnosticsState=Complete), reads results
     * 
     * @param string $diagnosticType Tipo diagnostica: IPPing, TraceRoute, DownloadDiagnostics, UploadDiagnostics
     * @return string Messaggio SOAP XML / SOAP XML message
     */
    public function generateGetDiagnosticsResultsRequest($diagnosticType)
    {
        $basePath = 'Device.IP.Diagnostics.' . $diagnosticType . '.';
        
        // Parametri comuni a tutti i tipi di diagnostica
        // Common parameters for all diagnostic types
        $parameters = [
            $basePath . 'DiagnosticsState'
        ];

        // Parametri specifici per tipo diagnostica
        // Type-specific diagnostic parameters
        switch ($diagnosticType) {
            case 'IPPing':
                $parameters = array_merge($parameters, [
                    $basePath . 'SuccessCount',
                    $basePath . 'FailureCount',
                    $basePath . 'AverageResponseTime',
                    $basePath . 'MinimumResponseTime',
                    $basePath . 'MaximumResponseTime'
                ]);
                break;

            case 'TraceRoute':
                $parameters = array_merge($parameters, [
                    $basePath . 'ResponseTime',
                    $basePath . 'RouteHopsNumberOfEntries',
                    $basePath . 'RouteHops.'  // Legge tutti gli hop
                ]);
                break;

            case 'DownloadDiagnostics':
                $parameters = array_merge($parameters, [
                    $basePath . 'ROMTime',
                    $basePath . 'BOMTime',
                    $basePath . 'EOMTime',
                    $basePath . 'TestBytesReceived',
                    $basePath . 'TotalBytesReceived',
                    $basePath . 'TCPOpenRequestTime',
                    $basePath . 'TCPOpenResponseTime'
                ]);
                break;

            case 'UploadDiagnostics':
                $parameters = array_merge($parameters, [
                    $basePath . 'ROMTime',
                    $basePath . 'BOMTime',
                    $basePath . 'EOMTime',
                    $basePath . 'TestBytesSent',
                    $basePath . 'TotalBytesSent',
                    $basePath . 'TCPOpenRequestTime',
                    $basePath . 'TCPOpenResponseTime'
                ]);
                break;
        }

        return $this->generateGetParameterValuesRequest($parameters);
    }
}
