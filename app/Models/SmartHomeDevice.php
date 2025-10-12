<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmartHomeDevice extends Model
{
    protected $fillable = [
        'cpe_device_id', 'device_class', 'device_name', 'protocol', 'ieee_address',
        'manufacturer', 'model', 'firmware_version', 'status', 'capabilities',
        'current_state', 'configuration', 'last_seen'
    ];

    protected $casts = [
        'capabilities' => 'array',
        'current_state' => 'array',
        'configuration' => 'array',
        'last_seen' => 'datetime'
    ];

    public function cpeDevice()
    {
        return $this->belongsTo(CpeDevice::class);
    }

    public function scopeOfClass($query, $deviceClass)
    {
        return $query->where('device_class', $deviceClass);
    }

    public function scopeByProtocol($query, $protocol)
    {
        return $query->where('protocol', $protocol);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }
}
