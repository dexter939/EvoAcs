<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FirmwareDeployment - Modello per deployment firmware su dispositivi
 * FirmwareDeployment - Model for firmware deployment to devices
 * 
 * Gestisce il ciclo di vita del deployment firmware via TR-069 Download
 * Manages firmware deployment lifecycle via TR-069 Download
 * 
 * @property string $status Stato: scheduled, downloading, installing, completed, failed
 * @property int $download_progress Percentuale download (0-100) / Download percentage (0-100)
 * @property \DateTime $scheduled_at Data/ora schedulazione / Scheduled date/time
 * @property \DateTime $started_at Data/ora inizio download / Download start date/time
 * @property \DateTime $completed_at Data/ora completamento / Completion date/time
 */
class FirmwareDeployment extends Model
{
    /**
     * Campi assegnabili in massa
     * Mass assignable fields
     */
    protected $fillable = [
        'firmware_version_id',
        'cpe_device_id',
        'status',
        'download_progress',
        'scheduled_at',
        'started_at',
        'completed_at',
        'error_message'
    ];

    /**
     * Cast dei tipi di dato
     * Data type casting
     */
    protected $casts = [
        'download_progress' => 'integer',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Valori di default
     * Default values
     */
    protected $attributes = [
        'status' => 'scheduled',
        'download_progress' => 0,
    ];

    /**
     * Relazione con versione firmware da deployare
     * Relationship with firmware version to deploy
     * 
     * @return BelongsTo
     */
    public function firmwareVersion(): BelongsTo
    {
        return $this->belongsTo(FirmwareVersion::class);
    }

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
