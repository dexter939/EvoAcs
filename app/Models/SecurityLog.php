<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'severity',
        'ip_address',
        'user_agent',
        'user_id',
        'action',
        'description',
        'metadata',
        'endpoint',
        'method',
        'status_code',
        'risk_level',
        'blocked',
    ];

    protected $casts = [
        'metadata' => 'array',
        'blocked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function logEvent(string $eventType, array $data = []): self
    {
        return self::create([
            'event_type' => $eventType,
            'severity' => $data['severity'] ?? 'info',
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'user_id' => $data['user_id'] ?? auth()->id(),
            'action' => $data['action'] ?? '',
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'endpoint' => $data['endpoint'] ?? request()->path(),
            'method' => $data['method'] ?? request()->method(),
            'status_code' => $data['status_code'] ?? null,
            'risk_level' => $data['risk_level'] ?? 'low',
            'blocked' => $data['blocked'] ?? false,
        ]);
    }

    public static function logRateLimitViolation(string $ip, string $endpoint): self
    {
        return self::logEvent('rate_limit_violation', [
            'severity' => 'warning',
            'ip_address' => $ip,
            'action' => 'rate_limit_exceeded',
            'description' => "Rate limit exceeded for endpoint: {$endpoint}",
            'risk_level' => 'medium',
            'blocked' => true,
        ]);
    }

    public static function logSuspiciousActivity(string $ip, string $reason, array $metadata = []): self
    {
        return self::logEvent('suspicious_activity', [
            'severity' => 'critical',
            'ip_address' => $ip,
            'action' => 'suspicious_behavior_detected',
            'description' => $reason,
            'metadata' => $metadata,
            'risk_level' => 'high',
            'blocked' => false,
        ]);
    }

    public static function logUnauthorizedAccess(string $endpoint, string $reason = ''): self
    {
        return self::logEvent('unauthorized_access', [
            'severity' => 'warning',
            'action' => 'access_denied',
            'description' => $reason ?: "Unauthorized access attempt to: {$endpoint}",
            'endpoint' => $endpoint,
            'risk_level' => 'high',
            'blocked' => true,
        ]);
    }
}
