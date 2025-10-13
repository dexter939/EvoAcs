<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Customer - Modello per clienti multi-tenant
 * Customer - Model for multi-tenant customers
 * 
 * Rappresenta un cliente con servizi e dispositivi associati
 * Represents a customer with associated services and devices
 * 
 * @property string $name Nome cliente / Customer name
 * @property string $external_id ID sistema esterno CRM/billing / External CRM/billing system ID
 * @property string $status Stato: active, inactive, suspended, terminated
 * @property array $billing_contact Contatto fatturazione (nome, email, telefono) / Billing contact info
 * @property string $contact_email Email contatto / Contact email
 * @property string $timezone Timezone cliente / Customer timezone
 * @property array $address Indirizzo fisico / Physical address
 * @property array $metadata Metadati personalizzati / Custom metadata
 */
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Campi assegnabili in massa
     * Mass assignable fields
     */
    protected $fillable = [
        'name',
        'external_id',
        'status',
        'billing_contact',
        'contact_email',
        'timezone',
        'address',
        'metadata',
    ];

    /**
     * Cast dei tipi di dato
     * Data type casting
     */
    protected $casts = [
        'billing_contact' => 'array',
        'address' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Relazione con servizi del cliente
     * Relationship with customer services
     * 
     * @return HasMany
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Scope per filtrare clienti attivi
     * Scope to filter active customers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
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
