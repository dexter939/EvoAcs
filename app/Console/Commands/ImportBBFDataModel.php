<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class ImportBBFDataModel extends Command
{
    protected $signature = 'tr069:import-bbf {model=tr-181-2-19} {--force}';
    protected $description = 'Import Broadband Forum TR-069 Data Models from GitHub repository';

    private $baseUrl = 'https://raw.githubusercontent.com/BroadbandForum/cwmp-data-models/master/';
    private $modelConfig = [
        'tr-181-2-19' => [
            'file' => 'tr-181-2-19-1-cwmp-full.xml',
            'vendor' => 'Broadband Forum',
            'model_name' => 'Device:2.19',
            'protocol' => 'TR-181 Issue 2',
            'spec' => 'TR-181 Issue 2 Amendment 19 Corrigendum 1',
            'description' => 'BBF Device:2.19 Data Model - Standard root data model for all TR-069 CPE devices'
        ],
        'tr-181-2-18' => [
            'file' => 'tr-181-2-18-0-cwmp-full.xml',
            'vendor' => 'Broadband Forum',
            'model_name' => 'Device:2.18',
            'protocol' => 'TR-181 Issue 2',
            'spec' => 'TR-181 Issue 2 Amendment 18',
            'description' => 'BBF Device:2.18 Data Model - Standard root data model for TR-069 CPE devices'
        ],
        'tr-098' => [
            'file' => 'tr-098-1-8-0-full.xml',
            'vendor' => 'Broadband Forum',
            'model_name' => 'InternetGatewayDevice:1.8',
            'protocol' => 'TR-098',
            'spec' => 'TR-098 Amendment 8',
            'description' => 'BBF InternetGatewayDevice:1.8 Data Model - Legacy root data model for backward compatibility'
        ],
        'tr-104' => [
            'file' => 'tr-104-2-0-0.xml',
            'vendor' => 'Broadband Forum',
            'model_name' => 'VoiceService:2.0',
            'protocol' => 'TR-104',
            'spec' => 'TR-104 Issue 2',
            'description' => 'BBF VoiceService Data Model - VoIP service provisioning parameters'
        ],
        'tr-143' => [
            'file' => 'tr-143-1-1-0.xml',
            'vendor' => 'Broadband Forum',
            'model_name' => 'DownloadDiagnostics',
            'protocol' => 'TR-143',
            'spec' => 'TR-143 Amendment 1',
            'description' => 'BBF Network Throughput Performance Test Data Model'
        ],
    ];

    public function handle()
    {
        $modelKey = $this->argument('model');
        
        if (!isset($this->modelConfig[$modelKey])) {
            $this->error("Unknown model: {$modelKey}");
            $this->info("Available models: " . implode(', ', array_keys($this->modelConfig)));
            return 1;
        }

        $config = $this->modelConfig[$modelKey];
        $xmlUrl = $this->baseUrl . $config['file'];

        $this->info("🚀 Starting BBF Data Model import: {$config['spec']}");
        $this->info("📥 Downloading XML from: {$config['file']}");

        // Download XML
        $response = Http::timeout(60)->get($xmlUrl);
        
        if (!$response->successful()) {
            $this->error("Failed to download XML file");
            return 1;
        }

        $xmlContent = $response->body();
        $this->info("✅ XML downloaded successfully (" . strlen($xmlContent) . " bytes)");

        // Parse XML
        try {
            libxml_use_internal_errors(true);
            $xml = new SimpleXMLElement($xmlContent);
            libxml_clear_errors();
        } catch (\Exception $e) {
            $this->error("Failed to parse XML: " . $e->getMessage());
            return 1;
        }

        // Create Data Model record
        $this->info("💾 Creating Data Model record...");
        
        // Check if exists
        $existing = DB::table('tr069_data_models')
            ->where('vendor', $config['vendor'])
            ->where('model_name', $config['model_name'])
            ->first();

        if ($existing && !$this->option('force')) {
            $this->warn("Data Model already exists (ID: {$existing->id})");
            $this->info("Use --force to re-import");
            return 0;
        }

        if ($existing) {
            // Delete existing parameters
            DB::table('tr069_parameters')->where('data_model_id', $existing->id)->delete();
            DB::table('tr069_data_models')->where('id', $existing->id)->delete();
            $this->info("🗑️  Deleted existing data model and parameters");
        }

        $dataModel = DB::table('tr069_data_models')->insertGetId([
            'vendor' => $config['vendor'],
            'model_name' => $config['model_name'],
            'firmware_version' => null,
            'protocol_version' => $config['protocol'],
            'spec_name' => $config['spec'],
            'description' => $config['description'],
            'metadata' => json_encode([
                'source_url' => 'https://github.com/BroadbandForum/cwmp-data-models',
                'xml_file' => $config['file'],
                'imported_at' => now()->toIso8601String(),
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("✅ Data Model created with ID: {$dataModel}");

        // Parse parameters from XML
        $this->info("🔍 Parsing parameters from XML...");
        $parameterCount = $this->parseXMLParameters($xml, $dataModel);

        $this->info("📊 Import completed successfully!");
        $this->info("  - Data Model ID: {$dataModel}");
        $this->info("  - Total Parameters: {$parameterCount}");

        return 0;
    }

    private function parseXMLParameters($xml, $dataModelId)
    {
        $parameters = [];
        $batch = [];
        $batchSize = 100;

        // Register namespaces
        $namespaces = $xml->getNamespaces(true);
        
        // Parse model objects and parameters
        $this->parseModel($xml, '', $dataModelId, $batch, $parameters);

        // Insert remaining batch
        if (!empty($batch)) {
            DB::table('tr069_parameters')->insert($batch);
        }

        return count($parameters);
    }

    private function parseModel($node, $prefix, $dataModelId, &$batch, &$parameters, $batchSize = 100)
    {
        // Get model elements
        foreach ($node->xpath('.//model') as $model) {
            $modelName = (string)$model['name'];
            $this->parseObject($model, $modelName . '.', $dataModelId, $batch, $parameters, $batchSize);
        }
    }

    private function parseObject($node, $prefix, $dataModelId, &$batch, &$parameters, $batchSize = 100)
    {
        // Parse objects
        foreach ($node->xpath('.//object') as $object) {
            $objectName = (string)$object['name'];
            $fullPath = $prefix . $objectName;
            $access = (string)$object['access'];
            $minEntries = (string)$object['minEntries'];
            $maxEntries = (string)$object['maxEntries'];
            
            $description = '';
            if (isset($object->description)) {
                $description = trim((string)$object->description);
            }

            $isMultiInstance = ($minEntries !== '' && $maxEntries !== '' && $maxEntries !== '1');

            $param = [
                'data_model_id' => $dataModelId,
                'parameter_path' => $fullPath,
                'parameter_name' => rtrim($objectName, '.'),
                'parameter_type' => $isMultiInstance ? 'object[]' : 'object',
                'access_type' => $access ?: 'R',
                'is_object' => true,
                'description' => substr($description, 0, 500),
                'default_value' => null,
                'min_version' => null,
                'validation_rules' => json_encode([
                    'minEntries' => $minEntries ?: null,
                    'maxEntries' => $maxEntries ?: null,
                ]),
                'metadata' => json_encode([
                    'full_description' => $description,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $batch[] = $param;
            $parameters[] = $fullPath;

            if (count($batch) >= $batchSize) {
                DB::table('tr069_parameters')->insert($batch);
                $batch = [];
                $this->info("  ✓ Imported " . count($parameters) . " parameters...");
            }

            // Parse child parameters and objects
            $this->parseParameters($object, $fullPath, $dataModelId, $batch, $parameters, $batchSize);
            $this->parseObject($object, $fullPath, $dataModelId, $batch, $parameters, $batchSize);
        }
    }

    private function parseParameters($node, $prefix, $dataModelId, &$batch, &$parameters, $batchSize = 100)
    {
        foreach ($node->xpath('.//parameter') as $parameter) {
            $paramName = (string)$parameter['name'];
            $fullPath = $prefix . $paramName;
            $access = (string)$parameter['access'];
            
            $description = '';
            if (isset($parameter->description)) {
                $description = trim((string)$parameter->description);
            }

            $syntax = $parameter->xpath('./syntax');
            $paramType = 'string';
            $defaultValue = null;

            if (!empty($syntax)) {
                $syntaxNode = $syntax[0];
                foreach ($syntaxNode->children() as $child) {
                    $paramType = $child->getName();
                    
                    if (isset($child['default'])) {
                        $defaultValue = (string)$child['default'];
                    }
                    break;
                }
            }

            $param = [
                'data_model_id' => $dataModelId,
                'parameter_path' => $fullPath,
                'parameter_name' => $paramName,
                'parameter_type' => $paramType,
                'access_type' => $access ?: 'R',
                'is_object' => false,
                'description' => substr($description, 0, 500),
                'default_value' => $defaultValue,
                'min_version' => null,
                'validation_rules' => null,
                'metadata' => json_encode([
                    'full_description' => $description,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $batch[] = $param;
            $parameters[] = $fullPath;

            if (count($batch) >= $batchSize) {
                DB::table('tr069_parameters')->insert($batch);
                $batch = [];
                $this->info("  ✓ Imported " . count($parameters) . " parameters...");
            }
        }
    }
}
