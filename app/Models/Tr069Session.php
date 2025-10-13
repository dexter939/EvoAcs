<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Tr069Session - Modello per gestione sessioni TR-069 CWMP
 * Tr069Session - Model for TR-069 CWMP session management
 * 
 * Traccia le sessioni SOAP attive tra ACS e dispositivi CPE secondo protocollo TR-069.
 * Implementa session management stateful per conversazioni SOAP multi-transazione.
 * 
 * Tracks active SOAP sessions between ACS and CPE devices according to TR-069 protocol.
 * Implements stateful session management for multi-transaction SOAP conversations.
 */
class Tr069Session extends Model
{
    /**
     * Attributi mass-assignable
     * Mass-assignable attributes
     * 
     * @var array<string>
     */
    protected $fillable = [
        'session_id',
        'cpe_device_id',
        'status',
        'cookie',
        'pending_commands',
        'last_command_sent',
        'current_message_id',
        'started_at',
        'last_activity',
        'ended_at',
        'timeout_seconds',
        'device_ip',
    ];

    /**
     * Cast automatici per tipi di dato
     * Automatic casts for data types
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'pending_commands' => 'array',
        'last_command_sent' => 'array',
        'current_message_id' => 'integer',
        'timeout_seconds' => 'integer',
        'started_at' => 'datetime',
        'last_activity' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Relazione con dispositivo CPE
     * Relationship with CPE device
     * 
     * @return BelongsTo
     */
    public function cpeDevice(): BelongsTo
    {
        return $this->belongsTo(CpeDevice::class);
    }

    /**
     * Verifica se la sessione è attiva
     * Check if session is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verifica se la sessione è scaduta (timeout)
     * Check if session has timed out
     * 
     * @return bool
     */
    public function isTimedOut(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $timeoutAt = $this->last_activity->addSeconds($this->timeout_seconds);
        return Carbon::now()->greaterThan($timeoutAt);
    }

    /**
     * Aggiunge un comando alla coda pending della sessione
     * Add a command to session's pending queue
     * 
     * @param string $type Tipo comando TR-069 (es. GetParameterValues, SetParameterValues)
     * @param array $params Parametri del comando
     * @param int|null $taskId ID task di provisioning associata (opzionale)
     * @return void
     */
    public function addPendingCommand(string $type, array $params, ?int $taskId = null): void
    {
        $commands = $this->pending_commands ?? [];
        
        $commands[] = [
            'type' => $type,
            'params' => $params,
            'task_id' => $taskId,
            'added_at' => Carbon::now()->toIso8601String(),
        ];
        
        $this->pending_commands = $commands;
        $this->save();
    }

    /**
     * Ottiene il prossimo comando pendente senza rimuoverlo dalla coda
     * Get next pending command without removing from queue
     * 
     * @return array|null
     */
    public function getNextCommand(): ?array
    {
        $commands = $this->pending_commands ?? [];
        return !empty($commands) ? $commands[0] : null;
    }

    /**
     * Rimuove e restituisce il prossimo comando dalla coda
     * Remove and return next command from queue
     * 
     * @return array|null
     */
    public function popNextCommand(): ?array
    {
        $commands = $this->pending_commands ?? [];
        
        if (empty($commands)) {
            return null;
        }
        
        $nextCommand = array_shift($commands);
        $this->pending_commands = $commands;
        $this->last_command_sent = $nextCommand;
        $this->save();
        
        return $nextCommand;
    }

    /**
     * Aggiorna timestamp ultima attività
     * Update last activity timestamp
     * 
     * @param string|null $attribute
     * @return bool
     */
    public function touch($attribute = null): bool
    {
        $this->last_activity = Carbon::now();
        return $this->save();
    }

    /**
     * Chiude la sessione
     * Close the session
     * 
     * @param string $status Stato finale (closed, timeout)
     * @return void
     */
    public function close(string $status = 'closed'): void
    {
        $this->status = $status;
        $this->ended_at = Carbon::now();
        $this->save();
    }

    /**
     * Incrementa e restituisce il prossimo message ID
     * Increment and return next message ID
     * 
     * @return int
     */
    public function getNextMessageId(): int
    {
        $messageId = $this->current_message_id;
        $this->current_message_id++;
        $this->save();
        
        return $messageId;
    }

    /**
     * Scope per sessioni attive
     * Scope for active sessions
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope per sessioni scadute
     * Scope for timed out sessions
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTimedOut($query)
    {
        return $query->where('status', 'active')
            ->where('last_activity', '<', Carbon::now()->subSeconds(30));
    }
}
