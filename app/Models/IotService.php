<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IotService extends Model
{
    protected $fillable = [
        'cpe_device_id', 'service_type', 'service_name', 'enabled',
        'linked_devices', 'automation_rules', 'schedule', 'statistics'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'linked_devices' => 'array',
        'automation_rules' => 'array',
        'schedule' => 'array',
        'statistics' => 'array'
    ];

    public function cpeDevice()
    {
        return $this->belongsTo(CpeDevice::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('service_type', $type);
    }
}
