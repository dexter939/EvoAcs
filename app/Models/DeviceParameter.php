<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DeviceParameter - Modello per parametri TR-181 dispositivo
 * DeviceParameter - Model for device TR-181 parameters
 * 
 * Memorizza parametri del data model TR-181 per ogni dispositivo CPE
 * Stores TR-181 data model parameters for each CPE device
 * 
 * @property string $parameter_path Percorso parametro TR-181 (es. Device.WiFi.SSID.1.SSID) / TR-181 parameter path
 * @property string $parameter_value Valore parametro / Parameter value
 * @property string $parameter_type Tipo dato (string, int, boolean, dateTime) / Data type
 * @property bool $is_writable Flag parametro scrivibile / Writable parameter flag
 */
class DeviceParameter extends Model
{
    /**
     * Campi assegnabili in massa
     * Mass assignable fields
     */
    protected $fillable = [
        'cpe_device_id',
        'parameter_path',
        'parameter_value',
        'parameter_type',
        'is_writable',
        'last_updated'
    ];

    /**
     * Cast dei tipi di dato
     * Data type casting
     */
    protected $casts = [
        'is_writable' => 'boolean',
        'last_updated' => 'datetime',
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
}
