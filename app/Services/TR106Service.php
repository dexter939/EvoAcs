<?php

namespace App\Services;

use App\Models\CpeDevice;
use Illuminate\Support\Facades\Log;

/**
 * TR-106 Data Model Template Service (Issue 1, Amendment 9)
 * 
 * BBF-compliant implementation for data model template management.
 * Provides base template definitions, parameter inheritance, and versioning.
 * 
 * Features:
 * - Data model template definition and versioning
 * - Parameter inheritance hierarchies
 * - Default value management
 * - Constraint validation (ranges, enums, patterns)
 * - Template import/export (XML)
 * - Vendor extension support
 * - Multi-version compatibility
 * 
 * @package App\Services
 * @version 1.9 (TR-106 Issue 1 Amendment 9)
 */
class TR106Service
{
    /**
     * BBF data model versions supported
     */
    const SUPPORTED_VERSIONS = [
        'Device:2.15' => ['release' => '2021-07', 'components' => 200],
        'Device:2.14' => ['release' => '2020-11', 'components' => 195],
        'Device:2.13' => ['release' => '2019-09', 'components' => 190],
        'InternetGatewayDevice:1.14' => ['release' => '2018-05', 'components' => 150],
    ];

    /**
     * BBF parameter data types
     */
    const DATA_TYPES = [
        'string' => ['max_length' => 65535],
        'int' => ['min' => -2147483648, 'max' => 2147483647],
        'unsignedInt' => ['min' => 0, 'max' => 4294967295],
        'long' => ['min' => -9223372036854775808, 'max' => 9223372036854775807],
        'unsignedLong' => ['min' => 0, 'max' => 18446744073709551615],
        'boolean' => ['values' => ['true', 'false']],
        'dateTime' => ['format' => 'ISO8601'],
        'base64' => ['encoding' => 'base64'],
        'hexBinary' => ['encoding' => 'hexadecimal'],
        'IPAddress' => ['pattern' => '/^(\d{1,3}\.){3}\d{1,3}$/'],
        'MACAddress' => ['pattern' => '/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'],
        'UUID' => ['pattern' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'],
    ];

    /**
     * Access types for parameters
     */
    const ACCESS_TYPES = [
        'readOnly' => 'Parameter value cannot be modified by the ACS',
        'readWrite' => 'Parameter value can be read and modified by the ACS',
        'writeOnly' => 'Parameter value can only be written, not read',
    ];

    /**
     * Get data model template definition
     */
    public function getTemplateDefinition(string $modelVersion = 'Device:2.15'): array
    {
        if (!isset(self::SUPPORTED_VERSIONS[$modelVersion])) {
            throw new \InvalidArgumentException("Unsupported data model version: {$modelVersion}");
        }

        return [
            'version' => $modelVersion,
            'release_date' => self::SUPPORTED_VERSIONS[$modelVersion]['release'],
            'total_components' => self::SUPPORTED_VERSIONS[$modelVersion]['components'],
            'supported_data_types' => array_keys(self::DATA_TYPES),
            'access_types' => array_keys(self::ACCESS_TYPES),
            'template_url' => "https://cwmp-data-models.broadband-forum.org/{$modelVersion}",
        ];
    }

    /**
     * Get parameter inheritance chain
     */
    public function getParameterInheritance(string $parameterPath): array
    {
        $parts = explode('.', $parameterPath);
        $chain = [];

        $currentPath = '';
        foreach ($parts as $part) {
            $currentPath .= ($currentPath ? '.' : '') . $part;
            $chain[] = [
                'path' => $currentPath,
                'level' => count(explode('.', $currentPath)),
                'is_object' => !preg_match('/\{i\}$/', $part),
            ];
        }

        return $chain;
    }

    /**
     * Get default values for a parameter type
     */
    public function getDefaultValue(string $dataType, array $constraints = []): mixed
    {
        return match($dataType) {
            'string' => $constraints['default'] ?? '',
            'int', 'unsignedInt' => $constraints['default'] ?? 0,
            'long', 'unsignedLong' => $constraints['default'] ?? 0,
            'boolean' => $constraints['default'] ?? false,
            'dateTime' => now()->toIso8601String(),
            'base64' => '',
            'hexBinary' => '',
            'IPAddress' => '0.0.0.0',
            'MACAddress' => '00:00:00:00:00:00',
            'UUID' => \Illuminate\Support\Str::uuid()->toString(),
            default => null,
        };
    }

    /**
     * Validate parameter value against data type and constraints
     */
    public function validateParameterValue(string $dataType, $value, array $constraints = []): array
    {
        $errors = [];

        switch ($dataType) {
            case 'string':
                if (!is_string($value)) {
                    $errors[] = "Value must be a string";
                }
                if (isset($constraints['maxLength']) && strlen($value) > $constraints['maxLength']) {
                    $errors[] = "String length exceeds maximum of {$constraints['maxLength']}";
                }
                if (isset($constraints['minLength']) && strlen($value) < $constraints['minLength']) {
                    $errors[] = "String length below minimum of {$constraints['minLength']}";
                }
                if (isset($constraints['pattern']) && !preg_match($constraints['pattern'], $value)) {
                    $errors[] = "Value does not match required pattern";
                }
                break;

            case 'int':
            case 'unsignedInt':
                if (!is_numeric($value)) {
                    $errors[] = "Value must be numeric";
                } else {
                    $numValue = intval($value);
                    if (isset($constraints['min']) && $numValue < $constraints['min']) {
                        $errors[] = "Value {$numValue} is below minimum {$constraints['min']}";
                    }
                    if (isset($constraints['max']) && $numValue > $constraints['max']) {
                        $errors[] = "Value {$numValue} exceeds maximum {$constraints['max']}";
                    }
                }
                break;

            case 'boolean':
                if (!in_array($value, ['true', 'false', true, false, 0, 1], true)) {
                    $errors[] = "Value must be boolean (true/false)";
                }
                break;

            case 'dateTime':
                try {
                    new \DateTime($value);
                } catch (\Exception $e) {
                    $errors[] = "Invalid ISO8601 dateTime format";
                }
                break;

            case 'IPAddress':
                if (!filter_var($value, FILTER_VALIDATE_IP)) {
                    $errors[] = "Invalid IP address format";
                }
                break;

            case 'MACAddress':
                if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/', $value)) {
                    $errors[] = "Invalid MAC address format";
                }
                break;

            case 'UUID':
                if (!\Illuminate\Support\Str::isUuid($value)) {
                    $errors[] = "Invalid UUID format";
                }
                break;
        }

        if (isset($constraints['enumeration']) && !in_array($value, $constraints['enumeration'])) {
            $errors[] = "Value must be one of: " . implode(', ', $constraints['enumeration']);
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
            'data_type' => $dataType,
            'value' => $value,
        ];
    }

