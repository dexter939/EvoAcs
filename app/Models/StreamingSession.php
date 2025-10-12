<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StreamingSession extends Model
{
    protected $fillable = [
        'stb_service_id', 'session_id', 'channel_name', 'content_url',
        'status', 'bitrate', 'packet_loss', 'jitter', 'qos_metrics',
        'started_at', 'ended_at'
    ];

    protected $casts = [
        'qos_metrics' => 'array',
        'jitter' => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at' => 'datetime'
    ];

    public function stbService()
    {
        return $this->belongsTo(StbService::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
