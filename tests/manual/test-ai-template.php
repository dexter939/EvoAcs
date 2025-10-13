<?php

/**
 * Test AI Template Generation
 * 
 * This script tests the AI-powered configuration template generation system.
 * It simulates API requests to verify:
 * 1. Template generation with OpenAI
 * 2. Configuration validation
 * 3. Optimization suggestions
 * 
 * Usage: php tests/manual/test-ai-template.php
 */

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\App;
use App\Services\AITemplateService;

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== AI Template Service Test ===\n\n";

// Test 1: Generate Template
echo "1. Testing AI Template Generation...\n";
try {
    $aiService = new AITemplateService();
    
    $template = $aiService->generateTemplate([
        'device_type' => 'CPE',
        'manufacturer' => 'Huawei',
        'model' => 'HG8245Q2',
        'services' => ['wifi', 'voip']
    ]);
    
    echo "✓ Template generated successfully!\n";
    echo "  - Template parameters: " . count($template['template']) . "\n";
    echo "  - Confidence score: " . $template['confidence_score'] . "%\n";
    echo "  - AI Model: " . $template['model_used'] . "\n";
    echo "  - Suggestions count: " . count($template['suggestions']) . "\n";
    
    if (!empty($template['suggestions'])) {
        echo "  - First suggestion: " . $template['suggestions'][0] . "\n";
    }
    
    echo "\n";
    
} catch (\Exception $e) {
    echo "✗ Template generation failed: " . $e->getMessage() . "\n\n";
}

// Test 2: Validate Configuration
echo "2. Testing AI Configuration Validation...\n";
try {
    $aiService = new AITemplateService();
    
    $testConfig = [
        'InternetGatewayDevice.WiFi.SSID.1.SSID' => 'TestNetwork',
        'InternetGatewayDevice.WiFi.SSID.1.Enable' => '1',
        'InternetGatewayDevice.WiFi.Radio.1.Channel' => '6',
        'InternetGatewayDevice.WiFi.AccessPoint.1.Security.ModeEnabled' => 'WPA2-PSK'
    ];
    
    $validation = $aiService->validateConfiguration($testConfig, 'CPE');
    
    echo "✓ Validation completed!\n";
    echo "  - Is valid: " . ($validation['is_valid'] ? 'Yes' : 'No') . "\n";
    echo "  - Issues found: " . count($validation['issues']) . "\n";
    echo "  - Recommendations: " . count($validation['recommendations']) . "\n";
    
    if (!empty($validation['issues'])) {
        echo "  - First issue: " . $validation['issues'][0]['message'] . "\n";
    }
    
    echo "\n";
    
} catch (\Exception $e) {
    echo "✗ Validation failed: " . $e->getMessage() . "\n\n";
}

// Test 3: Optimization Suggestions
echo "3. Testing AI Optimization Suggestions...\n";
try {
    $aiService = new AITemplateService();
    
    $testConfig = [
        'InternetGatewayDevice.WiFi.SSID.1.SSID' => 'TestNetwork',
        'InternetGatewayDevice.WiFi.Radio.1.TransmitPower' => '100',
        'InternetGatewayDevice.WiFi.Radio.1.Channel' => '1'
    ];
    
    $optimization = $aiService->suggestOptimizations($testConfig, 'performance');
    
    echo "✓ Optimization completed!\n";
    echo "  - Suggestions count: " . count($optimization['suggestions']) . "\n";
    
    if (!empty($optimization['suggestions'])) {
        $firstSug = $optimization['suggestions'][0];
        echo "  - First suggestion:\n";
        echo "    Category: " . $firstSug['category'] . "\n";
        echo "    Parameter: " . ($firstSug['parameter'] ?? 'N/A') . "\n";
        echo "    Impact: " . $firstSug['impact'] . "\n";
    }
    
    echo "\n";
    
} catch (\Exception $e) {
    echo "✗ Optimization failed: " . $e->getMessage() . "\n\n";
}

echo "=== Test Complete ===\n";
echo "\nNote: Actual OpenAI API calls require valid OPENAI_API_KEY environment variable.\n";
echo "If you see errors, verify your API key is set correctly.\n";
