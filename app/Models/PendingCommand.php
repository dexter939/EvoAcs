<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * PendingCommand - Model per comandi accodati (NAT Traversal)
 * 
 * Quando Connection Request fallisce (CPE dietro NAT), i comandi vengono accodati.
 * Durante il successivo Periodic Inform, l'ACS esegue i comandi accodati.
 * 
 * When Connection Request fails (CPE behind NAT), commands are queued.
 * During next Periodic Inform, ACS executes queued commands.
 * 
 * @property int $id
 * @property int $cpe_device_id
 * @property string $command_type provision|reboot|get_parameters|set_parameters|diagnostic|firmware_update
 * @property string $status pending|processing|completed|failed|cancelled
 * @property int $priority 1=high, 10=low, default=5
 * @property array|null $parameters Command-specific parameters (JSON)
 * @property array|null $result_data Execution result (JSON)
 * @property string|null $error_message Error message if failed
 * @property int $retry_count Number of retry attempts
 * @property int $max_retries Maximum retry attempts (default 3)
 * @property Carbon|null $executed_at When command was executed
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PendingCommand extends Model
{
    /**
     * Campi assegnabili in massa
     * Mass assignable fields
     */
    protected $fillable = [
        'cpe_device_id',
        'command_type',
        'status',
        'priority',
        'parameters',
        'result_data',
        'error_message',
        'retry_count',
        'max_retries',
        'executed_at',
    ];

    /**
     * Cast dei tipi di dato
     * Data type casting
     */
    protected $casts = [
        'parameters' => 'array',
        'result_data' => 'array',
        'priority' => 'integer',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'executed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Valori di default
     * Default values
     */
    protected $attributes = [
        'status' => 'pending',
        'priority' => 5,
        'retry_count' => 0,
        'max_retries' => 3,
    ];

    /**
     * Relazione con dispositivo CPE target
     * Relationship with target CPE device
     * 
     * @return BelongsTo
     */
    public function cpeDevice(): BelongsTo
    {
        return $this->belongsTo(CpeDevice::class);
    }

    /**
     * Scope: Solo comandi pending (non eseguiti)
     * Scope: Only pending (not executed) commands
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Comandi per un dispositivo specifico
     * Scope: Commands for specific device
     * 
     * @param Builder $query
     * @param int $deviceId
     * @return Builder
     */
    public function scopeForDevice(Builder $query, int $deviceId): Builder
    {
        return $query->where('cpe_device_id', $deviceId);
    }

    /**
     * Scope: Ordina per priorità (alta = 1, bassa = 10)
     * Scope: Order by priority (high = 1, low = 10)
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'asc')
                     ->orderBy('created_at', 'asc');
    }

    /**
     * Scope: Solo comandi che possono essere ritentati
     * Scope: Only commands that can be retried
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeRetryable(Builder $query): Builder
    {
        return $query->where('status', 'failed')
                     ->whereColumn('retry_count', '<', 'max_retries');
    }

    /**
     * Marca il comando come "in esecuzione"
     * Mark command as "processing"
     * 
     * @return bool
     */
    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => 'processing',
            'executed_at' => Carbon::now(),
        ]);
    }

    /**
     * Marca il comando come "completato" con risultato
     * Mark command as "completed" with result
     * 
     * @param array|null $result
     * @return bool
     */
    public function markAsCompleted(?array $result = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'result_data' => $result,
            'error_message' => null,
        ]);
    }

    /**
     * Marca il comando come "fallito" con messaggio di errore
     * Mark command as "failed" with error message
     * 
     * @param string $errorMessage
     * @param bool $incrementRetry Incrementa retry_count
     * @return bool
     */
    public function markAsFailed(string $errorMessage, bool $incrementRetry = true): bool
    {
        $data = [
            'status' => 'failed',
            'error_message' => $errorMessage,
        ];

        if ($incrementRetry) {
            $data['retry_count'] = $this->retry_count + 1;
        }

        return $this->update($data);
    }

    /**
     * Marca il comando come "cancellato"
     * Mark command as "cancelled"
     * 
     * @return bool
     */
    public function markAsCancelled(): bool
    {
        return $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Verifica se il comando può essere ritentato
     * Check if command can be retried
     * 
     * @return bool
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < $this->max_retries;
    }

    /**
     * Verifica se il comando è in attesa
     * Check if command is pending
     * 
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verifica se il comando è completato
     * Check if command is completed
     * 
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Ottieni priorità leggibile
     * Get human-readable priority
     * 
     * @return string
     */
    public function getPriorityLabelAttribute(): string
    {
        return match(true) {
            $this->priority <= 2 => 'High',
            $this->priority >= 8 => 'Low',
            default => 'Normal'
        };
    }
}