    /**
     * Import data model from XML
     */
    public function importDataModelXml(string $xmlContent): array
    {
        try {
            $xml = simplexml_load_string($xmlContent);
            
            if (!$xml) {
                throw new \Exception("Failed to parse XML content");
            }

            $dataModel = [
                'name' => (string) ($xml['name'] ?? 'Unknown'),
                'version' => (string) ($xml['version'] ?? '1.0'),
                'vendor' => (string) ($xml['vendor'] ?? 'Generic'),
                'parameters' => [],
                'objects' => [],
            ];

            foreach ($xml->xpath('//parameter') as $param) {
                $paramData = [
                    'name' => (string) $param['name'],
                    'type' => (string) $param['type'],
                    'access' => (string) $param['access'],
                    'default' => (string) ($param['default'] ?? ''),
                    'description' => (string) ($param->description ?? ''),
                ];
                
                $dataModel['parameters'][] = $paramData;
            }

            foreach ($xml->xpath('//object') as $obj) {
                $objData = [
                    'name' => (string) $obj['name'],
                    'access' => (string) $obj['access'],
                    'minEntries' => (int) ($obj['minEntries'] ?? 0),
                    'maxEntries' => (int) ($obj['maxEntries'] ?? 1),
                    'description' => (string) ($obj->description ?? ''),
                ];
                
                $dataModel['objects'][] = $objData;
            }

            return [
                'status' => 'success',
                'data_model' => $dataModel,
                'parameters_imported' => count($dataModel['parameters']),
                'objects_imported' => count($dataModel['objects']),
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => "XML import failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Export data model to XML
     */
    public function exportDataModelXml(array $dataModel): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><dataModel></dataModel>');
        
        $xml->addAttribute('name', $dataModel['name'] ?? 'CustomDataModel');
        $xml->addAttribute('version', $dataModel['version'] ?? '1.0');
        $xml->addAttribute('vendor', $dataModel['vendor'] ?? 'Custom');

        if (isset($dataModel['parameters'])) {
            $parametersNode = $xml->addChild('parameters');
            
            foreach ($dataModel['parameters'] as $param) {
                $paramNode = $parametersNode->addChild('parameter');
                $paramNode->addAttribute('name', $param['name']);
                $paramNode->addAttribute('type', $param['type']);
                $paramNode->addAttribute('access', $param['access']);
                
                if (isset($param['default'])) {
                    $paramNode->addAttribute('default', $param['default']);
                }
                
                if (isset($param['description'])) {
                    $paramNode->addChild('description', htmlspecialchars($param['description']));
                }
            }
        }

        return $xml->asXML();
    }

    /**
     * Get parameter constraints definition
     */
    public function getParameterConstraints(string $dataType, array $customConstraints = []): array
    {
        $baseConstraints = self::DATA_TYPES[$dataType] ?? [];
        
        return array_merge($baseConstraints, $customConstraints);
    }

    /**
     * Check version compatibility
     */
    public function checkVersionCompatibility(string $deviceVersion, string $requiredVersion): array
    {
        $deviceParts = explode(':', $deviceVersion);
        $requiredParts = explode(':', $requiredVersion);

        if ($deviceParts[0] !== $requiredParts[0]) {
            return [
                'compatible' => false,
                'reason' => 'Different data model root (Device vs InternetGatewayDevice)',
            ];
        }

        $deviceVersionNum = floatval($deviceParts[1] ?? 0);
        $requiredVersionNum = floatval($requiredParts[1] ?? 0);

        return [
            'compatible' => $deviceVersionNum >= $requiredVersionNum,
            'device_version' => $deviceVersion,
            'required_version' => $requiredVersion,
            'reason' => $deviceVersionNum >= $requiredVersionNum 
                ? 'Version compatible' 
                : 'Device version too old',
        ];
    }

    /**
     * Generate vendor extension namespace
     */
    public function generateVendorExtension(string $vendorOUI, string $parameterName): string
    {
        return "X_{$vendorOUI}_{$parameterName}";
    }

    /**
     * Validate vendor extension format
     */
    public function isValidVendorExtension(string $parameterName): bool
    {
        return preg_match('/^X_[A-F0-9]{6}_/', $parameterName) === 1;
    }

    /**
     * Get data type information
     */
    public function getDataTypeInfo(string $dataType): ?array
    {
        return self::DATA_TYPES[$dataType] ?? null;
    }

    /**
     * Get all supported data types
     */
    public function getSupportedDataTypes(): array
    {
        return array_map(function($type, $info) {
            return [
                'type' => $type,
                'info' => $info,
            ];
        }, array_keys(self::DATA_TYPES), self::DATA_TYPES);
    }
}
