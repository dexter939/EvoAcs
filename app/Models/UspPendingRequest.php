<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UspPendingRequest extends Model
{
    protected $fillable = [
        'cpe_device_id',
        'msg_id',
        'message_type',
        'request_payload',
        'status',
        'delivered_at',
        'expires_at'
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(CpeDevice::class, 'cpe_device_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<=', now());
    }

    public function markAsDelivered()
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now()
        ]);
    }

    public function markAsExpired()
    {
        $this->update(['status' => 'expired']);
    }
}
