<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CpeDevice - Modello per dispositivi CPE (Customer Premises Equipment)
 * CpeDevice - Model for CPE (Customer Premises Equipment) devices
 * 
 * Rappresenta un dispositivo CPE gestito dal sistema ACS via TR-069
 * Represents a CPE device managed by the ACS system via TR-069
 * 
 * @property string $serial_number Numero seriale univoco dispositivo / Unique device serial number
 * @property string $oui Organizationally Unique Identifier (IEEE)
 * @property string $product_class Classe prodotto TR-069 / TR-069 product class
 * @property string $manufacturer Produttore dispositivo / Device manufacturer
 * @property string $connection_request_url URL per richieste ACS->CPE / URL for ACS->CPE requests
 * @property string $status Stato: online, offline, provisioning, error
 * @property \DateTime $last_inform Ultimo messaggio Inform ricevuto / Last Inform message received
 * @property array $device_info Informazioni aggiuntive dispositivo (JSON) / Additional device info (JSON)
 */
class CpeDevice extends Model
{
    use SoftDeletes;

    /**
     * Campi assegnabili in massa
     * Mass assignable fields
     */
    protected $fillable = [
        'serial_number',
        'oui',
        'product_class',
        'manufacturer',
        'model_name',
        'hardware_version',
        'software_version',
        'connection_request_url',
        'connection_request_username',
        'connection_request_password',
        'ip_address',
        'mac_address',
        'status',
        'last_inform',
        'last_contact',
        'configuration_profile_id',
        'device_info',
        'wan_info',
        'wifi_info',
        'is_active',
        'notes'
    ];

    /**
     * Cast dei tipi di dato
     * Data type casting
     */
    protected $casts = [
        'device_info' => 'array',
        'wan_info' => 'array',
        'wifi_info' => 'array',
        'is_active' => 'boolean',
        'last_inform' => 'datetime',
        'last_contact' => 'datetime',
    ];

    /**
     * Relazione con profilo configurazione
     * Relationship with configuration profile
     * 
     * @return BelongsTo
     */
    public function configurationProfile(): BelongsTo
    {
        return $this->belongsTo(ConfigurationProfile::class);
    }

    /**
     * Relazione con parametri TR-181 del dispositivo
     * Relationship with device TR-181 parameters
     * 
     * @return HasMany
     */
    public function parameters(): HasMany
    {
        return $this->hasMany(DeviceParameter::class);
    }

    /**
     * Relazione con task di provisioning
     * Relationship with provisioning tasks
     * 
     * @return HasMany
     */
    public function provisioningTasks(): HasMany
    {
        return $this->hasMany(ProvisioningTask::class);
    }

    /**
     * Relazione con deployment firmware
     * Relationship with firmware deployments
     * 
     * @return HasMany
     */
    public function firmwareDeployments(): HasMany
    {
        return $this->hasMany(FirmwareDeployment::class);
    }
}
