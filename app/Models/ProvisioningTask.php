<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProvisioningTask - Modello per task di provisioning asincrono
 * ProvisioningTask - Model for asynchronous provisioning tasks
 * 
 * Rappresenta operazioni TR-069 (GetParameterValues, SetParameterValues, Reboot, Download)
 * da eseguire su dispositivi CPE tramite queue system
 * 
 * Represents TR-069 operations (GetParameterValues, SetParameterValues, Reboot, Download)
 * to execute on CPE devices via queue system
 * 
 * @property string $task_type Tipo: set_parameters, get_parameters, reboot, download, etc.
 * @property string $status Stato: pending, processing, completed, failed, cancelled
 * @property array $task_data Dati task (parametri, URL download, etc.) / Task data (parameters, download URL, etc.)
 * @property int $retry_count Numero tentativi / Retry count
 * @property int $max_retries Tentativi massimi (default 3) / Max retries (default 3)
 */
class ProvisioningTask extends Model
{
    /**
     * Campi assegnabili in massa
     * Mass assignable fields
     */
    protected $fillable = [
        'cpe_device_id',
        'task_type',
        'status',
        'task_data',
        'retry_count',
        'max_retries',
        'scheduled_at',
        'started_at',
        'completed_at',
        'error_message',
        'result_data'
    ];

    /**
     * Cast dei tipi di dato
     * Data type casting
     */
    protected $casts = [
        'task_data' => 'array',
        'result_data' => 'array',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Valori di default
     * Default values
     */
    protected $attributes = [
        'retry_count' => 0,
        'max_retries' => 3,
        'status' => 'pending',
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
}
