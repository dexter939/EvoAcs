<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertNotification extends Model
{
    protected $fillable = [
        'alert_type',
        'severity',
        'title',
        'message',
        'metadata',
        'related_device_id',
        'notification_channel',
        'status',
        'sent_at',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(CpeDevice::class, 'related_device_id');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
