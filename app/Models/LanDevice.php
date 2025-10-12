<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model per TR-64 LAN Devices
 * Model for TR-64 LAN Devices
 * 
 * Standard: TR-064 (LAN-Side CPE Configuration via UPnP/SOAP)
 * 
 * Dispositivi LAN scoperti tramite UPnP/SSDP
 * LAN devices discovered via UPnP/SSDP
 */
class LanDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'cpe_device_id',
        'usn',
        'device_type',
        'friendly_name',
        'manufacturer',
        'model_name',
        'serial_number',
        'ip_address',
        'mac_address',
        'port',
        'services',
        'description_url',
        'location',
        'status',
        'last_seen',
        'discovered_at',
        'metadata'
    ];

    protected $casts = [
        'services' => 'array',
        'metadata' => 'array',
        'last_seen' => 'datetime',
        'discovered_at' => 'datetime',
        'port' => 'integer',
    ];

    /**
     * Relazione: LAN device appartiene a CPE device
     * Relationship: LAN device belongs to CPE device
     */
    public function cpeDevice()
    {
        return $this->belongsTo(CpeDevice::class);
    }

    /**
     * Scope per dispositivi attivi
     * Scope for active devices
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope per dispositivi offline
     * Scope for offline devices
     */
    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    /**
     * Scope per dispositivi visti recentemente (ultimi 5 minuti)
     * Scope for recently seen devices (last 5 minutes)
     */
    public function scopeRecentlySeen($query)
    {
        return $query->where('last_seen', '>=', now()->subMinutes(5));
    }

    /**
     * Scope per tipo dispositivo
     * Scope by device type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('device_type', $type);
    }

    /**
     * Verifica se dispositivo è online (visto negli ultimi 5 minuti)
     * Check if device is online (seen in last 5 minutes)
     */
    public function isOnline(): bool
    {
        if (!$this->last_seen) {
            return false;
        }
        
        return $this->last_seen->isAfter(now()->subMinutes(5));
    }

    /**
     * Verifica se dispositivo supporta un servizio specifico
     * Check if device supports a specific service
     */
    public function hasService(string $serviceType): bool
    {
        if (!$this->services) {
            return false;
        }

        foreach ($this->services as $service) {
            if (isset($service['serviceType']) && $service['serviceType'] === $serviceType) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ottiene URL controllo per servizio specifico
     * Get control URL for specific service
     */
    public function getServiceControlUrl(string $serviceType): ?string
    {
        if (!$this->services) {
            return null;
        }

        foreach ($this->services as $service) {
            if (isset($service['serviceType']) && $service['serviceType'] === $serviceType) {
                return $service['controlURL'] ?? null;
            }
        }

        return null;
    }

    /**
     * Marca dispositivo come visto ora
     * Mark device as seen now
     */
    public function updateLastSeen(): void
    {
        $this->update([
            'last_seen' => now(),
            'status' => 'active'
        ]);
    }

    /**
     * Marca dispositivo come offline
     * Mark device as offline
     */
    public function markAsOffline(): void
    {
        $this->update(['status' => 'offline']);
    }
}
