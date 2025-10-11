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
 * Rappresenta un dispositivo CPE gestito dal sistema ACS via TR-069 o TR-369 (USP)
 * Represents a CPE device managed by the ACS system via TR-069 or TR-369 (USP)
 * 
 * @property string $serial_number Numero seriale univoco dispositivo / Unique device serial number
 * @property string $protocol_type Protocollo: tr069, tr369 / Protocol: tr069, tr369
 * @property string $usp_endpoint_id Endpoint ID USP univoco (TR-369) / Unique USP Endpoint ID (TR-369)
 * @property string $mqtt_client_id Client ID MQTT per transport / MQTT Client ID for transport
 * @property string $mtp_type Message Transfer Protocol: mqtt, websocket, stomp, coap, uds
 * @property string $oui Organizationally Unique Identifier (IEEE)
 * @property string $product_class Classe prodotto TR-069 / TR-069 product class
 * @property string $manufacturer Produttore dispositivo / Device manufacturer
 * @property string $connection_request_url URL per richieste ACS->CPE / URL for ACS->CPE requests
 * @property string $status Stato: online, offline, provisioning, error
 * @property \DateTime $last_inform Ultimo messaggio Inform ricevuto / Last Inform message received
 * @property array $device_info Informazioni aggiuntive dispositivo (JSON) / Additional device info (JSON)
 * @property array $usp_capabilities Capabilities USP supportate (JSON) / Supported USP capabilities (JSON)
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
        'protocol_type',
        'usp_endpoint_id',
        'mqtt_client_id',
        'mtp_type',
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
        'usp_capabilities',
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
        'usp_capabilities' => 'array',
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
     * Relazione con test diagnostici TR-143
     * Relationship with TR-143 diagnostic tests
     * 
     * @return HasMany
     */
    public function diagnosticTests(): HasMany
    {
        return $this->hasMany(DiagnosticTest::class);
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

    /**
     * Relazione con sottoscrizioni USP (TR-369)
     * Relationship with USP subscriptions (TR-369)
     * 
     * @return HasMany
     */
    public function uspSubscriptions(): HasMany
    {
        return $this->hasMany(UspSubscription::class);
    }

    /**
     * Scope per filtrare dispositivi TR-069
     * Scope to filter TR-069 devices
     */
    public function scopeTr069($query)
    {
        return $query->where('protocol_type', 'tr069');
    }

    /**
     * Scope per filtrare dispositivi TR-369 (USP)
     * Scope to filter TR-369 (USP) devices
     */
    public function scopeTr369($query)
    {
        return $query->where('protocol_type', 'tr369');
    }

    /**
     * Scope per filtrare per tipo protocollo
     * Scope to filter by protocol type
     */
    public function scopeByProtocol($query, string $protocol)
    {
        return $query->where('protocol_type', $protocol);
    }

    /**
     * Scope per filtrare dispositivi USP con MQTT
     * Scope to filter USP devices with MQTT transport
     */
    public function scopeUspMqtt($query)
    {
        return $query->where('protocol_type', 'tr369')
                     ->where('mtp_type', 'mqtt');
    }

    /**
     * Verifica se il dispositivo usa TR-369/USP
     * Check if device uses TR-369/USP
     */
    public function isUsp(): bool
    {
        return $this->protocol_type === 'tr369';
    }

    /**
     * Verifica se il dispositivo usa TR-069
     * Check if device uses TR-069
     */
    public function isTr069(): bool
    {
        return $this->protocol_type === 'tr069';
    }
}
