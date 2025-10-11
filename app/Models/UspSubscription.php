<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UspSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'cpe_device_id',
        'subscription_id',
        'event_path',
        'reference_list',
        'notification_retry',
        'persist',
        'status',
        'expires_at',
        'last_notification_at',
        'notification_count',
    ];

    protected $casts = [
        'reference_list' => 'array',
        'notification_retry' => 'boolean',
        'persist' => 'boolean',
        'expires_at' => 'datetime',
        'last_notification_at' => 'datetime',
        'notification_count' => 'integer',
    ];

    /**
     * Relationship to CPE Device
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(CpeDevice::class, 'cpe_device_id');
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', Carbon::now());
                    });
    }

    /**
     * Scope for expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Scope for cancelled subscriptions
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope by device
     */
    public function scopeForDevice($query, $deviceId)
    {
        return $query->where('cpe_device_id', $deviceId);
    }

    /**
     * Mark subscription as cancelled
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Mark subscription as expired
     */
    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Record a notification received
     */
    public function recordNotification(): void
    {
        $this->increment('notification_count');
        $this->update(['last_notification_at' => Carbon::now()]);
    }

    /**
     * Check if subscription is still valid
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
