<?php

namespace App\Services;

use App\Models\CpeDevice;
use App\Models\DeviceParameter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * TR-181 (Device:2 Data Model) Service
 * 
 * Implements BBF TR-181 Issue 2 standard for CPE WAN Management Protocol
 * Complete device data model for broadband residential gateways
 * 
 * Key Namespaces:
 * - Device.DeviceInfo.* - Device identification and status
 * - Device.ManagementServer.* - ACS connection and management
 * - Device.Time.* - Time configuration and NTP
 * - Device.LAN.* - LAN-side interfaces and configuration
 * - Device.WiFi.* - WiFi radios, SSIDs, and access points
 * - Device.IP.* - IP layer configuration
 * - Device.DHCPv4.* - DHCP server and client
 * - Device.Hosts.* - Connected hosts tracking
 * 
 * Note: Routing, Firewall, NAT, DNS namespaces planned for future implementation
 */
class TR181Service
{
    /**
     * Cache for device parameters to avoid N+1 queries
     * Keyed by device_id to prevent cross-device corruption
     */
    private array $parameterCache = [];

    /**
     * Preload all parameters for a device into memory cache
     * Eliminates N+1 query problem for fleet-scale performance
     * FIXED: Now device-aware to prevent cross-device data corruption
     */
    private function loadParameterCache(CpeDevice $device): void
    {
        $deviceId = $device->id;

        if (!isset($this->parameterCache[$deviceId])) {
            $this->parameterCache[$deviceId] = DeviceParameter::where('cpe_device_id', $deviceId)
                ->get()
                ->keyBy('parameter_name');
        }
    }

    /**
     * Clear parameter cache for specific device or all devices
     */
    public function clearCache(?int $deviceId = null): void
    {
        if ($deviceId === null) {
            $this->parameterCache = [];
        } else {
            unset($this->parameterCache[$deviceId]);
        }
    }

    /**
     * Get Device.DeviceInfo parameters
     * Provides device identification, hardware/software info, status
     */
    public function getDeviceInfo(CpeDevice $device): array
    {
        $this->loadParameterCache($device);
        $params = [
            'Device.DeviceInfo.Manufacturer' => $device->manufacturer,
            'Device.DeviceInfo.ManufacturerOUI' => $device->oui,
            'Device.DeviceInfo.ModelName' => $device->product_class,
            'Device.DeviceInfo.ProductClass' => $device->product_class,
            'Device.DeviceInfo.SerialNumber' => $device->serial_number,
            'Device.DeviceInfo.HardwareVersion' => $device->hardware_version,
            'Device.DeviceInfo.SoftwareVersion' => $device->software_version,
            'Device.DeviceInfo.UpTime' => $this->getParameterValue($device, 'Device.DeviceInfo.UpTime'),
            'Device.DeviceInfo.Description' => $device->description,
            'Device.DeviceInfo.Status' => $this->mapDeviceStatus($device->status),
        ];

        return $params;
    }

    /**
     * Get Device.ManagementServer parameters
     * ACS connection settings, periodic inform, credentials
     */
    public function getManagementServer(CpeDevice $device): array
    {
        return [
            'Device.ManagementServer.URL' => config('acs.server_url'),
            'Device.ManagementServer.Username' => $device->username ?? config('acs.default_username'),
            'Device.ManagementServer.PeriodicInformEnable' => true,
            'Device.ManagementServer.PeriodicInformInterval' => $device->inform_interval ?? 300,
            'Device.ManagementServer.ConnectionRequestURL' => $device->connection_request_url,
            'Device.ManagementServer.ConnectionRequestUsername' => $device->connection_request_username,
            'Device.ManagementServer.UpgradesManaged' => true,
            'Device.ManagementServer.ParameterKey' => $device->parameter_key,
        ];
    }

    /**
     * Set Management Server configuration
     */
    public function setManagementServer(CpeDevice $device, array $params): bool
    {
        $updates = [];

        if (isset($params['PeriodicInformInterval'])) {
            $updates['inform_interval'] = (int) $params['PeriodicInformInterval'];
        }

        if (isset($params['ConnectionRequestUsername'])) {
            $updates['connection_request_username'] = $params['ConnectionRequestUsername'];
        }

        if (isset($params['ConnectionRequestPassword'])) {
            $updates['connection_request_password'] = bcrypt($params['ConnectionRequestPassword']);
        }

        if (!empty($updates)) {
            $device->update($updates);
        }

        return true;
    }

