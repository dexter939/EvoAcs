<?php

namespace App\Services;

use App\Models\CpeDevice;
use App\Models\LanDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service per TR-64 UPnP/SSDP Discovery
 * Service for TR-64 UPnP/SSDP Discovery
 * 
 * Gestisce discovery dispositivi LAN tramite UPnP/SSDP
 * Handles LAN device discovery via UPnP/SSDP
 */
class UpnpDiscoveryService
{
    /**
     * Processa annuncio SSDP e salva/aggiorna LAN device
     * Process SSDP announcement and save/update LAN device
     * 
     * @param CpeDevice $cpeDevice Dispositivo CPE parent
     * @param array $ssdpData Dati SSDP announcement
     * @return LanDevice Device salvato
     */
    public function processSsdpAnnouncement(CpeDevice $cpeDevice, array $ssdpData): LanDevice
    {
        $usn = $ssdpData['usn'] ?? $ssdpData['USN'] ?? null;
        $location = $ssdpData['location'] ?? $ssdpData['LOCATION'] ?? null;

        if (!$usn) {
            throw new \InvalidArgumentException('USN is required for SSDP announcement');
        }

        // Parse device description se disponibile location
        $deviceInfo = [];
        if ($location) {
            $deviceInfo = $this->fetchDeviceDescription($location);
        }

        // Upsert LAN device
        $lanDevice = LanDevice::updateOrCreate(
            [
                'cpe_device_id' => $cpeDevice->id,
                'usn' => $usn
            ],
            [
                'device_type' => $ssdpData['nt'] ?? $ssdpData['NT'] ?? $deviceInfo['deviceType'] ?? null,
                'friendly_name' => $deviceInfo['friendlyName'] ?? null,
                'manufacturer' => $deviceInfo['manufacturer'] ?? null,
                'model_name' => $deviceInfo['modelName'] ?? null,
                'serial_number' => $deviceInfo['serialNumber'] ?? null,
                'ip_address' => $this->extractIpFromLocation($location),
                'port' => $this->extractPortFromLocation($location),
                'services' => $deviceInfo['services'] ?? null,
                'description_url' => $deviceInfo['presentationURL'] ?? null,
                'location' => $location,
                'status' => 'active',
                'last_seen' => now(),
                'discovered_at' => $ssdpData['discovered_at'] ?? now(),
                'metadata' => [
                    'ssdp_headers' => $ssdpData,
                    'discovery_method' => 'ssdp'
                ]
            ]
        );

        Log::info("TR-64: Processed SSDP announcement for USN: {$usn}");

        return $lanDevice;
    }

