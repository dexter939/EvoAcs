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
     * Prova Connection Request, se fallisce accoda il comando
     * Try Connection Request, if fails queue the command
     * 
     * @param CpeDevice $device
     * @param string $commandType provision|reboot|get_parameters|set_parameters|diagnostic|firmware_update
     * @param array|null $parameters Parametri specifici del comando
     * @param int $priority Priorità 1=high, 5=normal, 10=low
     * @return array [success, message, queued (bool)]
     */
    public function sendCommandWithNatFallback(
        CpeDevice $device, 
        string $commandType, 
        ?array $parameters = null, 
        int $priority = 5
    ): array {
        
        // Prova prima Connection Request diretto
        // Try Connection Request first
        $result = $this->connectionRequestService->sendConnectionRequest($device);

        if ($result['success']) {
            // Connection Request riuscito
            // Connection Request succeeded
            Log::info("Command sent via Connection Request", [
                'device' => $device->serial_number,
                'command_type' => $commandType
            ]);

            return [
                'success' => true,
                'message' => 'Command sent via Connection Request',
                'queued' => false,
                'method' => 'connection_request'
            ];
        }

        // Connection Request fallito - verifica se è NAT failure
        // Connection Request failed - check if it's NAT failure
        $isNatFailure = in_array($result['error_code'] ?? '', [
            'CONNECTION_ERROR',     // Timeout/unreachable (NAT)
            'MISSING_URL',          // No ConnectionRequestURL (NAT or unconfigured)
        ]);

        if ($isNatFailure) {
            // Accoda il comando per esecuzione successiva durante Periodic Inform
            // Queue command for later execution during Periodic Inform
            $pendingCommand = $this->queueCommand($device, $commandType, $parameters, $priority);

            Log::info("Command queued due to NAT (Connection Request failed)", [
                'device' => $device->serial_number,
                'command_type' => $commandType,
                'pending_command_id' => $pendingCommand->id,
                'error_code' => $result['error_code']
            ]);

            return [
                'success' => true,
                'message' => 'Command queued (device behind NAT). Will execute on next Periodic Inform.',
                'queued' => true,
                'pending_command_id' => $pendingCommand->id,
                'method' => 'pending_queue'
            ];
        }

        // Altro tipo di errore (autenticazione, HTTP error, etc.)
        // Other error type (authentication, HTTP error, etc.)
        Log::error("Command failed (not NAT-related)", [
            'device' => $device->serial_number,
            'command_type' => $commandType,
            'error' => $result['message']
        ]);

        return [
            'success' => false,
            'message' => $result['message'],
            'queued' => false,
            'error_code' => $result['error_code'] ?? 'UNKNOWN'
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
