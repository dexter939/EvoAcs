<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTR069DataModel extends Command
{
    protected $signature = 'tr069:import-datamodel {xml_file} {--vendor=} {--model=} {--firmware=}';
    protected $description = 'Import TR-069 data model from XML file into database';

    public function handle()
    {
        $xmlFile = $this->argument('xml_file');
        
        if (!file_exists($xmlFile)) {
            $this->error("File not found: {$xmlFile}");
            return 1;
        }

        $this->info("Importing TR-069 data model from: {$xmlFile}");

        // Parse XML
        $xml = simplexml_load_file($xmlFile);
        if (!$xml) {
            $this->error("Failed to parse XML file");
            return 1;
        }

        // Register namespaces
        $namespaces = $xml->getNamespaces(true);
        foreach ($namespaces as $prefix => $uri) {
            $xml->registerXPathNamespace($prefix ?: 'default', $uri);
        }

        // Extract spec name
        $spec = (string)$xml['spec'];
        $this->info("Spec: {$spec}");

        // Determine protocol version from spec
        $protocolVersion = $this->extractProtocolVersion($spec);
        
        // Create or update data model
        $dataModel = DB::table('tr069_data_models')->updateOrInsert(
            ['spec_name' => $spec],
            [
                'vendor' => $this->option('vendor') ?? 'Unknown',
                'model_name' => $this->option('model') ?? 'Unknown',
                'firmware_version' => $this->option('firmware'),
                'protocol_version' => $protocolVersion,
                'spec_name' => $spec,
                'description' => "Imported from {$xmlFile}",
                'metadata' => json_encode(['source' => $xmlFile]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $dataModelId = DB::table('tr069_data_models')->where('spec_name', $spec)->value('id');
        $this->info("Data Model ID: {$dataModelId}");

        // Parse parameters from model section
        $paramCount = 0;
        
        // Find model first
        $models = $xml->xpath('//model') ?: [];
        if (empty($models)) {
            $this->error("No model found in XML");
            return 1;
        }
        
        $model = $models[0];
        $this->info("Model: " . $model['name']);
        
        // Find all objects in model
        $objects = $model->xpath('.//object') ?: [];
        $this->info("Found " . count($objects) . " objects");

        foreach ($objects as $object) {
            $paramPath = (string)$object['name'];
            $access = (string)$object['access'] ?: 'readOnly';
            $minVersion = (string)$object['minVersion'];
            
            $description = '';
            if (isset($object->description)) {
                $description = trim((string)$object->description);
            }

            // Insert object
            DB::table('tr069_parameters')->updateOrInsert(
                [
                    'data_model_id' => $dataModelId,
                    'parameter_path' => $paramPath
                ],
                [
                    'parameter_name' => basename($paramPath, '.'),
                    'parameter_type' => 'object',
                    'access_type' => $this->mapAccessType($access),
                    'is_object' => true,
                    'description' => $description,
                    'min_version' => $minVersion,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $paramCount++;

            // Parse child parameters
            $parameters = $object->xpath('.//dm:parameter') ?: [];
            foreach ($parameters as $param) {
                $paramName = (string)$param['name'];
                $fullPath = $paramPath . $paramName;
                $paramType = (string)$param->syntax->dataType ?? (string)$param->syntax ?? 'string';
                $paramAccess = (string)$param['access'] ?: 'readOnly';
                $paramMinVersion = (string)$param['minVersion'];
                $paramDefault = (string)$param->syntax->default ?? null;
                
                $paramDescription = '';
                if (isset($param->description)) {
                    $paramDescription = trim((string)$param->description);
                }

                // Extract validation rules
                $validationRules = [];
                if (isset($param->syntax->size)) {
                    $validationRules['size'] = [
                        'min' => (string)$param->syntax->size['minLength'] ?? null,
                        'max' => (string)$param->syntax->size['maxLength'] ?? null,
                    ];
                }
                if (isset($param->syntax->pattern)) {
                    $validationRules['pattern'] = (string)$param->syntax->pattern['value'];
                }

                DB::table('tr069_parameters')->updateOrInsert(
                    [
                        'data_model_id' => $dataModelId,
                        'parameter_path' => $fullPath
                    ],
                    [
                        'parameter_name' => $paramName,
                        'parameter_type' => $paramType,
                        'access_type' => $this->mapAccessType($paramAccess),
                        'is_object' => false,
                        'description' => $paramDescription,
                        'default_value' => $paramDefault,
                        'min_version' => $paramMinVersion,
                        'validation_rules' => !empty($validationRules) ? json_encode($validationRules) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $paramCount++;
            }

            if ($paramCount % 100 == 0) {
                $this->info("Imported {$paramCount} parameters...");
            }
        }

        $this->info("✅ Successfully imported {$paramCount} parameters!");
        return 0;
    }

    private function extractProtocolVersion($spec)
    {
        if (str_contains($spec, 'TR098') || str_contains($spec, 'tr-098')) {
            return 'TR-098';
        } elseif (str_contains($spec, 'TR104') || str_contains($spec, 'tr-104')) {
            return 'TR-104';
        } elseif (str_contains($spec, 'TR140') || str_contains($spec, 'tr-140')) {
            return 'TR-140';
        } elseif (str_contains($spec, 'TR181') || str_contains($spec, 'tr-181')) {
            return 'TR-181';
        }
        return 'Unknown';
    }

    private function mapAccessType($access)
    {
        if (in_array($access, ['readWrite', 'RW'])) {
            return 'RW';
        } elseif (in_array($access, ['writeOnly', 'W'])) {
            return 'W';
        }
        return 'R';
    }
}