    /**
     * Fetch device description XML da location URL
     * Fetch device description XML from location URL
     */
    private function fetchDeviceDescription(string $location): array
    {
        try {
            $response = Http::timeout(5)->get($location);

            if (!$response->successful()) {
                Log::warning("TR-64: Failed to fetch device description from {$location}");
                return [];
            }

            $xml = simplexml_load_string($response->body());
            if (!$xml) {
                return [];
            }

            // Parse UPnP device XML
            $device = $xml->device ?? null;
            if (!$device) {
                return [];
            }

            $services = [];
            if (isset($device->serviceList->service)) {
                foreach ($device->serviceList->service as $service) {
                    $services[] = [
                        'serviceType' => (string)$service->serviceType,
                        'serviceId' => (string)$service->serviceId,
                        'controlURL' => (string)$service->controlURL,
                        'eventSubURL' => (string)$service->eventSubURL,
                        'SCPDURL' => (string)$service->SCPDURL
                    ];
                }
            }

            return [
                'deviceType' => (string)$device->deviceType,
                'friendlyName' => (string)$device->friendlyName,
                'manufacturer' => (string)$device->manufacturer,
                'modelName' => (string)$device->modelName,
                'serialNumber' => (string)$device->serialNumber,
                'UDN' => (string)$device->UDN,
                'presentationURL' => (string)$device->presentationURL,
                'services' => $services
            ];

        } catch (\Exception $e) {
            Log::error("TR-64: Error fetching device description: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Estrae IP address da location URL
     * Extract IP address from location URL
     */
    private function extractIpFromLocation(?string $location): ?string
    {
        if (!$location) {
            return null;
        }

        $parsed = parse_url($location);
        return $parsed['host'] ?? null;
    }

    /**
     * Estrae porta da location URL
     * Extract port from location URL
     */
    private function extractPortFromLocation(?string $location): ?int
    {
        if (!$location) {
            return null;
        }

        $parsed = parse_url($location);
        return $parsed['port'] ?? null;
    }

    /**
     * Invoca azione SOAP su servizio UPnP
     * Invoke SOAP action on UPnP service
     * 
     * @param LanDevice $lanDevice Dispositivo target
     * @param string $serviceType Tipo servizio (es. urn:schemas-upnp-org:service:DeviceInfo:1)
     * @param string $action Azione SOAP
     * @param array $arguments Argomenti azione
     * @return array Risposta SOAP
     */
    public function invokeSoapAction(LanDevice $lanDevice, string $serviceType, string $action, array $arguments = []): array
    {
        $controlUrl = $lanDevice->getServiceControlUrl($serviceType);

        if (!$controlUrl) {
            throw new \Exception("Service {$serviceType} not found on device");
        }

        // Costruisci URL completo
        $baseUrl = $lanDevice->location ? dirname($lanDevice->location) : "http://{$lanDevice->ip_address}:{$lanDevice->port}";
        $fullUrl = $controlUrl[0] === '/' ? $baseUrl . $controlUrl : $controlUrl;

        // Costruisci SOAP envelope
        $soapBody = $this->buildSoapEnvelope($serviceType, $action, $arguments);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset="utf-8"',
                'SOAPAction' => "\"{$serviceType}#{$action}\""
            ])->timeout(10)->send('POST', $fullUrl, [
                'body' => $soapBody
            ]);

            if (!$response->successful()) {
                throw new \Exception("SOAP request failed: " . $response->status());
            }

            return $this->parseSoapResponse($response->body());

        } catch (\Exception $e) {
            Log::error("TR-64: SOAP action failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Costruisce SOAP envelope per azione UPnP
     * Build SOAP envelope for UPnP action
     */
    private function buildSoapEnvelope(string $serviceType, string $action, array $arguments): string
    {
        $argsXml = '';
        foreach ($arguments as $name => $value) {
            $argsXml .= "<{$name}>" . htmlspecialchars($value) . "</{$name}>";
        }

        return <<<XML
<?xml version="1.0"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
    <s:Body>
        <u:{$action} xmlns:u="{$serviceType}">
            {$argsXml}
        </u:{$action}>
    </s:Body>
</s:Envelope>
XML;
    }

    /**
     * Parse SOAP response XML
     * Parse SOAP response XML
     */
    private function parseSoapResponse(string $xmlString): array
    {
        $xml = simplexml_load_string($xmlString);
        if (!$xml) {
            return [];
        }

        $xml->registerXPathNamespace('s', 'http://schemas.xmlsoap.org/soap/envelope/');
        $body = $xml->xpath('//s:Body');

        if (empty($body)) {
            return [];
        }

        // Converti il body in array
        $result = [];
        foreach ($body[0]->children() as $child) {
            foreach ($child->children() as $param) {
                $result[$param->getName()] = (string)$param;
            }
        }

        return $result;
    }

    /**
     * Marca dispositivi offline se non visti da tempo
     * Mark devices offline if not seen for a while
     */
    public function cleanupStaleDevices(CpeDevice $cpeDevice, int $minutesThreshold = 10): int
    {
        $count = $cpeDevice->lanDevices()
            ->where('status', 'active')
            ->where('last_seen', '<', now()->subMinutes($minutesThreshold))
            ->update(['status' => 'offline']);

        if ($count > 0) {
            Log::info("TR-64: Marked {$count} LAN devices as offline for CPE {$cpeDevice->serial_number}");
        }

        return $count;
    }
}
