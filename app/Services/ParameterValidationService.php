<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ParameterValidationService
{
    public function validateParameters(int $dataModelId, array $parameters): array
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
                        'severity' => 'error'
                    ];
                    continue;
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
                    'warning' => 'Parameter is read-only',
                    'severity' => 'warning'
                ];
            }

            $validationResult = $this->validateValue($value, $paramDef);
            
            if (!$validationResult['valid']) {
                $errors[] = [
                    'parameter' => $paramPath,
                    'error' => $validationResult['message'],
                    'severity' => 'error'
                ];
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
                
                if ($min !== null && $value < $min) {
                    return [
                        'valid' => false,
                        'message' => "Value ({$value}) is less than minimum ({$min})"
                    ];
                }
                
                if ($max !== null && $value > $max) {
                    return [
                        'valid' => false,
                        'message' => "Value ({$value}) exceeds maximum ({$max})"
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
                if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'])) {
                    return ['valid' => false, 'message' => 'Value must be boolean'];
                }
                break;
                
            case 'int':
            case 'unsignedInt':
                if (!is_numeric($value) || (int)$value != $value) {
                    return ['valid' => false, 'message' => 'Value must be an integer'];
                }
                if ($type === 'unsignedInt' && $value < 0) {
                    return ['valid' => false, 'message' => 'Value must be unsigned (positive)'];
                }
                break;
                
            case 'string':
                if (!is_string($value) && !is_numeric($value)) {
                    return ['valid' => false, 'message' => 'Value must be a string'];
                }
                break;
                
            case 'dateTime':
                if (!strtotime($value)) {
                    return ['valid' => false, 'message' => 'Value must be a valid datetime'];
                }
                break;
        }

        return ['valid' => true, 'message' => 'Valid type'];
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
                if ($rule === 'required' && (empty($value) && $value !== 0)) {
                    $errors[] = [
                        'parameter' => $paramPath,
                        'error' => "Template rule violation: {$paramKey} is required",
                        'severity' => 'error',
                        'rule_type' => 'template'
                    ];
                }
                
                if ($rule === 'integer' && !is_numeric($value)) {
                    $errors[] = [
                        'parameter' => $paramPath,
                        'error' => "Template rule violation: {$paramKey} must be an integer",
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
