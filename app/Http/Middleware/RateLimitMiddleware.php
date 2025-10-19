<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\SecurityLog;
use App\Models\IpBlacklist;

class RateLimitMiddleware
{
    protected $limits = [
        'api' => [
            'requests' => 60,
            'decay' => 1,
        ],
        'tr069' => [
            'requests' => 300,
            'decay' => 1,
        ],
        'login' => [
            'requests' => 5,
            'decay' => 15,
        ],
    ];

    protected $maxViolations = 3;
    protected $banDuration = 60;

    public function handle(Request $request, Closure $next, string $limiter = 'api'): Response
    {
        $ip = $request->ip();

        if (IpBlacklist::isBlocked($ip)) {
            SecurityLog::logEvent('blocked_ip_attempt', [
                'severity' => 'warning',
                'ip_address' => $ip,
                'action' => 'access_denied_blacklisted',
                'description' => 'Blocked IP attempted to access the system',
                'endpoint' => $request->path(),
                'risk_level' => 'high',
                'blocked' => true,
            ]);

            return response()->json([
                'error' => 'Access denied',
                'message' => 'Your IP address has been blocked due to suspicious activity.'
            ], 403);
        }

        $limits = $this->limits[$limiter] ?? $this->limits['api'];
        $key = $this->resolveRequestSignature($request, $limiter);

        if (RateLimiter::tooManyAttempts($key, $limits['requests'])) {
            $this->handleRateLimitExceeded($request, $limiter);

            return response()->json([
                'error' => 'Too many requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        RateLimiter::hit($key, $limits['decay'] * 60);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $limits['requests']);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $limits['requests']));

        return $response;
    }

    protected function resolveRequestSignature(Request $request, string $limiter): string
    {
        return sha1($limiter . '|' . $request->ip());
    }

    protected function handleRateLimitExceeded(Request $request, string $limiter): void
    {
        $ip = $request->ip();
        $violationKey = "rate_limit_violations:{$ip}";
        
        $violations = Cache::get($violationKey, 0) + 1;
        Cache::put($violationKey, $violations, now()->addMinutes(60));

        SecurityLog::logRateLimitViolation($ip, $request->path());

        if ($violations >= $this->maxViolations) {
            IpBlacklist::blockIp(
                $ip,
                "Exceeded rate limit {$violations} times",
                $this->banDuration,
                [
                    'limiter' => $limiter,
                    'endpoint' => $request->path(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            SecurityLog::logEvent('ip_auto_blocked', [
                'severity' => 'critical',
                'ip_address' => $ip,
                'action' => 'auto_blocked_rate_limit',
                'description' => "IP automatically blocked after {$violations} rate limit violations",
                'risk_level' => 'high',
                'blocked' => true,
                'metadata' => [
                    'violations' => $violations,
                    'limiter' => $limiter,
                ],
            ]);

            Cache::forget($violationKey);
        }
    }
}
