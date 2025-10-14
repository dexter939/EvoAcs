<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkClient extends Model
{
    protected $fillable = [
        'device_id',
        'mac_address',
        'ip_address',
        'hostname',
        'connection_type',
        'interface_name',
        'signal_strength',
        'active',
        'last_seen',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_seen' => 'datetime',
        'signal_strength' => 'integer',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(CpeDevice::class, 'device_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeWifi($query)
    {
        return $query->whereIn('connection_type', ['wifi_2.4ghz', 'wifi_5ghz', 'wifi_6ghz']);
    }

    public function scopeLan($query)
    {
        return $query->where('connection_type', 'lan');
    }

    public function getSignalQualityAttribute()
    {
        if (!$this->signal_strength) {
            return null;
        }

        if ($this->signal_strength >= -50) return 'excellent';
        if ($this->signal_strength >= -60) return 'good';
        if ($this->signal_strength >= -70) return 'fair';
        return 'poor';
    }

    public function getConnectionIconAttribute()
    {
        return match($this->connection_type) {
            'lan' => 'fa-ethernet',
            'wifi_2.4ghz', 'wifi_5ghz', 'wifi_6ghz' => 'fa-wifi',
            default => 'fa-network-wired'
        };
    }
}
