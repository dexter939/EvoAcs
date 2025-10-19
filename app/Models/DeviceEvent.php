<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DeviceEvent - Modello per cronologia eventi dispositivo
 * DeviceEvent - Model for device event history
 * 
 * Traccia tutti gli eventi significativi per ogni dispositivo CPE
 * Tracks all significant events for each CPE device
 * 
 * @property string $event_type Tipo evento (provisioning, reboot, firmware_update, etc.) / Event type
 * @property string $event_status Stato evento (pending, processing, completed, failed) / Event status
 * @property string $event_title Titolo evento / Event title
 * @property string $event_description Descrizione dettagliata / Detailed description
 * @property array $event_data Dati aggiuntivi (JSON) / Additional data (JSON)
 * @property string $triggered_by Sorgente evento (web, tr069, tr369, system) / Event source
 */
class DeviceEvent extends Model
{
    /**
     * Campi assegnabili in massa
     * Mass assignable fields
     */
    protected $fillable = [
        'cpe_device_id',
        'event_type',
        'event_status',
        'event_title',
        'event_description',
        'event_data',
        'triggered_by',
        'user_email',
        'started_at',
        'completed_at',
    ];

    /**
     * Cast dei tipi di dato
     * Data type casting
     */
    protected $casts = [
        'event_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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
     * Scope per eventi di un certo tipo
     * Scope for events of a certain type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }
    
    /**
     * Scope per eventi con un certo stato
     * Scope for events with a certain status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('event_status', $status);
    }
}
