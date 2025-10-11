<?php

namespace App\Services;

use App\Models\Tr069Session;
use App\Models\CpeDevice;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * TR069SessionManager - Gestione sessioni TR-069 CWMP
 * TR069SessionManager - TR-069 CWMP session management
 * 
 * Service per gestire il ciclo di vita delle sessioni TR-069 tra ACS e dispositivi CPE.
 * Implementa la logica di session management richiesta dallo standard TR-069:
 * - Creazione e tracking sessioni HTTP con cookie
 * - Gestione coda comandi SOAP
 * - Timeout e chiusura sessioni
 * 
 * Service to manage TR-069 session lifecycle between ACS and CPE devices.
 * Implements session management logic required by TR-069 standard:
 * - HTTP session creation and tracking with cookies
 * - SOAP command queue management
 * - Session timeout and closure
 */
class TR069SessionManager
{
    /**
     * Crea o recupera una sessione esistente per un dispositivo
     * Create or retrieve existing session for a device
     * 
     * Implementa la logica TR-069 di session tracking:
     * - Se esiste un cookie nella richiesta, cerca la sessione corrispondente
     * - Se non esiste sessione attiva, ne crea una nuova
     * - Gestisce timeout automatico di sessioni scadute
     * 
     * @param CpeDevice $device Dispositivo CPE
     * @param string|null $cookieValue Cookie dalla richiesta HTTP (opzionale)
     * @param string|null $deviceIp IP address dispositivo
     * @return Tr069Session
     */
    public function getOrCreateSession(CpeDevice $device, ?string $cookieValue = null, ?string $deviceIp = null): Tr069Session
    {
        // Cerca sessione attiva tramite cookie se presente
        // Search for active session via cookie if present
        if ($cookieValue) {
            $session = Tr069Session::where('cookie', $cookieValue)
                ->where('cpe_device_id', $device->id)
                ->where('status', 'active')
                ->first();
            
            if ($session) {
                // Verifica timeout
                // Check timeout
                if ($session->isTimedOut()) {
                    $session->close('timeout');
                    \Log::info('TR-069 session timed out', ['session_id' => $session->session_id]);
                } else {
                    // Aggiorna last_activity
                    // Update last_activity
                    $session->touch();
                    return $session;
                }
            }
        }
        
        // Cerca sessione attiva per dispositivo (fallback senza cookie)
        // Search for active session by device (fallback without cookie)
        $session = Tr069Session::where('cpe_device_id', $device->id)
            ->where('status', 'active')
            ->orderBy('last_activity', 'desc')
            ->first();
        
        if ($session) {
            if ($session->isTimedOut()) {
                $session->close('timeout');
                \Log::info('TR-069 session timed out', ['session_id' => $session->session_id]);
            } else {
                $session->touch();
                return $session;
            }
        }
        
        // Crea nuova sessione
        // Create new session
        return $this->createSession($device, $deviceIp);
    }

    /**
     * Crea una nuova sessione TR-069
     * Create a new TR-069 session
     * 
     * @param CpeDevice $device Dispositivo CPE
     * @param string|null $deviceIp IP address dispositivo
     * @return Tr069Session
     */
    public function createSession(CpeDevice $device, ?string $deviceIp = null): Tr069Session
    {
        $sessionId = Str::uuid()->toString();
        $cookie = 'TR069_' . Str::random(32);
        
        $session = Tr069Session::create([
            'session_id' => $sessionId,
            'cpe_device_id' => $device->id,
            'status' => 'active',
            'cookie' => $cookie,
            'device_ip' => $deviceIp,
            'started_at' => Carbon::now(),
            'last_activity' => Carbon::now(),
            'timeout_seconds' => 30, // Standard TR-069 timeout
        ]);
        
        \Log::info('TR-069 session created', [
            'session_id' => $sessionId,
            'device_id' => $device->id,
            'cookie' => $cookie
        ]);
        
        return $session;
    }

    /**
     * Recupera sessione tramite cookie
     * Retrieve session by cookie
     * 
     * @param string $cookie Cookie value
     * @return Tr069Session|null
     */
    public function getSessionByCookie(string $cookie): ?Tr069Session
    {
        return Tr069Session::where('cookie', $cookie)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Recupera sessione attiva per dispositivo
     * Retrieve active session for device
     * 
     * @param CpeDevice $device Dispositivo CPE
     * @return Tr069Session|null
     */
    public function getActiveSession(CpeDevice $device): ?Tr069Session
    {
        return Tr069Session::where('cpe_device_id', $device->id)
            ->where('status', 'active')
            ->orderBy('last_activity', 'desc')
            ->first();
    }

    /**
     * Accoda un comando SOAP alla sessione
     * Queue a SOAP command to session
     * 
     * @param Tr069Session $session Sessione corrente
     * @param string $commandType Tipo comando (GetParameterValues, SetParameterValues, etc.)
     * @param array $params Parametri comando
     * @param int|null $taskId ID task di provisioning (opzionale)
     * @return void
     */
    public function queueCommand(Tr069Session $session, string $commandType, array $params, ?int $taskId = null): void
    {
        $session->addPendingCommand($commandType, $params, $taskId);
        
        \Log::info('TR-069 command queued', [
            'session_id' => $session->session_id,
            'command_type' => $commandType,
            'task_id' => $taskId
        ]);
    }

    /**
     * Ottiene il prossimo comando da inviare
     * Get next command to send
     * 
     * @param Tr069Session $session Sessione corrente
     * @return array|null
     */
    public function getNextCommand(Tr069Session $session): ?array
    {
        return $session->popNextCommand();
    }

    /**
     * Verifica se la sessione ha comandi pendenti
     * Check if session has pending commands
     * 
     * @param Tr069Session $session Sessione corrente
     * @return bool
     */
    public function hasPendingCommands(Tr069Session $session): bool
    {
        $commands = $session->pending_commands ?? [];
        return !empty($commands);
    }

    /**
     * Chiude la sessione
     * Close session
     * 
     * @param Tr069Session $session Sessione da chiudere
     * @param string $status Stato finale (closed, timeout)
     * @return void
     */
    public function closeSession(Tr069Session $session, string $status = 'closed'): void
    {
        $session->close($status);
        
        \Log::info('TR-069 session closed', [
            'session_id' => $session->session_id,
            'status' => $status
        ]);
    }

    /**
     * Pulisce sessioni scadute (cronjob o comando artisan)
     * Clean up timed out sessions (cronjob or artisan command)
     * 
     * @return int Numero sessioni chiuse / Number of closed sessions
     */
    public function cleanupTimedOutSessions(): int
    {
        $timedOutSessions = Tr069Session::timedOut()->get();
        $count = 0;
        
        foreach ($timedOutSessions as $session) {
            $session->close('timeout');
            $count++;
        }
        
        if ($count > 0) {
            \Log::info('TR-069 timed out sessions cleaned', ['count' => $count]);
        }
        
        return $count;
    }

    /**
     * Ottiene statistiche sessioni
     * Get session statistics
     * 
     * @return array
     */
    public function getSessionStats(): array
    {
        return [
            'active' => Tr069Session::where('status', 'active')->count(),
            'closed' => Tr069Session::where('status', 'closed')->count(),
            'timeout' => Tr069Session::where('status', 'timeout')->count(),
            'total' => Tr069Session::count(),
        ];
    }
}
