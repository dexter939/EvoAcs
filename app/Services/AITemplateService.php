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
}
