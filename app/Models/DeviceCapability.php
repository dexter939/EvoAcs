<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model per TR-111 Device Capabilities
 * Model for TR-111 Device Capabilities
 * 
 * Standard: TR-111 (Device Parameter Discovery)
 * 
 * Traccia parametri scoperti dinamicamente da dispositivi CPE
 * Tracks dynamically discovered parameters from CPE devices
 */
class DeviceCapability extends Model
{
    use HasFactory;

    protected $fillable = [
        'cpe_device_id',
        'parameter_path',
        'parameter_name',
        'is_writable',
        'data_type',
        'is_vendor_specific',
        'discovered_at',
        'last_verified_at',
        'metadata'
    ];

    protected $casts = [
        'is_writable' => 'boolean',
        'is_vendor_specific' => 'boolean',
        'discovered_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relazione: capability appartiene a dispositivo CPE
     * Relationship: capability belongs to CPE device
     */
    public function cpeDevice()
    {
        return $this->belongsTo(CpeDevice::class);
    }

    /**
     * Scope per filtrare parametri scrivibili
     * Scope to filter writable parameters
     */
    public function scopeWritable($query)
    {
        return $query->where('is_writable', true);
    }

    /**
     * Scope per filtrare parametri vendor-specific
     * Scope to filter vendor-specific parameters
     */
    public function scopeVendorSpecific($query)
    {
        return $query->where('is_vendor_specific', true);
    }

    /**
     * Scope per filtrare parametri standard
     * Scope to filter standard parameters
     */
    public function scopeStandard($query)
    {
        return $query->where('is_vendor_specific', false);
    }

    /**
     * Scope per parametri di un percorso specifico (con pattern matching)
     * Scope for parameters matching a specific path pattern
     */
    public function scopePathLike($query, $pattern)
    {
        return $query->where('parameter_path', 'LIKE', $pattern);
    }

    /**
     * Verifica se il parametro è stato scoperto di recente (ultimi 7 giorni)
     * Check if parameter was discovered recently (last 7 days)
     */
    public function isRecentlyDiscovered(): bool
    {
        if (!$this->discovered_at) {
            return false;
        }
        
        return $this->discovered_at->isAfter(now()->subDays(7));
    }

    /**
     * Verifica se il parametro necessita verifica (oltre 30 giorni dall'ultima)
     * Check if parameter needs verification (over 30 days since last)
     */
    public function needsVerification(): bool
    {
        if (!$this->last_verified_at) {
            return true;
        }
        
        return $this->last_verified_at->isBefore(now()->subDays(30));
    }

    /**
     * Marca parametro come verificato
     * Mark parameter as verified
     */
    public function markAsVerified(): void
    {
        $this->update(['last_verified_at' => now()]);
    }

    /**
     * Ottiene il percorso parent del parametro
     * Get parent path of the parameter
     */
    public function getParentPath(): ?string
    {
        $parts = explode('.', $this->parameter_path);
        array_pop();
        
        return count($parts) > 0 ? implode('.', $parts) : null;
    }

    /**
     * Verifica se è un parametro leaf (senza figli)
     * Check if it's a leaf parameter (no children)
     */
    public function isLeafParameter(): bool
    {
        // Se il path termina con un numero, è probabilmente un'istanza di un oggetto multi-instance
        // If path ends with a number, it's probably a multi-instance object instance
        return !str_ends_with($this->parameter_path, '.');
    }
}
