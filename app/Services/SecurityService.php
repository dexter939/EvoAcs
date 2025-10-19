<?php

namespace App\Services;

use App\Models\SecurityLog;
use App\Models\IpBlacklist;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SecurityService
{
    public function getSecurityDashboardStats(): array
    {
        $last24Hours = now()->subHours(24);
        $last7Days = now()->subDays(7);

        return [
            'total_events_24h' => SecurityLog::where('created_at', '>=', $last24Hours)->count(),
            'critical_events_24h' => SecurityLog::where('created_at', '>=', $last24Hours)
                ->where('severity', 'critical')
                ->count(),
            'blocked_attempts_24h' => SecurityLog::where('created_at', '>=', $last24Hours)
                ->where('blocked', true)
                ->count(),
            'active_blacklisted_ips' => IpBlacklist::where('is_active', true)->count(),
            'rate_limit_violations_24h' => SecurityLog::where('created_at', '>=', $last24Hours)
                ->where('event_type', 'rate_limit_violation')
                ->count(),
            'unauthorized_access_24h' => SecurityLog::where('created_at', '>=', $last24Hours)
                ->where('event_type', 'unauthorized_access')
                ->count(),
            'suspicious_activity_24h' => SecurityLog::where('created_at', '>=', $last24Hours)
                ->where('event_type', 'suspicious_activity')
                ->count(),
            'auto_blocked_ips_24h' => IpBlacklist::where('created_at', '>=', $last24Hours)->count(),
        ];
    }

    public function getSecurityEventsTrend(int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        $events = SecurityLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN severity = "critical" THEN 1 ELSE 0 END) as critical'),
                DB::raw('SUM(CASE WHEN severity = "warning" THEN 1 ELSE 0 END) as warning'),
                DB::raw('SUM(CASE WHEN blocked = true THEN 1 ELSE 0 END) as blocked')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $events->pluck('date')->toArray(),
            'total' => $events->pluck('total')->toArray(),
            'critical' => $events->pluck('critical')->toArray(),
            'warning' => $events->pluck('warning')->toArray(),
            'blocked' => $events->pluck('blocked')->toArray(),
        ];
    }

    public function getTopThreats(int $limit = 10): array
    {
        return IpBlacklist::select('ip_address', 'violation_count', 'reason', 'last_violation_at')
            ->where('is_active', true)
            ->orderByDesc('violation_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getRecentSecurityEvents(int $limit = 20): array
    {
        return SecurityLog::with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'event_type' => $log->event_type,
                    'severity' => $log->severity,
                    'ip_address' => $log->ip_address,
                    'action' => $log->action,
                    'description' => $log->description,
                    'endpoint' => $log->endpoint,
                    'risk_level' => $log->risk_level,
                    'blocked' => $log->blocked,
                    'user' => $log->user ? $log->user->name : 'Guest',
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    public function getEventsByType(): array
    {
        $last24Hours = now()->subHours(24);
        
        return SecurityLog::select('event_type', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $last24Hours)
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->get()
            ->pluck('count', 'event_type')
            ->toArray();
    }

    public function getRiskLevelDistribution(): array
    {
        $last24Hours = now()->subHours(24);
        
        return [
            'critical' => SecurityLog::where('created_at', '>=', $last24Hours)
                ->where('risk_level', 'high')
                ->count(),
            'warning' => SecurityLog::where('created_at', '>=', $last24Hours)
                ->where('risk_level', 'medium')
                ->count(),
            'info' => SecurityLog::where('created_at', '>=', $last24Hours)
                ->where('risk_level', 'low')
                ->count(),
        ];
    }

    public function blockIpAddress(string $ip, string $reason, ?int $durationMinutes = null): bool
    {
        try {
            IpBlacklist::blockIp($ip, $reason, $durationMinutes);
            
            SecurityLog::logEvent('manual_ip_block', [
                'severity' => 'info',
                'ip_address' => $ip,
                'action' => 'ip_manually_blocked',
                'description' => "IP manually blocked: {$reason}",
                'risk_level' => 'medium',
                'metadata' => [
                    'duration_minutes' => $durationMinutes,
                    'blocked_by' => auth()->user()?->name,
                ],
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function unblockIpAddress(string $ip): bool
    {
        try {
            $result = IpBlacklist::unblockIp($ip);
            
            if ($result) {
                SecurityLog::logEvent('manual_ip_unblock', [
                    'severity' => 'info',
                    'ip_address' => $ip,
                    'action' => 'ip_manually_unblocked',
                    'description' => "IP manually unblocked",
                    'risk_level' => 'low',
                    'metadata' => [
                        'unblocked_by' => auth()->user()?->name,
                    ],
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function cleanupExpiredBans(): int
    {
        return IpBlacklist::cleanupExpired();
    }

    public function analyzeSecurityHealth(): array
    {
        $stats = $this->getSecurityDashboardStats();
        
        $healthScore = 100;
        $issues = [];

        if ($stats['critical_events_24h'] > 10) {
            $healthScore -= 30;
            $issues[] = 'High number of critical security events';
        }

        if ($stats['rate_limit_violations_24h'] > 50) {
            $healthScore -= 20;
            $issues[] = 'Excessive rate limit violations detected';
        }

        if ($stats['unauthorized_access_24h'] > 20) {
            $healthScore -= 25;
            $issues[] = 'Multiple unauthorized access attempts';
        }

        if ($stats['suspicious_activity_24h'] > 5) {
            $healthScore -= 15;
            $issues[] = 'Suspicious activity patterns detected';
        }

        $status = 'excellent';
        if ($healthScore < 90) $status = 'good';
        if ($healthScore < 70) $status = 'fair';
        if ($healthScore < 50) $status = 'poor';
        if ($healthScore < 30) $status = 'critical';

        return [
            'health_score' => max(0, $healthScore),
            'status' => $status,
            'issues' => $issues,
            'recommendation' => $this->getSecurityRecommendation($healthScore, $issues),
        ];
    }

    protected function getSecurityRecommendation(int $score, array $issues): string
    {
        if ($score >= 90) {
            return 'Security status is excellent. Continue monitoring.';
        }

        if ($score >= 70) {
            return 'Security is good but requires attention to: ' . implode(', ', $issues);
        }

        if ($score >= 50) {
            return 'Security needs improvement. Immediate actions required for: ' . implode(', ', $issues);
        }

        return 'CRITICAL: Security is compromised. Immediate intervention required!';
    }
}
