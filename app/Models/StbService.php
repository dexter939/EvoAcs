<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StbService extends Model
{
    protected $fillable = [
        'cpe_device_id', 'service_type', 'frontend_type', 'streaming_protocol',
        'server_url', 'server_port', 'channel_list', 'codec_settings',
        'qos_parameters', 'enabled'
    ];

    protected $casts = [
        'channel_list' => 'array',
        'codec_settings' => 'array',
        'qos_parameters' => 'array',
        'enabled' => 'boolean'
    ];

    public function cpeDevice()
    {
        return $this->belongsTo(CpeDevice::class);
    }

    public function streamingSessions()
    {
        return $this->hasMany(StreamingSession::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
