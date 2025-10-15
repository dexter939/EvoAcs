<?php

namespace App\Services;

use App\Models\CpeDevice;
use App\Models\PendingCommand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * PendingCommandService - Gestisce comandi accodati per NAT Traversal
 * 
 * Quando Connection Request fallisce (CPE dietro NAT), i comandi vengono accodati.
 * Durante il successivo Periodic Inform, l'ACS esegue i comandi accodati.
 * 
 * When Connection Request fails (CPE behind NAT), commands are queued.
 * During next Periodic Inform, ACS executes queued commands.
 */
class PendingCommandService
{
    protected ConnectionRequestService $connectionRequestService;

    public function __construct(ConnectionRequestService $connectionRequestService)
    {
        $this->connectionRequestService = $connectionRequestService;
    }

    /**
     * Prova Connection Request, accoda sempre il comando per esecuzione durante TR-069 session
     * Try Connection Request, always queue command for execution during TR-069 session
     * 
     * IMPORTANTE: Connection Request serve solo a "svegliare" il CPE e farlo fare Inform.
     * Il comando vero viene eseguito durante la sessione TR-069 (SOAP), non durante Connection Request.
     * 
     * IMPORTANT: Connection Request only "wakes up" the CPE to make it Inform.
     * The actual command is executed during TR-069 session (SOAP), not during Connection Request.
     * 
     * @param CpeDevice $device
     * @param string $commandType provision|reboot|get_parameters|set_parameters|diagnostic|firmware_update
     * @param array|null $parameters Parametri specifici del comando
     * @param int $priority Priorità 1=high, 5=normal, 10=low
     * @return array [success, message, queued (bool), immediate (bool)]
     */
    public function sendCommandWithNatFallback(
        CpeDevice $device, 
        string $commandType, 
        ?array $parameters = null, 
        int $priority = 5
    ): array {
        
        // Accoda sempre il comando (viene eseguito durante TR-069 session, non durante Connection Request)
        // Always queue command (executed during TR-069 session, not during Connection Request)
        $pendingCommand = $this->queueCommand($device, $commandType, $parameters, $priority);
        
        // Prova Connection Request per svegliare il CPE immediatamente
        // Try Connection Request to wake up CPE immediately
        $result = $this->connectionRequestService->sendConnectionRequest($device);

        if ($result['success']) {
            // Connection Request riuscito → comando verrà eseguito a breve (~5s)
            // Connection Request succeeded → command will execute soon (~5s)
            Log::info("Command queued, Connection Request sent (immediate execution)", [
                'device' => $device->serial_number,
                'command_type' => $commandType,
                'pending_command_id' => $pendingCommand->id
            ]);

            return [
                'success' => true,
                'message' => 'Command will execute immediately (Connection Request sent)',
                'queued' => true,
                'immediate' => true,
                'pending_command_id' => $pendingCommand->id,
                'method' => 'connection_request'
            ];
        }

        // Connection Request fallito → comando verrà eseguito al prossimo Periodic Inform (~60s)
        // Connection Request failed → command will execute on next Periodic Inform (~60s)
        $isNatFailure = in_array($result['error_code'] ?? '', [
            'CONNECTION_ERROR',     // Timeout/unreachable (NAT)
            'MISSING_URL',          // No ConnectionRequestURL (NAT or unconfigured)
        ]);

        if ($isNatFailure) {
            Log::info("Command queued, NAT detected (delayed execution on next Inform)", [
                'device' => $device->serial_number,
                'command_type' => $commandType,
                'pending_command_id' => $pendingCommand->id,
                'error_code' => $result['error_code']
            ]);

            return [
                'success' => true,
                'message' => 'Command queued (device behind NAT). Will execute on next Periodic Inform (~60s).',
                'queued' => true,
                'immediate' => false,
                'pending_command_id' => $pendingCommand->id,
                'method' => 'pending_queue_nat'
            ];
        }

        // Altro tipo di errore (autenticazione, HTTP error, etc.)
        // Other error type (authentication, HTTP error, etc.)
        Log::warning("Command queued, Connection Request failed (non-NAT error)", [
            'device' => $device->serial_number,
            'command_type' => $commandType,
            'pending_command_id' => $pendingCommand->id,
            'error' => $result['message']
        ]);

        // Anche in caso di errore non-NAT, il comando è accodato e verrà eseguito
        // Even for non-NAT errors, command is queued and will execute
        return [
            'success' => true,
            'message' => 'Command queued. Will execute on next Periodic Inform (Connection Request failed: ' . $result['message'] . ').',
            'queued' => true,
            'immediate' => false,
            'pending_command_id' => $pendingCommand->id,
            'method' => 'pending_queue_error'
        ];
    }

    /**
     * Accoda un comando per esecuzione futura
     * Queue a command for future execution
     * 
     * @param CpeDevice $device
     * @param string $commandType
     * @param array|null $parameters
     * @param int $priority
     * @return PendingCommand
     */
    public function queueCommand(
        CpeDevice $device,
        string $commandType,
        ?array $parameters = null,
        int $priority = 5
    ): PendingCommand {
        
        return PendingCommand::create([
            'cpe_device_id' => $device->id,
            'command_type' => $commandType,
            'parameters' => $parameters,
            'priority' => $priority,
            'status' => 'pending',
        ]);
    }

    /**
     * Ottieni tutti i comandi pending per un dispositivo (ordinati per priorità)
     * Get all pending commands for a device (ordered by priority)
     * 
     * @param CpeDevice $device
     * @return Collection<PendingCommand>
     */
    public function getPendingCommands(CpeDevice $device): Collection
    {
        return PendingCommand::forDevice($device->id)
            ->pending()
            ->byPriority()
            ->get();
    }

    /**
     * Cancella un comando pending
     * Cancel a pending command
     * 
     * @param int $commandId
     * @return bool
     */
    public function cancelCommand(int $commandId): bool
    {
        $command = PendingCommand::find($commandId);
        
        if (!$command) {
            return false;
        }

        return $command->markAsCancelled();
    }

    /**
     * Ritenta un comando fallito
     * Retry a failed command
     * 
     * @param int $commandId
     * @return bool
     */
    public function retryCommand(int $commandId): bool
    {
        $command = PendingCommand::find($commandId);
        
        if (!$command || !$command->canRetry()) {
            return false;
        }

        return $command->update([
            'status' => 'pending',
            'error_message' => null,
        ]);
    }

    /**
     * Ottieni statistiche comandi pending per dashboard
     * Get pending commands statistics for dashboard
     * 
     * @param int|null $deviceId Opzionale: filtra per dispositivo
     * @return array
     */
    public function getStatistics(?int $deviceId = null): array
    {
        $query = PendingCommand::query();
        
        if ($deviceId) {
            $query->forDevice($deviceId);
        }

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'processing' => (clone $query)->where('status', 'processing')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'high_priority' => (clone $query)->where('priority', '<=', 2)->where('status', 'pending')->count(),
        ];
    }

    /**
     * Pulisci comandi vecchi completati/falliti (housekeeping)
     * Clean old completed/failed commands (housekeeping)
     * 
     * @param int $daysOld Rimuovi comandi più vecchi di N giorni (default 30)
     * @return int Numero comandi rimossi
     */
    public function cleanOldCommands(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);

        return PendingCommand::whereIn('status', ['completed', 'cancelled'])
            ->where('updated_at', '<', $cutoffDate)
            ->delete();
    }
}
