<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportMikroTikDataModel extends Command
{
    protected $signature = 'tr069:import-mikrotik';
    protected $description = 'Import MikroTik TR-069 Data Model from official documentation';

    public function handle()
    {
        $this->info('🚀 Starting MikroTik Data Model import...');

        // Download HTML documentation
        $this->info('📥 Downloading MikroTik TR-069 documentation...');
        $response = Http::timeout(30)->get('https://help.mikrotik.com/docs/download/attachments/9863195/current.html?api=v2');
        
        if (!$response->successful()) {
            $this->error('Failed to download documentation');
            return 1;
        }

        $html = $response->body();
        $this->info('✅ Documentation downloaded successfully');

        // Create Data Model record
        $this->info('💾 Creating MikroTik Data Model record...');
        $dataModel = DB::table('tr069_data_models')->insertGetId([
            'vendor' => 'MikroTik',
            'model_name' => 'RouterOS',
            'firmware_version' => '6.39+',
            'protocol_version' => 'TR-181',
            'spec_name' => 'Device:2',
            'description' => 'MikroTik RouterOS TR-069 Data Model (Device:2) - Complete parameter set from official documentation',
            'metadata' => json_encode([
                'source_url' => 'https://help.mikrotik.com/docs/spaces/ROS/pages/9863195/TR-069',
                'doc_url' => 'https://help.mikrotik.com/docs/download/attachments/9863195/current.html?api=v2',
                'imported_at' => now()->toIso8601String(),
                'supported_features' => [
                    'Device.DeviceInfo',
                    'Device.ManagementServer',
                    'Device.Hosts',
                    'Device.WiFi',
                    'Device.IP',
                    'Device.Routing',
                    'Device.DNS',
                    'Device.DHCPv4',
                    'Device.Firewall',
                    'Device.X_MIKROTIK_*'
                ]
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("✅ Data Model created with ID: {$dataModel}");

        // Parse parameters from HTML
        $this->info('🔍 Parsing parameters from documentation...');
        $parameters = $this->parseParameters($html, $dataModel);

        $this->info("📊 Found {$parameters} parameters");

        // Insert parameters in batches
        $this->info('💾 Importing parameters into database...');
        
        $this->info('✅ MikroTik Data Model import completed successfully!');
        
        // Statistics
        $paramCount = DB::table('tr069_parameters')
            ->where('data_model_id', $dataModel)
            ->count();
        
        $this->info("📈 Statistics:");
        $this->info("  - Data Model ID: {$dataModel}");
        $this->info("  - Total Parameters: {$paramCount}");
        
        // Count by category
        $categories = [
            'DeviceInfo' => 'Device.DeviceInfo.%',
            'ManagementServer' => 'Device.ManagementServer.%',
            'Hosts' => 'Device.Hosts.%',
            'WiFi' => 'Device.WiFi.%',
            'IP' => 'Device.IP.%',
            'Routing' => 'Device.Routing.%',
            'DNS' => 'Device.DNS.%',
            'DHCPv4' => 'Device.DHCPv4.%',
            'Firewall' => 'Device.Firewall.%',
            'MIKROTIK' => 'Device.X_MIKROTIK_%',
        ];

        foreach ($categories as $name => $pattern) {
            $count = DB::table('tr069_parameters')
                ->where('data_model_id', $dataModel)
                ->where('parameter_path', 'LIKE', $pattern)
                ->count();
            if ($count > 0) {
                $this->info("  - {$name}: {$count} parameters");
            }
        }

        return 0;
    }

    private function parseParameters($html, $dataModelId)
    {
        $parameterCount = 0;
        $batch = [];
        $batchSize = 100;

        // Parse HTML using regex to extract parameter definitions
        // Pattern: <a name='Device.XXX.YYY'>ParameterName</a>
        preg_match_all(
            "/<div class='float-pad-box table-node-name'><a name='([^']+)'>([^<]*)<\/a>/",
            $html,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $fullPath = $match[1];
            $shortName = $match[2];

            // Skip if it's just a parent path ending with dot
            if (empty($shortName) && substr($fullPath, -1) === '.') {
                continue;
            }

            // Extract parameter info from the next divs
            $paramInfo = $this->extractParameterInfo($html, $fullPath);

            $isObject = (substr($fullPath, -1) === '.' || strpos($fullPath, '{i}') !== false);
            
            $batch[] = [
                'data_model_id' => $dataModelId,
                'parameter_path' => $fullPath,
                'parameter_name' => $shortName ?: $this->extractLastSegment($fullPath),
                'parameter_type' => $paramInfo['type'] ?? 'unknown',
                'access_type' => $paramInfo['access'] ?? 'R',
                'is_object' => $isObject,
                'description' => $paramInfo['description'] ?? '',
                'default_value' => $paramInfo['default'] ?? null,
                'min_version' => $paramInfo['version'] ?? '6.39',
                'validation_rules' => json_encode($paramInfo['validation'] ?? null),
                'metadata' => json_encode([
                    'ros_mapping' => $paramInfo['ros_mapping'] ?? null,
                    'flags' => $paramInfo['flags'] ?? null,
                    'full_description' => $paramInfo['full_description'] ?? null,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $parameterCount++;

            // Insert in batches
            if (count($batch) >= $batchSize) {
                DB::table('tr069_parameters')->insert($batch);
                $batch = [];
                $this->info("  ✓ Imported {$parameterCount} parameters...");
            }
        }

        // Insert remaining batch
        if (!empty($batch)) {
            DB::table('tr069_parameters')->insert($batch);
        }

        return $parameterCount;
    }

    private function extractParameterInfo($html, $paramPath)
    {
        $info = [];

        // Find the section for this parameter
        $pattern = "/<a name='" . preg_quote($paramPath, '/') . "'>.*?(?=<div class='float-pad-box table-node-name'>|$)/s";
        
        if (preg_match($pattern, $html, $section)) {
            $content = $section[0];

            // Extract type
            if (preg_match("/type:\s*<\/span><span>([^<]+)/", $content, $typeMatch)) {
                $info['type'] = trim($typeMatch[1]);
            }

            // Extract access (W means writable)
            if (preg_match("/<div class='float-pad-box table-node-read'>([^<]+)/", $content, $accessMatch)) {
                $access = trim($accessMatch[1]);
                $info['access'] = ($access === 'W') ? 'RW' : (($access === '-') ? 'R' : $access);
            }

            // Extract version
            if (preg_match("/<div class='float-pad-box table-node-vers'>([^<]+)/", $content, $versionMatch)) {
                $ver = trim($versionMatch[1]);
                if (!empty($ver)) {
                    $info['version'] = $ver;
                }
            }

            // Extract description
            if (preg_match("/<div class='float-pad-box table-node-descr'>([^<]+)/", $content, $descMatch)) {
                $desc = trim(strip_tags($descMatch[1]));
                $info['description'] = substr($desc, 0, 500); // Limit to 500 chars
                $info['full_description'] = $desc;
            }

            // Extract ROS mapping
            if (preg_match("/ROS:\s*([^\n<]+)/", $content, $rosMatch)) {
                $info['ros_mapping'] = trim(strip_tags($rosMatch[1]));
            }

            // Extract flags
            if (preg_match("/flags:\s*([^\n<]+)/", $content, $flagsMatch)) {
                $info['flags'] = trim(strip_tags($flagsMatch[1]));
            }
        }

        return $info;
    }

    private function extractLastSegment($path)
    {
        $parts = explode('.', rtrim($path, '.'));
        return end($parts);
    }
}
