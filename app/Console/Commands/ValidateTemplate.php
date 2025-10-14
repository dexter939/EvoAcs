<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ParameterValidationService;
use Illuminate\Support\Facades\DB;

class ValidateTemplate extends Command
{
    protected $signature = 'template:validate {id : Template ID to validate}';
    protected $description = 'Validate configuration template parameters against data model rules';

    public function handle()
    {
        $templateId = $this->argument('id');
        $validator = new ParameterValidationService();
        
        $template = DB::table('configuration_templates')->where('id', $templateId)->first();
        
        if (!$template) {
            $this->error("❌ Template ID {$templateId} not found");
            return 1;
        }
        
        $this->info("📋 Validating: {$template->name} ({$template->protocol_version})");
        $this->line("Vendor: {$template->vendor} | Model: {$template->model}");
        $this->line("Data Model ID: {$template->data_model_id}");
        $this->newLine();
        
        $result = $validator->validateTemplate($templateId);
        
        $this->info("📊 Validation Results:");
        $this->line("Total parameters checked: {$result['total_checked']}");
        $this->newLine();
        
        if (!empty($result['errors'])) {
            $this->error("❌ ERRORS ({" . count($result['errors']) . "}):");
            foreach ($result['errors'] as $error) {
                $this->error("  • {$error['parameter']}");
                $this->error("    → {$error['error']}");
            }
            $this->newLine();
        }
        
        if (!empty($result['warnings'])) {
            $this->warn("⚠️  WARNINGS ({" . count($result['warnings']) . "}):");
            foreach ($result['warnings'] as $warning) {
                $this->warn("  • {$warning['parameter']}");
                $this->warn("    → {$warning['warning']}");
            }
            $this->newLine();
        }
        
        if ($result['valid'] && empty($result['warnings'])) {
            $this->info("✅ All parameters validated successfully!");
        } elseif ($result['valid']) {
            $this->warn("✅ Template is valid but has warnings");
        } else {
            $this->error("❌ Template validation failed");
            return 1;
        }
        
        if (!empty($result['validated_parameters'])) {
            $this->info("\n📝 Validated Parameters:");
            $count = 0;
            foreach ($result['validated_parameters'] as $path => $param) {
                $count++;
                if ($count <= 5) {
                    $this->line("  ✓ {$path} = {$param['value']} [{$param['type']}, {$param['access']}]");
                }
            }
            if ($count > 5) {
                $this->line("  ... and " . ($count - 5) . " more");
            }
        }
        
        return 0;
    }
}
