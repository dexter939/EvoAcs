<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ParameterValidationService
{
    public function validateParameters(int $dataModelId, array $parameters, ?string $deviceVersion = null): array
    {
        $errors = [];
        $warnings = [];
        $validatedParams = [];

        foreach ($parameters as $paramPath => $value) {
            $paramDef = DB::table('tr069_parameters')
                ->where('data_model_id', $dataModelId)
                ->where('parameter_path', $paramPath)
                ->first();

            if (!$paramDef) {
                $templatePath = $this->convertToTemplatePath($paramPath);
                $paramDef = DB::table('tr069_parameters')
                    ->where('data_model_id', $dataModelId)
                    ->where('parameter_path', $templatePath)
                    ->first();
                
                if (!$paramDef) {
                    $errors[] = [
                        'parameter' => $paramPath,
                        'error' => 'Parameter not found in data model',
                        'severity' => 'error',
                        'suggestion' => 'Check parameter path spelling or verify it exists in the data model specification'
                    ];
                    continue;
                }
            }
            
            if ($deviceVersion && $paramDef->min_version) {
                if (version_compare($deviceVersion, $paramDef->min_version, '<')) {
                    $warnings[] = [
                        'parameter' => $paramPath,
                        'warning' => "Parameter requires minimum version {$paramDef->min_version} (device version: {$deviceVersion})",
                        'severity' => 'warning',
                        'suggestion' => "Upgrade device firmware to version >= {$paramDef->min_version} or remove this parameter"
                    ];
                }
            }

            if ($paramDef->is_object) {
                $errors[] = [
                    'parameter' => $paramPath,
                    'error' => 'Cannot set value on object type (use child parameters)',
                    'severity' => 'error'
                ];
                continue;
            }

            if ($paramDef->access_type === 'R') {
                $warnings[] = [
                    'parameter' => $paramPath,
                    'warning' => 'Parameter is read-only (cannot be modified)',
                    'severity' => 'warning',
                    'suggestion' => 'Remove this parameter from configuration or use read-only access'
                ];
            }

            $validationResult = $this->validateValue($value, $paramDef);
            
            if (!$validationResult['valid']) {
                $error = [
                    'parameter' => $paramPath,
                    'error' => $validationResult['message'],
                    'severity' => 'error'
                ];
                
                if (isset($validationResult['suggestion'])) {
                    $error['suggestion'] = $validationResult['suggestion'];
                }
                
                if (isset($validationResult['allowed_values'])) {
                    $error['allowed_values'] = $validationResult['allowed_values'];
                }
                
                $errors[] = $error;
            } else {
                $validatedParams[$paramPath] = [
                    'value' => $value,
                    'type' => $paramDef->parameter_type,
                    'access' => $paramDef->access_type,
                    'validated' => true
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'validated_parameters' => $validatedParams,
            'total_checked' => count($parameters)
        ];
    }

    private function validateValue($value, $paramDef): array
    {
        if ($paramDef->validation_rules) {
            $rules = json_decode($paramDef->validation_rules, true);
            
            if (isset($rules['enumeration'])) {
                $allowedValues = $rules['enumeration'];
                if (!in_array($value, $allowedValues, true)) {
                    return [
                        'valid' => false,
                        'message' => "Value must be one of the allowed enumeration values",
                        'suggestion' => "Allowed values: " . implode(', ', $allowedValues),
                        'allowed_values' => $allowedValues
                    ];
                }
            }
            
            if (isset($rules['size'])) {
                $minLength = $rules['size']['min'] ?? null;
                $maxLength = $rules['size']['max'] ?? null;
                $valueLength = is_string($value) ? strlen($value) : 0;
                
                if ($minLength && $valueLength < $minLength) {
                    return [
                        'valid' => false,
                        'message' => "Value length ({$valueLength}) is less than minimum ({$minLength})"
                    ];
                }
                
                if ($maxLength && $valueLength > $maxLength) {
                    return [
                        'valid' => false,
                        'message' => "Value length ({$valueLength}) exceeds maximum ({$maxLength})"
                    ];
                }
            }
            
            if (isset($rules['pattern'])) {
                $pattern = $rules['pattern'];
                if (!preg_match("/{$pattern}/", $value)) {
                    return [
                        'valid' => false,
                        'message' => "Value does not match required pattern: {$pattern}"
                    ];
                }
            }
            
            if (isset($rules['range'])) {
                $min = $rules['range']['min'] ?? null;
                $max = $rules['range']['max'] ?? null;
                $units = $rules['range']['units'] ?? null;
                
                $numericValue = $value;
                if ($units) {
                    $numericValue = $this->normalizeValueWithUnits($value, $units);
                }
                
                if ($min !== null && $numericValue < $min) {
                    $message = $units 
                        ? "Value ({$value}) is less than minimum ({$min} {$units})"
                        : "Value ({$value}) is less than minimum ({$min})";
                    return [
                        'valid' => false,
                        'message' => $message,
                        'suggestion' => $units ? "Value must be >= {$min} {$units}" : "Value must be >= {$min}"
                    ];
                }
                
                if ($max !== null && $numericValue > $max) {
                    $message = $units 
                        ? "Value ({$value}) exceeds maximum ({$max} {$units})"
                        : "Value ({$value}) exceeds maximum ({$max})";
                    return [
                        'valid' => false,
                        'message' => $message,
                        'suggestion' => $units ? "Value must be <= {$max} {$units}" : "Value must be <= {$max}"
                    ];
                }
            }
            
            if (isset($rules['units'])) {
                $requiredUnits = $rules['units'];
                if (!$this->hasValidUnits($value, $requiredUnits)) {
                    return [
                        'valid' => false,
                        'message' => "Value must include valid units",
                        'suggestion' => "Expected units: {$requiredUnits} (e.g., 100{$requiredUnits})"
                    ];
                }
            }
        }

        $typeValidation = $this->validateType($value, $paramDef->parameter_type);
        if (!$typeValidation['valid']) {
            return $typeValidation;
        }

        return ['valid' => true, 'message' => 'Valid'];
    }

    private function validateType($value, $type): array
    {
        if (empty($type)) {
            return ['valid' => true, 'message' => 'No type specified'];
        }

        switch ($type) {
            case 'boolean':
                if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
                    return [
                        'valid' => false, 
                        'message' => 'Value must be boolean (true/false or 0/1)',
                        'suggestion' => 'Use true, false, 0, or 1'
                    ];
                }
                break;
                
            case 'int':
            case 'unsignedInt':
                $valueStr = (string)$value;
                if (!preg_match('/^-?[0-9]+$/', $valueStr)) {
                    return [
                        'valid' => false, 
                        'message' => 'Value must be an integer (no decimals, spaces, or scientific notation)',
                        'suggestion' => 'Use whole numbers without decimals (e.g., 42, -10, 0)'
                    ];
                }
                
                if ($type === 'unsignedInt') {
                    if ($valueStr[0] === '-') {
                        return [
                            'valid' => false, 
                            'message' => 'Value must be unsigned (positive integer)',
                            'suggestion' => 'Use positive whole numbers only (e.g., 0, 1, 42)'
                        ];
                    }
                    if ($this->compareNumericStrings($valueStr, '4294967295') > 0) {
                        return [
                            'valid' => false, 
                            'message' => 'Value exceeds 32-bit unsigned int range (0 to 4294967295)',
                            'suggestion' => 'Use values between 0 and 4294967295'
                        ];
                    }
                } else {
                    if ($this->compareNumericStrings($valueStr, '-2147483648') < 0 || $this->compareNumericStrings($valueStr, '2147483647') > 0) {
                        return [
                            'valid' => false, 
                            'message' => 'Value exceeds 32-bit signed int range (-2147483648 to 2147483647)',
                            'suggestion' => 'Use values between -2147483648 and 2147483647'
                        ];
                    }
                }
                break;
                
            case 'long':
            case 'unsignedLong':
                $valueStr = (string)$value;
                
                if ($type === 'long') {
                    if (!preg_match('/^-?[0-9]+$/', $valueStr)) {
                        return [
                            'valid' => false, 
                            'message' => 'Value must be a long integer (no decimals or scientific notation)',
                            'suggestion' => 'Use whole numbers (64-bit range: -9223372036854775808 to 9223372036854775807)'
                        ];
                    }
                    
                    if ($this->compareNumericStrings($valueStr, '-9223372036854775808') < 0 || $this->compareNumericStrings($valueStr, '9223372036854775807') > 0) {
                        return [
                            'valid' => false, 
                            'message' => 'Value exceeds 64-bit signed long range',
                            'suggestion' => 'Use values between -9223372036854775808 and 9223372036854775807'
                        ];
                    }
                } else {
                    if (!preg_match('/^[0-9]+$/', $valueStr)) {
                        return [
                            'valid' => false, 
                            'message' => 'Value must be an unsigned long integer (no decimals, negatives, or scientific notation)',
                            'suggestion' => 'Use positive whole numbers (0 to 18446744073709551615)'
                        ];
                    }
                    
                    if ($this->compareNumericStrings($valueStr, '18446744073709551615') > 0) {
                        return [
                            'valid' => false, 
                            'message' => 'Value exceeds 64-bit unsigned long range',
                            'suggestion' => 'Use values between 0 and 18446744073709551615'
                        ];
                    }
                }
                break;
                
            case 'string':
                if (!is_string($value) && !is_numeric($value)) {
                    return [
                        'valid' => false, 
                        'message' => 'Value must be a string',
                        'suggestion' => 'Use text values'
                    ];
                }
                break;
                
            case 'dateTime':
                if (!strtotime($value)) {
                    return [
                        'valid' => false, 
                        'message' => 'Value must be a valid datetime (ISO 8601 format)',
                        'suggestion' => 'Use format: YYYY-MM-DDTHH:MM:SS or YYYY-MM-DD HH:MM:SS (e.g., 2025-10-19T17:30:00)'
                    ];
                }
                break;
                
            case 'base64':
                if (!$this->isValidBase64($value)) {
                    return [
                        'valid' => false, 
                        'message' => 'Value must be valid Base64 encoded data',
                        'suggestion' => 'Use Base64 encoding (characters: A-Z, a-z, 0-9, +, /, =)'
                    ];
                }
                break;
                
            case 'hexBinary':
                if (!$this->isValidHexBinary($value)) {
                    return [
                        'valid' => false, 
                        'message' => 'Value must be valid hexadecimal binary (hex digits only)',
                        'suggestion' => 'Use hex characters only: 0-9, A-F (e.g., A1B2C3, FF00AA)'
                    ];
                }
                break;
                
            case 'IPAddress':
            case 'ipAddress':
                if (!$this->isValidIPAddress($value)) {
                    return [
                        'valid' => false, 
                        'message' => 'Value must be a valid IP address (IPv4 or IPv6)',
                        'suggestion' => 'IPv4: 192.168.1.1 or IPv6: 2001:0db8::1'
                    ];
                }
                break;
                
            case 'MACAddress':
            case 'macAddress':
                if (!$this->isValidMACAddress($value)) {
                    return [
                        'valid' => false, 
                        'message' => 'Value must be a valid MAC address',
                        'suggestion' => 'Use format: AA:BB:CC:DD:EE:FF or AA-BB-CC-DD-EE-FF'
                    ];
                }
                break;
                
            case 'list':
                if (!$this->isValidList($value)) {
                    return [
                        'valid' => false, 
                        'message' => 'Value must be a valid comma-separated list',
                        'suggestion' => 'Use format: value1,value2,value3 (e.g., eth0,eth1,wlan0)'
                    ];
                }
                break;
                
            default:
                return [
                    'valid' => false, 
                    'message' => "Unknown parameter type: {$type}",
                    'suggestion' => 'Check BBF data model specification for valid types'
                ];
        }

        return ['valid' => true, 'message' => 'Valid type'];
    }
    
    private function isValidBase64(string $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $value)) {
            return false;
        }
        
        $decoded = base64_decode($value, true);
        return $decoded !== false && base64_encode($decoded) === $value;
    }
    
    private function isValidHexBinary(string $value): bool
    {
        return (bool)preg_match('/^[0-9A-Fa-f]*$/', $value) && strlen($value) % 2 === 0;
    }
    
    private function isValidIPAddress(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false;
    }
    
    private function isValidMACAddress(string $value): bool
    {
        return (bool)preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value);
    }
    
    private function isValidList($value): bool
    {
        if (is_array($value)) {
            return true;
        }
        
        if (!is_string($value)) {
            return false;
        }
        
        if ($value === '') {
            return true;
        }
        
        $items = explode(',', $value);
        foreach ($items as $item) {
            $trimmed = trim($item);
            if ($trimmed === '') {
                return false;
            }
        }
        
        return count($items) > 0;
    }
    
    private function normalizeValueWithUnits($value, string $units): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        
        if (!is_string($value)) {
            return 0;
        }
        
        $unitsMap = [
            'dBm' => 1,
            'dB' => 1,
            'kbps' => 1000,
            'Mbps' => 1000000,
            'Gbps' => 1000000000,
            'KB' => 1024,
            'MB' => 1048576,
            'GB' => 1073741824,
            'ms' => 0.001,
            'seconds' => 1,
            'minutes' => 60,
            'hours' => 3600
        ];
        
        $pattern = '/^([-+]?[\d.]+)\s*(' . preg_quote($units, '/') . ')$/i';
        if (preg_match($pattern, $value, $matches)) {
            $numericPart = (float)$matches[1];
            $multiplier = $unitsMap[$units] ?? 1;
            return $numericPart * $multiplier;
        }
        
        return (float)$value;
    }
    
    private function hasValidUnits($value, string $requiredUnits): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        $pattern = '/^[-+]?[\d.]+\s*' . preg_quote($requiredUnits, '/') . '$/i';
        return (bool)preg_match($pattern, $value);
    }
    
    private function compareNumericStrings(string $a, string $b): int
    {
        $negativeA = $a[0] === '-';
        $negativeB = $b[0] === '-';
        
        if ($negativeA && !$negativeB) {
            return -1;
        }
        if (!$negativeA && $negativeB) {
            return 1;
        }
        
        if ($negativeA && $negativeB) {
            $a = substr($a, 1);
            $b = substr($b, 1);
            
            $lenA = strlen($a);
            $lenB = strlen($b);
            
            if ($lenA !== $lenB) {
                return $lenA > $lenB ? -1 : 1;
            }
            
            $cmp = strcmp($a, $b);
            return $cmp === 0 ? 0 : ($cmp > 0 ? -1 : 1);
        }
        
        $lenA = strlen($a);
        $lenB = strlen($b);
        
        if ($lenA !== $lenB) {
            return $lenA > $lenB ? 1 : -1;
        }
        
        return strcmp($a, $b);
    }

    private function convertToTemplatePath(string $path): string
    {
        return preg_replace('/\.\d+\./', '.{i}.', $path);
    }

    public function validateTemplate(int $templateId): array
    {
        $template = DB::table('configuration_templates')->where('id', $templateId)->first();
        
        if (!$template) {
            return [
                'valid' => false,
                'error' => 'Template not found'
            ];
        }

        $parameters = json_decode($template->parameters, true);
        $result = $this->validateParameters($template->data_model_id, $parameters);
        
        if ($template->validation_rules) {
            $templateRules = json_decode($template->validation_rules, true);
            $templateRuleErrors = $this->enforceTemplateRules($parameters, $templateRules);
            
            if (!empty($templateRuleErrors)) {
                $result['errors'] = array_merge($result['errors'], $templateRuleErrors);
                $result['valid'] = false;
            }
            
            $result['template_rules_applied'] = true;
            $result['template_rules'] = $templateRules;
        }
        
        return $result;
    }

    private function enforceTemplateRules(array $parameters, array $templateRules): array
    {
        $errors = [];
        
        foreach ($templateRules as $paramKey => $rules) {
            $paramPath = $this->findParameterPathByKey($parameters, $paramKey);
            
            if (!$paramPath) {
                continue;
            }
            
            $value = $parameters[$paramPath];
            
            foreach ($rules as $rule) {
                if ($rule === 'required' && (!isset($value) || $value === '' || $value === null)) {
                    $errors[] = [
                        'parameter' => $paramPath,
                        'error' => "Template rule violation: {$paramKey} is required",
                        'severity' => 'error',
                        'rule_type' => 'template'
                    ];
                }
                
                if ($rule === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false && $value !== 0) {
                    $errors[] = [
                        'parameter' => $paramPath,
                        'error' => "Template rule violation: {$paramKey} must be an integer (got {$value})",
                        'severity' => 'error',
                        'rule_type' => 'template'
                    ];
                }
                
                if (str_starts_with($rule, 'between:')) {
                    list(, $range) = explode(':', $rule);
                    list($min, $max) = explode(',', $range);
                    
                    if (is_numeric($value) && ($value < $min || $value > $max)) {
                        $errors[] = [
                            'parameter' => $paramPath,
                            'error' => "Template rule violation: {$paramKey} must be between {$min} and {$max} (got {$value})",
                            'severity' => 'error',
                            'rule_type' => 'template'
                        ];
                    }
                }
                
                if (str_starts_with($rule, 'min:')) {
                    list(, $min) = explode(':', $rule);
                    
                    if (is_string($value) && strlen($value) < $min) {
                        $errors[] = [
                            'parameter' => $paramPath,
                            'error' => "Template rule violation: {$paramKey} must be at least {$min} characters",
                            'severity' => 'error',
                            'rule_type' => 'template'
                        ];
                    }
                }
                
                if (str_starts_with($rule, 'max:')) {
                    list(, $max) = explode(':', $rule);
                    
                    if (is_string($value) && strlen($value) > $max) {
                        $errors[] = [
                            'parameter' => $paramPath,
                            'error' => "Template rule violation: {$paramKey} must be at most {$max} characters",
                            'severity' => 'error',
                            'rule_type' => 'template'
                        ];
                    }
                }
                
                if (str_starts_with($rule, 'in:')) {
                    list(, $values) = explode(':', $rule);
                    $allowed = explode(',', $values);
                    
                    if (!in_array($value, $allowed)) {
                        $errors[] = [
                            'parameter' => $paramPath,
                            'error' => "Template rule violation: {$paramKey} must be one of: " . implode(', ', $allowed),
                            'severity' => 'error',
                            'rule_type' => 'template'
                        ];
                    }
                }
                
                if (str_starts_with($rule, 'regex:')) {
                    $pattern = substr($rule, 6);
                    
                    if (!preg_match($pattern, $value)) {
                        $errors[] = [
                            'parameter' => $paramPath,
                            'error' => "Template rule violation: {$paramKey} does not match required pattern",
                            'severity' => 'error',
                            'rule_type' => 'template'
                        ];
                    }
                }
            }
        }
        
        return $errors;
    }

    private function findParameterPathByKey(array $parameters, string $key): ?string
    {
        foreach ($parameters as $path => $value) {
            if (str_contains($path, $key)) {
                return $path;
            }
        }
        return null;
    }
}
