<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Service - Modello per servizi telecom multi-tenant
 * Service - Model for multi-tenant telecom services
 * 
 * Rappresenta un servizio telecom (FTTH, VoIP, IPTV, etc.) associato a un cliente
 * Represents a telecom service (FTTH, VoIP, IPTV, etc.) associated with a customer
 * 
 * @property int $customer_id ID cliente / Customer ID
 * @property string $name Nome servizio / Service name
 * @property string $service_type Tipo: FTTH, VoIP, IPTV, IoT, Femtocell, Other
 * @property string $status Stato: provisioned, active, suspended, terminated
 * @property string $contract_number Numero contratto / Contract number
 * @property \DateTime $activation_at Data attivazione / Activation date
 * @property \DateTime $termination_at Data cessazione / Termination date
 * @property string $sla_tier Livello SLA (Gold, Silver, Bronze) / SLA tier
 * @property string $billing_reference Riferimento fatturazione / Billing reference
 * @property array $tags Tag categorizzazione / Categorization tags
 * @property array $metadata Metadati personalizzati / Custom metadata
 */
class Service extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Campi assegnabili in massa
     * Mass assignable fields
     */
    protected $fillable = [
        'customer_id',
        'name',
        'service_type',
        'status',
        'contract_number',
        'activation_at',
        'termination_at',
        'sla_tier',
        'billing_reference',
        'tags',
        'metadata',
    ];

    /**
     * Cast dei tipi di dato
     * Data type casting
     */
    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'activation_at' => 'datetime',
        'termination_at' => 'datetime',
    ];

    /**
     * Relazione con cliente
     * Relationship with customer
     * 
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relazione con dispositivi CPE del servizio
     * Relationship with service CPE devices
     * 
     * @return HasMany
     */
    public function cpeDevices(): HasMany
    {
        return $this->hasMany(CpeDevice::class);
    }

    /**
     * Scope per filtrare servizi attivi
     * Scope to filter active services
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope per filtrare per tipo servizio
     * Scope to filter by service type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('service_type', $type);
    }

    /**
     * Scope per filtrare per stato
     * Scope to filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