    /**
     * Get Device.Time parameters
     * NTP configuration and current time
     */
    public function getTimeConfiguration(CpeDevice $device): array
    {
        return [
            'Device.Time.Enable' => true,
            'Device.Time.Status' => 'Synchronized',
            'Device.Time.NTPServer1' => $this->getParameterValue($device, 'Device.Time.NTPServer1', 'pool.ntp.org'),
            'Device.Time.NTPServer2' => $this->getParameterValue($device, 'Device.Time.NTPServer2', 'time.google.com'),
            'Device.Time.CurrentLocalTime' => now()->toIso8601String(),
            'Device.Time.LocalTimeZone' => config('app.timezone', 'UTC'),
        ];
    }

    /**
     * Get Device.WiFi parameters
     * Radio configuration, SSIDs, security settings
     */
    public function getWiFiConfiguration(CpeDevice $device): array
    {
        $radioCount = (int) $this->getParameterValue($device, 'Device.WiFi.RadioNumberOfEntries', 0);
        $ssidCount = (int) $this->getParameterValue($device, 'Device.WiFi.SSIDNumberOfEntries', 0);

        $wifi = [
            'Device.WiFi.RadioNumberOfEntries' => $radioCount,
            'Device.WiFi.SSIDNumberOfEntries' => $ssidCount,
            'Device.WiFi.AccessPointNumberOfEntries' => $ssidCount,
        ];

        // Get radio configurations
        for ($i = 1; $i <= $radioCount; $i++) {
            $prefix = "Device.WiFi.Radio.{$i}.";
            $wifi[$prefix . 'Enable'] = $this->getParameterValue($device, $prefix . 'Enable', true);
            $wifi[$prefix . 'Status'] = $this->getParameterValue($device, $prefix . 'Status', 'Up');
            $wifi[$prefix . 'Channel'] = $this->getParameterValue($device, $prefix . 'Channel');
            $wifi[$prefix . 'OperatingFrequencyBand'] = $this->getParameterValue($device, $prefix . 'OperatingFrequencyBand', '2.4GHz');
            $wifi[$prefix . 'OperatingStandards'] = $this->getParameterValue($device, $prefix . 'OperatingStandards', 'n');
            $wifi[$prefix . 'TransmitPower'] = $this->getParameterValue($device, $prefix . 'TransmitPower', 100);
        }

        // Get SSID configurations
        for ($i = 1; $i <= $ssidCount; $i++) {
            $prefix = "Device.WiFi.SSID.{$i}.";
            $wifi[$prefix . 'Enable'] = $this->getParameterValue($device, $prefix . 'Enable', true);
            $wifi[$prefix . 'Status'] = $this->getParameterValue($device, $prefix . 'Status', 'Up');
            $wifi[$prefix . 'SSID'] = $this->getParameterValue($device, $prefix . 'SSID');
            $wifi[$prefix . 'BSSID'] = $this->getParameterValue($device, $prefix . 'BSSID');
        }

        return $wifi;
    }

    /**
     * Get Device.LAN parameters
     * LAN interfaces, DHCP, IP configuration
     */
    public function getLANConfiguration(CpeDevice $device): array
    {
        $interfaceCount = (int) $this->getParameterValue($device, 'Device.LAN.InterfaceNumberOfEntries', 1);

        $lan = [
            'Device.LAN.InterfaceNumberOfEntries' => $interfaceCount,
        ];

        for ($i = 1; $i <= $interfaceCount; $i++) {
            $prefix = "Device.LAN.Interface.{$i}.";
            $lan[$prefix . 'Enable'] = $this->getParameterValue($device, $prefix . 'Enable', true);
            $lan[$prefix . 'Status'] = $this->getParameterValue($device, $prefix . 'Status', 'Up');
            $lan[$prefix . 'MACAddress'] = $this->getParameterValue($device, $prefix . 'MACAddress');
            $lan[$prefix . 'IPAddress'] = $this->getParameterValue($device, $prefix . 'IPAddress', '192.168.1.1');
            $lan[$prefix . 'SubnetMask'] = $this->getParameterValue($device, $prefix . 'SubnetMask', '255.255.255.0');
        }

        return $lan;
    }

