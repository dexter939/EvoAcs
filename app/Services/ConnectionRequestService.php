<?php

namespace App\Services;

use App\Models\CpeDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ConnectionRequestService
 * 
 * Servizio per gestire Connection Request TR-069 verso dispositivi CPE
 * Service to handle TR-069 Connection Requests to CPE devices
 * 
 * Standard TR-069 Connection Request:
 * - L'ACS invia una richiesta HTTP GET/POST alla ConnectionRequestURL del CPE
 * - Il CPE risponde con HTTP 200 OK e inizia un nuovo Inform verso l'ACS
 * - L'autenticazione può essere HTTP Basic Auth o Digest Auth
 * 
 * - ACS sends an HTTP GET/POST request to the CPE's ConnectionRequestURL
 * - CPE responds with HTTP 200 OK and starts a new Inform to the ACS
 * - Authentication can be HTTP Basic Auth or Digest Auth
 */
class ConnectionRequestService
{
    /**
     * Timeout per richiesta HTTP in secondi
     * HTTP request timeout in seconds
     */
    private const REQUEST_TIMEOUT = 10;

    /**
     * Invia Connection Request a un dispositivo CPE
     * Send Connection Request to a CPE device
     * 
     * @param CpeDevice $device Dispositivo target / Target device
     * @return array Risultato con success (bool) e message (string)
     */
    public function sendConnectionRequest(CpeDevice $device): array
    {
        // Verifica ConnectionRequestURL disponibile
        // Verify ConnectionRequestURL is available
        if (empty($device->connection_request_url)) {
            Log::warning("Connection Request failed: missing URL for device {$device->serial_number}");
            return [
                'success' => false,
                'message' => 'ConnectionRequestURL not available for this device',
                'error_code' => 'MISSING_URL'
            ];
        }

        $url = $device->connection_request_url;
        $username = $device->connection_request_username;
        $password = $device->connection_request_password;

        Log::info("Sending Connection Request to device {$device->serial_number}", [
            'url' => $url,
            'has_auth' => !empty($username)
        ]);

        try {
            // Usa metodo di autenticazione specificato o prova digest se non specificato
            // Use specified auth method or try digest if not specified
            if (!empty($username) && !empty($password)) {
                $authMethod = $device->auth_method ?? 'digest';
                
                $response = $this->sendRequestWithAuth($url, $username, $password, $authMethod);
                
                if ($response->successful()) {
                    Log::info("Connection Request successful ({$authMethod} Auth) for device {$device->serial_number}", [
                        'status' => $response->status()
                    ]);

                    return [
                        'success' => true,
                        'message' => "Connection Request sent successfully (" . ucfirst($authMethod) . " Auth)",
                        'http_status' => $response->status(),
                        'auth_method' => $authMethod
                    ];
                }

                // Se metodo configurato fallisce con 401, prova l'altro metodo come fallback
                // If configured method fails with 401, try the other method as fallback
                if ($response->status() === 401) {
                    $fallbackMethod = $authMethod === 'digest' ? 'basic' : 'digest';
                    Log::info("Retrying with {$fallbackMethod} Auth for device {$device->serial_number}");
                    
                    $response = $this->sendRequestWithAuth($url, $username, $password, $fallbackMethod);
                    
                    if ($response->successful()) {
                        Log::info("Connection Request successful ({$fallbackMethod} Auth) for device {$device->serial_number}", [
                            'status' => $response->status()
                        ]);

                        return [
                            'success' => true,
                            'message' => "Connection Request sent successfully (" . ucfirst($fallbackMethod) . " Auth)",
                            'http_status' => $response->status(),
                            'auth_method' => $fallbackMethod
                        ];
                    }
                }

                // Entrambi i metodi di autenticazione falliti
                // Both authentication methods failed
                Log::warning("Connection Request failed (both auth methods) for device {$device->serial_number}", [
                    'status' => $response->status()
                ]);

                return [
                    'success' => false,
                    'message' => "Authentication failed: {$response->status()}",
                    'error_code' => 'AUTH_FAILED',
                    'http_status' => $response->status()
                ];
            }

            // Nessuna autenticazione configurata - prova senza auth
            // No authentication configured - try without auth
            $response = Http::timeout(self::REQUEST_TIMEOUT)->get($url);

            if ($response->successful()) {
                Log::info("Connection Request successful (no auth) for device {$device->serial_number}", [
                    'status' => $response->status()
                ]);

                return [
                    'success' => true,
                    'message' => 'Connection Request sent successfully',
                    'http_status' => $response->status()
                ];
            } else {
                Log::warning("Connection Request failed for device {$device->serial_number}", [
                    'status' => $response->status()
                ]);

                return [
                    'success' => false,
                    'message' => "HTTP error: {$response->status()}",
                    'error_code' => 'HTTP_ERROR',
                    'http_status' => $response->status()
                ];
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Errore di connessione (timeout, network unreachable, etc.)
            // Connection error (timeout, network unreachable, etc.)
            Log::error("Connection Request connection error for device {$device->serial_number}", [
                'error' => $e->getMessage(),
                'url' => $url
            ]);

            return [
                'success' => false,
                'message' => "Connection error: {$e->getMessage()}",
                'error_code' => 'CONNECTION_ERROR'
            ];

        } catch (\Exception $e) {
            // Errore generico
            // Generic error
            Log::error("Connection Request unexpected error for device {$device->serial_number}", [
                'error' => $e->getMessage(),
                'url' => $url
            ]);

            return [
                'success' => false,
                'message' => "Unexpected error: {$e->getMessage()}",
                'error_code' => 'UNEXPECTED_ERROR'
            ];
        }
    }

    /**
     * Invia richiesta HTTP con autenticazione specifica
     * Send HTTP request with specific authentication
     * 
     * @param string $url
     * @param string $username
     * @param string $password
     * @param string $authMethod 'basic' o 'digest' / 'basic' or 'digest'
     * @return \Illuminate\Http\Client\Response
     */
    private function sendRequestWithAuth(string $url, string $username, string $password, string $authMethod)
    {
        if ($authMethod === 'digest') {
            // HTTP Digest Auth con challenge flow (RFC 2617)
            // 1. Prima richiesta senza auth per ottenere challenge
            // 2. Seconda richiesta con Digest header calcolato
            
            // Step 1: Initial request to get 401 challenge
            $initialResponse = Http::timeout(self::REQUEST_TIMEOUT)->get($url);
            
            // Se non è 401, ritorna la risposta (potrebbe essere 200 senza auth richiesta)
            if ($initialResponse->status() !== 401) {
                return $initialResponse;
            }
            
            // Step 2: Parse WWW-Authenticate header e calcola Digest
            $wwwAuth = $initialResponse->header('WWW-Authenticate');
            
            if (empty($wwwAuth) || !str_contains($wwwAuth, 'Digest')) {
                // No Digest challenge, return original 401
                return $initialResponse;
            }
            
            // Calculate Digest Authorization header
            $digestHeader = $this->calculateDigestAuthHeader($url, $username, $password, $wwwAuth);
            
            // Step 3: Retry with Digest Authorization
            return Http::timeout(self::REQUEST_TIMEOUT)
                ->withHeaders(['Authorization' => $digestHeader])
                ->get($url);
                
        } else {
            // HTTP Basic Auth usa Laravel HTTP client
            // HTTP Basic Auth uses Laravel HTTP client
            return Http::timeout(self::REQUEST_TIMEOUT)
                ->withBasicAuth($username, $password)
                ->get($url);
        }
    }
    
    /**
     * Calcola Digest Authorization header (RFC 2617)
     * Calculate Digest Authorization header (RFC 2617)
     * 
     * @param string $url
     * @param string $username
     * @param string $password
     * @param string $wwwAuthHeader Header WWW-Authenticate dal server
     * @return string Digest Authorization header
     */
    private function calculateDigestAuthHeader(string $url, string $username, string $password, string $wwwAuthHeader): string
    {
        // Parse WWW-Authenticate header
        $realm = $this->parseDigestValue($wwwAuthHeader, 'realm');
        $nonce = $this->parseDigestValue($wwwAuthHeader, 'nonce');
        $qop = $this->parseDigestValue($wwwAuthHeader, 'qop');
        $opaque = $this->parseDigestValue($wwwAuthHeader, 'opaque');
        
        // Parse URL per ottenere URI path
        $parsedUrl = parse_url($url);
        $uri = $parsedUrl['path'] ?? '/';
        if (!empty($parsedUrl['query'])) {
            $uri .= '?' . $parsedUrl['query'];
        }
        
        // Generate client nonce and nc (nonce count)
        $cnonce = md5(uniqid());
        $nc = '00000001';
        
        // Calculate HA1 = MD5(username:realm:password)
        $ha1 = md5("{$username}:{$realm}:{$password}");
        
        // Calculate HA2 = MD5(method:uri)
        $ha2 = md5("GET:{$uri}");
        
        // Calculate response hash
        if ($qop === 'auth' || $qop === 'auth-int') {
            // With qop: MD5(HA1:nonce:nc:cnonce:qop:HA2)
            $response = md5("{$ha1}:{$nonce}:{$nc}:{$cnonce}:{$qop}:{$ha2}");
        } else {
            // Without qop: MD5(HA1:nonce:HA2)
            $response = md5("{$ha1}:{$nonce}:{$ha2}");
        }
        
        // Build Digest Authorization header
        $authHeader = 'Digest ' .
            "username=\"{$username}\", " .
            "realm=\"{$realm}\", " .
            "nonce=\"{$nonce}\", " .
            "uri=\"{$uri}\", " .
            "response=\"{$response}\"";
        
        if ($qop) {
            $authHeader .= ", qop={$qop}, nc={$nc}, cnonce=\"{$cnonce}\"";
        }
        
        if ($opaque) {
            $authHeader .= ", opaque=\"{$opaque}\"";
        }
        
        return $authHeader;
    }
    
    /**
     * Parse un valore dal header WWW-Authenticate
     * Parse a value from WWW-Authenticate header
     * 
     * @param string $header
     * @param string $key
     * @return string|null
     */
    private function parseDigestValue(string $header, string $key): ?string
    {
        // Match key="value" or key=value
        if (preg_match('/' . $key . '="?([^",]+)"?/', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Verifica se un dispositivo supporta Connection Request
     * Check if a device supports Connection Request
     * 
     * @param CpeDevice $device
     * @return bool
     */
    public function isConnectionRequestSupported(CpeDevice $device): bool
    {
        return !empty($device->connection_request_url);
    }

    /**
     * Testa Connection Request con fallback POST se GET fallisce
     * Test Connection Request with POST fallback if GET fails
     * 
     * Alcuni CPE richiedono POST invece di GET per Connection Request
     * Some CPE require POST instead of GET for Connection Request
     * 
     * @param CpeDevice $device
     * @return array
     */
    public function testConnectionRequest(CpeDevice $device): array
    {
        // Prova prima con GET (standard TR-069)
        // Try GET first (TR-069 standard)
        $result = $this->sendConnectionRequest($device);

        // Se GET fallisce con HTTP error, prova POST
        // If GET fails with HTTP error, try POST
        if (!$result['success'] && ($result['error_code'] ?? '') === 'HTTP_ERROR') {
            Log::info("Retrying Connection Request with POST method for device {$device->serial_number}");
            
            try {
                $request = Http::timeout(self::REQUEST_TIMEOUT);
                
                if (!empty($device->connection_request_username) && !empty($device->connection_request_password)) {
                    $request = $request->withBasicAuth(
                        $device->connection_request_username, 
                        $device->connection_request_password
                    );
                }

                $response = $request->post($device->connection_request_url);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'message' => 'Connection Request sent successfully (POST method)',
                        'http_status' => $response->status(),
                        'method' => 'POST'
                    ];
                }
            } catch (\Exception $e) {
                // Ignora errore POST e ritorna risultato GET originale
                // Ignore POST error and return original GET result
            }
        }

        return $result;
    }
}
