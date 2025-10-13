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
     * @return array Response simulata con metodi status() e successful()
     */
    private function sendRequestWithAuth(string $url, string $username, string $password, string $authMethod)
    {
        if ($authMethod === 'digest') {
            // HTTP Digest Auth usa Laravel HTTP client (supporta withDigestAuth dal Laravel 7+)
            // HTTP Digest Auth uses Laravel HTTP client (supports withDigestAuth since Laravel 7+)
            return Http::timeout(self::REQUEST_TIMEOUT)
                ->withDigestAuth($username, $password)
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
