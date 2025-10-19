<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class IpBlacklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'reason',
        'description',
        'violation_count',
        'first_violation_at',
        'last_violation_at',
        'blocked_at',
        'expires_at',
        'is_permanent',
        'is_active',
        'blocked_by',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_permanent' => 'boolean',
        'is_active' => 'boolean',
        'first_violation_at' => 'datetime',
        'last_violation_at' => 'datetime',
        'blocked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public static function isBlocked(string $ip): bool
    {
        return self::where('ip_address', $ip)
            ->where('is_active', true)
            ->where(function($query) {
                $query->where('is_permanent', true)
                    ->orWhere('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->exists();
    }

    public static function blockIp(string $ip, string $reason, int $durationMinutes = null, array $metadata = []): self
    {
        $existing = self::where('ip_address', $ip)->first();

        if ($existing) {
            $existing->update([
                'violation_count' => $existing->violation_count + 1,
                'last_violation_at' => now(),
                'reason' => $reason,
                'is_active' => true,
                'expires_at' => $durationMinutes ? now()->addMinutes($durationMinutes) : null,
                'metadata' => array_merge($existing->metadata ?? [], $metadata),
            ]);
            return $existing;
        }

        return self::create([
            'ip_address' => $ip,
            'reason' => $reason,
            'violation_count' => 1,
            'first_violation_at' => now(),
            'last_violation_at' => now(),
            'blocked_at' => now(),
            'expires_at' => $durationMinutes ? now()->addMinutes($durationMinutes) : null,
            'is_permanent' => $durationMinutes === null,
            'is_active' => true,
            'blocked_by' => auth()->id(),
            'metadata' => $metadata,
        ]);
    }

    public static function unblockIp(string $ip): bool
    {
        return self::where('ip_address', $ip)
            ->update(['is_active' => false]);
    }

    public static function cleanupExpired(): int
    {
        return self::where('is_active', true)
            ->where('is_permanent', false)
            ->where('expires_at', '<=', now())
            ->update(['is_active' => false]);
    }
}
