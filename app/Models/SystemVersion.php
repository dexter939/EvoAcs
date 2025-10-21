<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

class SystemVersion extends Model
{
    protected $fillable = [
        'version',
        'deployment_status',
        'environment',
        'deployed_at',
        'completed_at',
        'migrations_run',
        'health_check_results',
        'deployment_notes',
        'error_log',
        'deployed_by',
        'rollback_version',
        'duration_seconds',
        'is_current',
    ];

    protected $casts = [
        'deployed_at' => 'datetime',
        'completed_at' => 'datetime',
        'migrations_run' => 'array',
        'health_check_results' => 'array',
        'is_current' => 'boolean',
    ];

    public static function getCurrentVersion(?string $environment = null): ?self
    {
        $environment = $environment ?? config('app.env', 'production');
        
        return self::where('is_current', true)
            ->where('environment', $environment)
            ->latest('deployed_at')
            ->first();
    }

    public static function recordDeployment(
        string $version,
        string $environment = 'production',
        ?string $deployedBy = null
    ): self {
        return DB::transaction(function () use ($version, $environment, $deployedBy) {
            $previous = self::where('is_current', true)
                ->where('environment', $environment)
                ->first();

            if ($previous) {
                $previous->update(['is_current' => false]);
            }

            return self::create([
                'version' => $version,
                'environment' => $environment,
                'deployment_status' => 'deploying',
                'deployed_at' => now(),
                'deployed_by' => $deployedBy ?? 'system',
                'is_current' => true,
                'rollback_version' => $previous?->version,
            ]);
        });
    }

    public function markAsSuccess(array $healthCheckResults = []): self
    {
        $this->update([
            'deployment_status' => 'success',
            'completed_at' => now(),
            'duration_seconds' => $this->deployed_at ? (int) now()->diffInSeconds($this->deployed_at) : null,
            'health_check_results' => $healthCheckResults,
        ]);

        return $this;
    }

    public function markAsFailed(string $error): self
    {
        return DB::transaction(function () use ($error) {
            $this->update([
                'deployment_status' => 'failed',
                'completed_at' => now(),
                'error_log' => $error,
                'is_current' => false,
            ]);

            if ($this->rollback_version) {
                self::where('version', $this->rollback_version)
                    ->where('environment', $this->environment)
                    ->where('deployment_status', 'success')
                    ->update(['is_current' => true]);
            }

            return $this;
        });
    }

    public function recordMigrations(array $migrations): self
    {
        $this->update([
            'migrations_run' => $migrations,
        ]);

        return $this;
    }

    protected function isHealthy(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->health_check_results) {
                    return null;
                }

                foreach ($this->health_check_results as $check) {
                    if (isset($check['status']) && $check['status'] !== 'ok') {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    protected function durationFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->duration_seconds) {
                    return null;
                }

                $minutes = floor($this->duration_seconds / 60);
                $seconds = $this->duration_seconds % 60;

                return $minutes > 0
                    ? "{$minutes}m {$seconds}s"
                    : "{$seconds}s";
            }
        );
    }

    public function scopeSuccessful($query)
    {
        return $query->where('deployment_status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('deployment_status', 'failed');
    }

    public function scopeForEnvironment($query, string $environment)
    {
        return $query->where('environment', $environment);
    }
}
