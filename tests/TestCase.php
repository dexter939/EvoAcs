<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withoutVite();
    }

    /**
     * Get authenticated API headers with API key
     */
    protected function apiHeaders(array $additional = []): array
    {
        return array_merge([
            'X-API-Key' => env('ACS_API_KEY', 'test-api-key-for-phpunit-testing'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $additional);
    }

    /**
     * Make authenticated API request
     */
    protected function apiGet(string $uri, array $headers = [])
    {
        return $this->get($uri, $this->apiHeaders($headers));
    }

    /**
     * Make authenticated API POST request
     */
    protected function apiPost(string $uri, array $data = [], array $headers = [])
    {
        return $this->post($uri, $data, $this->apiHeaders($headers));
    }

    /**
     * Make authenticated API PUT request
     */
    protected function apiPut(string $uri, array $data = [], array $headers = [])
    {
        return $this->put($uri, $data, $this->apiHeaders($headers));
    }

    /**
     * Make authenticated API DELETE request
     */
    protected function apiDelete(string $uri, array $headers = [])
    {
        return $this->delete($uri, $this->apiHeaders($headers));
    }

    /**
     * Create TR-069 SOAP envelope for testing
     */
    protected function createTr069SoapEnvelope(string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
               '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" ' .
               'xmlns:cwmp="urn:dslforum-org:cwmp-1-0">' . "\n" .
               '<soap:Header>' . "\n" .
               '<cwmp:ID soap:mustUnderstand="1">test-id-' . uniqid() . '</cwmp:ID>' . "\n" .
               '</soap:Header>' . "\n" .
               '<soap:Body>' . "\n" .
               $body . "\n" .
               '</soap:Body>' . "\n" .
               '</soap:Envelope>';
    }

    /**
     * Create TR-069 Inform message
     */
    protected function createTr069Inform(array $params = []): string
    {
        $defaults = [
            'serial' => 'TEST-' . uniqid(),
            'oui' => '00259E',
            'product_class' => 'IGD',
            'event_code' => '0 BOOTSTRAP',
        ];
        
        $params = array_merge($defaults, $params);
        
        $body = '<cwmp:Inform>' . "\n" .
                '<DeviceId>' . "\n" .
                '<Manufacturer>TestManufacturer</Manufacturer>' . "\n" .
                '<OUI>' . $params['oui'] . '</OUI>' . "\n" .
                '<ProductClass>' . $params['product_class'] . '</ProductClass>' . "\n" .
                '<SerialNumber>' . $params['serial'] . '</SerialNumber>' . "\n" .
                '</DeviceId>' . "\n" .
                '<Event><EventStruct><EventCode>' . $params['event_code'] . '</EventCode><CommandKey></CommandKey></EventStruct></Event>' . "\n" .
                '<MaxEnvelopes>1</MaxEnvelopes>' . "\n" .
                '<CurrentTime>2025-01-01T00:00:00Z</CurrentTime>' . "\n" .
                '<RetryCount>0</RetryCount>' . "\n" .
                '<ParameterList></ParameterList>' . "\n" .
                '</cwmp:Inform>';
        
        return $this->createTr069SoapEnvelope($body);
    }

    /**
     * Create USP Get request protobuf for testing
     */
    protected function createUspGetRequest(array $paths): array
    {
        return [
            'header' => [
                'msg_id' => 'test-msg-' . uniqid(),
                'msg_type' => 1, // GET
            ],
            'body' => [
                'request' => [
                    'get' => [
                        'param_paths' => $paths,
                    ],
                ],
            ],
        ];
    }

    /**
     * Assert JSON response has correct structure
     */
    protected function assertJsonStructure(array $structure, $response = null)
    {
        if ($response === null) {
            $response = $this->response;
        }
        
        return $response->assertJsonStructure($structure);
    }

    /**
     * Assert response is successful API response
     */
    protected function assertSuccessResponse($response = null)
    {
        if ($response === null) {
            $response = $this->response;
        }
        
        return $response->assertStatus(200)
                        ->assertJsonStructure(['success', 'data']);
    }
}
