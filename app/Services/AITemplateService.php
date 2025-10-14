<?php

namespace App\Services;

use App\Models\ConfigurationProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * AITemplateService - Servizio per integrazione OpenAI API
 * Gestisce generazione automatica, validazione e ottimizzazione di configuration templates
 */
class AITemplateService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        $this->model = config('services.openai.model', 'gpt-4o-mini');
        $this->baseUrl = 'https://api.openai.com/v1';
    }

    /**
     * Genera template di configurazione usando AI
     * Generate configuration template using AI
     * 
     * @param array $requirements Requisiti: device_type, manufacturer, model, services (wifi, voip, etc)
     * @return array [success, template_data, confidence_score, suggestions]
     */
    public function generateTemplate(array $requirements): array
    {
        $prompt = $this->buildGenerationPrompt($requirements);
        
        try {
            $response = $this->callOpenAI($prompt, [
                'temperature' => 0.3,
                'max_tokens' => 2000
            ]);
            
            $result = $this->parseGenerationResponse($response);
            
            Log::info('AI Template generated', [
                'device_type' => $requirements['device_type'] ?? 'unknown',
                'confidence' => $result['confidence_score']
            ]);
            
            return [
                'success' => true,
                'template_data' => $result['template'],
                'confidence_score' => $result['confidence_score'],
                'suggestions' => $result['suggestions'],
                'model_used' => $this->model,
                'prompt' => $prompt
            ];
            
        } catch (\Exception $e) {
            Log::error('AI Template generation failed', [
                'error' => $e->getMessage(),
                'requirements' => $requirements
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'template_data' => null,
                'confidence_score' => 0,
                'suggestions' => []
            ];
        }
    }

    /**
     * Valida configurazione esistente usando AI
     * Validate existing configuration using AI
     * 
     * @param ConfigurationProfile $profile
     * @return array [is_valid, issues, recommendations]
     */
    public function validateConfiguration(ConfigurationProfile $profile): array
    {
        $prompt = $this->buildValidationPrompt($profile);
        
        try {
            $response = $this->callOpenAI($prompt, [
                'temperature' => 0.2,
                'max_tokens' => 1500
            ]);
            
            $result = $this->parseValidationResponse($response);
            
            Log::info('AI Configuration validated', [
                'profile_id' => $profile->id,
                'is_valid' => $result['is_valid'],
                'issues_count' => count($result['issues'])
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('AI Configuration validation failed', [
                'error' => $e->getMessage(),
                'profile_id' => $profile->id
            ]);
            
            return [
                'is_valid' => null,
                'issues' => [],
                'recommendations' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Suggerisce ottimizzazioni per configurazione
     * Suggest optimizations for configuration
     * 
     * @param ConfigurationProfile $profile
     * @param string $focus Area di focus: 'performance', 'security', 'stability', 'all'
     * @return array [suggestions]
     */
    public function suggestOptimizations(ConfigurationProfile $profile, string $focus = 'all'): array
    {
        $prompt = $this->buildOptimizationPrompt($profile, $focus);
        
        try {
            $response = $this->callOpenAI($prompt, [
                'temperature' => 0.4,
                'max_tokens' => 1500
            ]);
            
            $result = $this->parseOptimizationResponse($response);
            
            Log::info('AI Optimizations suggested', [
                'profile_id' => $profile->id,
                'focus' => $focus,
                'suggestions_count' => count($result['suggestions'])
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('AI Optimization suggestion failed', [
                'error' => $e->getMessage(),
                'profile_id' => $profile->id
            ]);
            
            return [
                'suggestions' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Chiama OpenAI API
     * Call OpenAI API
     */
    private function callOpenAI(string $prompt, array $options = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json'
        ])->timeout(60)->post($this->baseUrl . '/chat/completions', [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert in TR-069, TR-369 (USP), and carrier-grade CPE device configuration. You provide accurate, production-ready configurations following industry best practices and telecommunications standards.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $options['temperature'] ?? 0.3,
            'max_tokens' => $options['max_tokens'] ?? 1500,
            'response_format' => ['type' => 'json_object']
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API error: ' . $response->body());
        }

        $data = $response->json();
        return json_decode($data['choices'][0]['message']['content'], true);
    }

    /**
     * Costruisce prompt per generazione template
     */
    private function buildGenerationPrompt(array $requirements): string
    {
        $deviceType = $requirements['device_type'] ?? 'generic';
        $manufacturer = $requirements['manufacturer'] ?? 'unknown';
        $model = $requirements['model'] ?? 'unknown';
        $services = $requirements['services'] ?? [];
        
        return <<<PROMPT
Generate a TR-069/TR-369 configuration template for:
- Device Type: {$deviceType}
- Manufacturer: {$manufacturer}
- Model: {$model}
- Required Services: {$this->formatServices($services)}

Requirements:
1. Generate complete TR-181 data model parameters
2. Include WiFi settings (2.4GHz/5GHz) if applicable
3. Include VoIP settings if requested
4. Include WAN settings (PPPoE, DHCP, Static)
5. Follow carrier-grade best practices
6. Ensure parameter names match TR-181 standard

Return JSON with:
{
  "template": {
    "parameters": {},
    "wifi_settings": {},
    "wan_settings": {},
    "voip_settings": {}
  },
  "confidence_score": 0-100,
  "suggestions": ["suggestion1", "suggestion2"]
}
PROMPT;
    }

    /**
     * Costruisce prompt per validazione
     */
    private function buildValidationPrompt(ConfigurationProfile $profile): string
    {
        $params = json_encode($profile->parameters, JSON_PRETTY_PRINT);
        $wifi = json_encode($profile->wifi_settings, JSON_PRETTY_PRINT);
        $wan = json_encode($profile->wan_settings, JSON_PRETTY_PRINT);
        $voip = json_encode($profile->voip_settings, JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Validate this TR-069/TR-369 configuration profile:

Device Type: {$profile->device_type}
Manufacturer: {$profile->manufacturer}
Model: {$profile->model}

Parameters:
{$params}

WiFi Settings:
{$wifi}

WAN Settings:
{$wan}

VoIP Settings:
{$voip}

Check for:
1. TR-181 compliance
2. Security issues
3. Performance problems
4. Missing mandatory parameters
5. Conflicting settings

Return JSON with:
{
  "is_valid": true/false,
  "issues": [{"severity": "critical/warning/info", "message": "...", "parameter": "..."}],
  "recommendations": ["rec1", "rec2"]
}
PROMPT;
    }

    /**
     * Costruisce prompt per ottimizzazioni
     */
    private function buildOptimizationPrompt(ConfigurationProfile $profile, string $focus): string
    {
        $params = json_encode($profile->parameters, JSON_PRETTY_PRINT);
        $wifi = json_encode($profile->wifi_settings, JSON_PRETTY_PRINT);
        
        $focusText = match($focus) {
            'performance' => 'Focus on throughput, latency, and resource optimization',
            'security' => 'Focus on encryption, authentication, and access control',
            'stability' => 'Focus on reliability, failover, and error handling',
            default => 'Optimize for performance, security, and stability'
        };
        
        return <<<PROMPT
Suggest optimizations for this TR-069 configuration:

Device Type: {$profile->device_type}
Current Parameters:
{$params}

WiFi Settings:
{$wifi}

{$focusText}

Provide specific, actionable suggestions with:
- Parameter names
- Recommended values
- Rationale

Return JSON with:
{
  "suggestions": [
    {
      "category": "performance/security/stability",
      "parameter": "Device.WiFi.Radio.1.Channel",
      "current_value": "auto",
      "suggested_value": "36",
      "rationale": "...",
      "impact": "high/medium/low"
    }
  ]
}
PROMPT;
    }

    /**
     * Parsing responses
     */
    private function parseGenerationResponse(array $response): array
    {
        return [
            'template' => $response['template'] ?? [],
            'confidence_score' => $response['confidence_score'] ?? 75,
            'suggestions' => $response['suggestions'] ?? []
        ];
    }

    private function parseValidationResponse(array $response): array
    {
        return [
            'is_valid' => $response['is_valid'] ?? true,
            'issues' => $response['issues'] ?? [],
            'recommendations' => $response['recommendations'] ?? []
        ];
    }

    private function parseOptimizationResponse(array $response): array
    {
        return [
            'suggestions' => $response['suggestions'] ?? []
        ];
    }

    private function formatServices(array $services): string
    {
        return empty($services) ? 'None specified' : implode(', ', $services);
    }

    /**
     * Analizza risultati diagnostici e propone soluzioni
     * Analyze diagnostic results and propose solutions
     * 
     * @param \App\Models\DiagnosticTest $diagnosticTest
     * @return array [analysis, issues, solutions, severity]
     */
    public function analyzeDiagnosticResults(\App\Models\DiagnosticTest $diagnosticTest): array
    {
        $prompt = $this->buildDiagnosticAnalysisPrompt($diagnosticTest);
        
        try {
            $response = $this->callOpenAI($prompt, [
                'temperature' => 0.2,
                'max_tokens' => 2000
            ]);
            
            $result = $this->parseDiagnosticAnalysisResponse($response);
            
            Log::info('AI Diagnostic analysis completed', [
                'diagnostic_id' => $diagnosticTest->id,
                'type' => $diagnosticTest->diagnostic_type,
                'severity' => $result['severity'],
                'issues_count' => count($result['issues'])
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('AI Diagnostic analysis failed', [
                'error' => $e->getMessage(),
                'diagnostic_id' => $diagnosticTest->id
            ]);
            
            return [
                'analysis' => 'Analysis unavailable',
                'issues' => [],
                'solutions' => [],
                'severity' => 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analizza serie di test diagnostici per identificare pattern
     * Analyze series of diagnostic tests to identify patterns
     * 
     * @param array $diagnosticTests Array di DiagnosticTest
     * @return array [pattern_detected, root_cause, recommendations]
     */
    public function analyzeDeviceDiagnosticHistory(array $diagnosticTests): array
    {
        $prompt = $this->buildHistoricalAnalysisPrompt($diagnosticTests);
        
        try {
            $response = $this->callOpenAI($prompt, [
                'temperature' => 0.3,
                'max_tokens' => 2000
            ]);
            
            $result = $this->parseHistoricalAnalysisResponse($response);
            
            Log::info('AI Historical diagnostic analysis completed', [
                'tests_count' => count($diagnosticTests),
                'patterns_found' => count($result['patterns'] ?? [])
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('AI Historical analysis failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'patterns' => [],
                'root_cause' => null,
                'recommendations' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Costruisce prompt per analisi diagnostica singola
     */
    private function buildDiagnosticAnalysisPrompt(\App\Models\DiagnosticTest $test): string
    {
        $type = $test->diagnostic_type;
        $status = $test->status;
        $params = json_encode($test->parameters, JSON_PRETTY_PRINT);
        $results = json_encode($test->results, JSON_PRETTY_PRINT);
        $summary = json_encode($test->getResultsSummary(), JSON_PRETTY_PRINT);
        $deviceInfo = $test->cpeDevice ? "{$test->cpeDevice->manufacturer} {$test->cpeDevice->model} ({$test->cpeDevice->protocol_type})" : 'Unknown';
        
        return <<<PROMPT
Analyze this TR-143 diagnostic test result and provide troubleshooting guidance:

Device: {$deviceInfo}
Serial Number: {$test->cpeDevice->serial_number}
Test Type: {$type}
Status: {$status}
Error Message: {$test->error_message}

Parameters:
{$params}

Raw Results:
{$results}

Summary:
{$summary}

As a telecom network expert, analyze this diagnostic result and provide:

1. Overall assessment of the test result
2. Identified issues or anomalies
3. Root cause analysis
4. Specific troubleshooting steps
5. Recommended solutions
6. Severity level (critical/high/medium/low/info)

Consider common CPE issues:
- Network connectivity problems (packet loss, high latency)
- DNS resolution failures
- ISP routing issues
- Device configuration errors
- WiFi interference or signal degradation
- WAN connection instability
- Speed test anomalies (low throughput, asymmetric speeds)

Return JSON with:
{
  "analysis": "Overall assessment text",
  "issues": [
    {
      "category": "connectivity/performance/configuration",
      "description": "Issue description",
      "metric": "affected metric (e.g., packet_loss, latency)",
      "threshold_exceeded": "expected vs actual"
    }
  ],
  "solutions": [
    {
      "priority": "high/medium/low",
      "action": "Specific action to take",
      "technical_detail": "TR-069 parameters or commands to use",
      "expected_result": "What should improve"
    }
  ],
  "severity": "critical/high/medium/low/info",
  "root_cause": "Most likely root cause"
}
PROMPT;
    }

    /**
     * Costruisce prompt per analisi storica
     */
    private function buildHistoricalAnalysisPrompt(array $tests): string
    {
        $testsData = array_map(function($test) {
            return [
                'id' => $test->id,
                'type' => $test->diagnostic_type,
                'status' => $test->status,
                'date' => $test->created_at->format('Y-m-d H:i:s'),
                'summary' => $test->getResultsSummary()
            ];
        }, $tests);
        
        $testsJson = json_encode($testsData, JSON_PRETTY_PRINT);
        $count = count($tests);
        
        return <<<PROMPT
Analyze this historical series of {$count} diagnostic tests for pattern detection:

Test History:
{$testsJson}

As a telecom network expert, identify:

1. Recurring issues or degradation patterns
2. Time-based correlations (e.g., performance degrading over time)
3. Test failure patterns
4. Root cause hypothesis based on patterns
5. Preventive measures

Return JSON with:
{
  "patterns": [
    {
      "type": "degradation/intermittent/recurring",
      "description": "Pattern description",
      "affected_tests": ["test types affected"],
      "frequency": "how often it occurs"
    }
  ],
  "root_cause": "Most likely root cause based on pattern analysis",
  "recommendations": [
    {
      "priority": "high/medium/low",
      "action": "Recommended preventive action",
      "rationale": "Why this recommendation based on patterns"
    }
  ],
  "trend": "improving/stable/degrading",
  "confidence": 0-100
}
PROMPT;
    }

    /**
     * Parse diagnostic analysis response
     */
    private function parseDiagnosticAnalysisResponse(array $response): array
    {
        return [
            'analysis' => $response['analysis'] ?? 'No analysis available',
            'issues' => $response['issues'] ?? [],
            'solutions' => $response['solutions'] ?? [],
            'severity' => $response['severity'] ?? 'info',
            'root_cause' => $response['root_cause'] ?? null
        ];
    }

    /**
     * Parse historical analysis response
     */
    private function parseHistoricalAnalysisResponse(array $response): array
    {
        return [
            'patterns' => $response['patterns'] ?? [],
            'root_cause' => $response['root_cause'] ?? null,
            'recommendations' => $response['recommendations'] ?? [],
            'trend' => $response['trend'] ?? 'stable',
            'confidence' => $response['confidence'] ?? 50
        ];
    }
}