    /**
     * Get Device.DHCPv4 parameters
     * DHCP server and client configuration
     */
    public function getDHCPv4Configuration(CpeDevice $device): array
    {
        $serverPoolCount = (int) $this->getParameterValue($device, 'Device.DHCPv4.Server.PoolNumberOfEntries', 1);

        $dhcp = [
            'Device.DHCPv4.Server.Enable' => $this->getParameterValue($device, 'Device.DHCPv4.Server.Enable', true),
            'Device.DHCPv4.Server.PoolNumberOfEntries' => $serverPoolCount,
        ];

        for ($i = 1; $i <= $serverPoolCount; $i++) {
            $prefix = "Device.DHCPv4.Server.Pool.{$i}.";
            $dhcp[$prefix . 'Enable'] = $this->getParameterValue($device, $prefix . 'Enable', true);
            $dhcp[$prefix . 'MinAddress'] = $this->getParameterValue($device, $prefix . 'MinAddress', '192.168.1.100');
            $dhcp[$prefix . 'MaxAddress'] = $this->getParameterValue($device, $prefix . 'MaxAddress', '192.168.1.200');
            $dhcp[$prefix . 'SubnetMask'] = $this->getParameterValue($device, $prefix . 'SubnetMask', '255.255.255.0');
            $dhcp[$prefix . 'DNSServers'] = $this->getParameterValue($device, $prefix . 'DNSServers', '8.8.8.8,8.8.4.4');
            $dhcp[$prefix . 'LeaseTime'] = $this->getParameterValue($device, $prefix . 'LeaseTime', 86400);
        }

        return $dhcp;
    }

    /**
     * Get Device.Hosts parameters
     * Connected hosts tracking - FIXED: Returns flattened TR-181 parameters
     */
    public function getConnectedHosts(CpeDevice $device): array
    {
        $hosts = $device->networkClients()->get();
        $params = [
            'Device.Hosts.HostNumberOfEntries' => $hosts->count(),
        ];

        // Flatten host parameters to TR-181 compliant key/value pairs
        foreach ($hosts as $index => $host) {
            $i = $index + 1;
            $params["Device.Hosts.Host.{$i}.PhysAddress"] = $host->mac_address;
            $params["Device.Hosts.Host.{$i}.IPAddress"] = $host->ip_address;
            $params["Device.Hosts.Host.{$i}.HostName"] = $host->hostname;
            $params["Device.Hosts.Host.{$i}.Active"] = $host->is_active;
            $params["Device.Hosts.Host.{$i}.InterfaceType"] = $host->interface_type;
            $params["Device.Hosts.Host.{$i}.Layer2Interface"] = $host->layer2_interface;
        }

        return $params;
    }

    /**
     * Get Device.IP parameters
     * IP layer configuration
     */
    public function getIPConfiguration(CpeDevice $device): array
    {
        return [
            'Device.IP.IPv4Enable' => true,
            'Device.IP.IPv4Status' => 'Enabled',
            'Device.IP.IPv6Enable' => $this->getParameterValue($device, 'Device.IP.IPv6Enable', false),
            'Device.IP.IPv6Status' => $this->getParameterValue($device, 'Device.IP.IPv6Status', 'Disabled'),
            'Device.IP.InterfaceNumberOfEntries' => $this->getParameterValue($device, 'Device.IP.InterfaceNumberOfEntries', 2),
        ];
    }

    /**
     * Get all TR-181 parameters for a device
     * Comprehensive data model export
     * Optimized: Single bulk load of all parameters
     */
    public function getAllParameters(CpeDevice $device): array
    {
        // Preload all parameters once
        $this->loadParameterCache($device);

        return array_merge(
            $this->getDeviceInfo($device),
            $this->getManagementServer($device),
            $this->getTimeConfiguration($device),
            $this->getWiFiConfiguration($device),
            $this->getLANConfiguration($device),
            $this->getDHCPv4Configuration($device),
            $this->getIPConfiguration($device),
            $this->getConnectedHosts($device)
        );
    }

    /**
     * Set TR-181 parameters on device
     * 
     * @param CpeDevice $device
     * @param array $parameters Key-value pairs of TR-181 parameters
     * @return array Results with success/failure per parameter
     */
    public function setParameters(CpeDevice $device, array $parameters): array
    {
        $results = [];

        foreach ($parameters as $paramName => $paramValue) {
            try {
                // Route to appropriate handler based on parameter namespace
                if (str_starts_with($paramName, 'Device.ManagementServer.')) {
                    $subParam = str_replace('Device.ManagementServer.', '', $paramName);
                    $this->setManagementServer($device, [$subParam => $paramValue]);
                    $results[$paramName] = ['status' => 'success', 'value' => $paramValue];
                } else {
                    // Store in device_parameters table
                    $this->setParameterValue($device, $paramName, $paramValue);
                    $results[$paramName] = ['status' => 'success', 'value' => $paramValue];
                }
            } catch (\Exception $e) {
                Log::error("TR-181 SetParameterValues failed for {$paramName}: " . $e->getMessage());
                $results[$paramName] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Validate TR-181 parameter name
     * FIXED: Only validates implemented namespaces
     */
    public function isValidParameter(string $paramName): bool
    {
        $validPrefixes = [
            'Device.DeviceInfo.',
            'Device.ManagementServer.',
            'Device.Time.',
            'Device.LAN.',
            'Device.WiFi.',
            'Device.IP.',
            'Device.DHCPv4.',
            'Device.Hosts.',
        ];

        foreach ($validPrefixes as $prefix) {
            if (str_starts_with($paramName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper: Get parameter value from device_parameters table
     * Uses in-memory cache to avoid N+1 queries
     * FIXED: Device-scoped cache lookup
     */
    private function getParameterValue(CpeDevice $device, string $paramName, $default = null)
    {
        $this->loadParameterCache($device);

        $deviceId = $device->id;
        $param = $this->parameterCache[$deviceId]->get($paramName);

        return $param ? $param->parameter_value : $default;
    }

    /**
     * Helper: Set parameter value in device_parameters table
     * FIXED: Invalidates cache after write to ensure cache coherency
     */
    private function setParameterValue(CpeDevice $device, string $paramName, $paramValue): void
    {
        $updatedParam = DeviceParameter::updateOrCreate(
            [
                'cpe_device_id' => $device->id,
                'parameter_name' => $paramName,
            ],
            [
                'parameter_value' => $paramValue,
                'parameter_type' => $this->detectParameterType($paramValue),
                'writable' => $this->isWritableParameter($paramName),
            ]
        );

        // Update cache immediately to maintain coherency
        $deviceId = $device->id;
        if (isset($this->parameterCache[$deviceId])) {
            $this->parameterCache[$deviceId]->put($paramName, $updatedParam);
        }
    }

    /**
     * Detect parameter type from value
     */
    private function detectParameterType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'int';
        }
        if (is_numeric($value)) {
            return 'unsignedInt';
        }
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return 'string'; // or 'IPAddress' if you want specific type
        }
        return 'string';
    }

    /**
     * Check if parameter is writable
     */
    private function isWritableParameter(string $paramName): bool
    {
        // Read-only parameters
        $readOnlyPrefixes = [
            'Device.DeviceInfo.Manufacturer',
            'Device.DeviceInfo.ManufacturerOUI',
            'Device.DeviceInfo.SerialNumber',
            'Device.DeviceInfo.HardwareVersion',
            'Device.DeviceInfo.UpTime',
            'Device.Hosts.',
        ];

        foreach ($readOnlyPrefixes as $prefix) {
            if (str_starts_with($paramName, $prefix)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Map device status to TR-181 standard values
     */
    private function mapDeviceStatus(string $status): string
    {
        $statusMap = [
            'online' => 'Online',
            'offline' => 'Offline',
            'error' => 'Error',
            'init' => 'Initializing',
        ];

        return $statusMap[$status] ?? 'Unknown';
    }
}
