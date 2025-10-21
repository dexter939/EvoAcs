<?php

namespace App\Services;

use App\Models\SystemVersion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SystemUpdateService
{
    private const APP_VERSION = '1.0.0';

    public function getCurrentVersion(?string $environment = null): string
    {
        $versionRecord = SystemVersion::getCurrentVersion($environment);
        return $versionRecord ? $versionRecord->version : 'unknown';
    }

    public function checkForUpdate(?string $environment = null): bool
    {
        $current = $this->getCurrentVersion($environment);
        return $current !== self::APP_VERSION;
    }

    public function performAutoUpdate(string $environment = null): array
    {
        $environment = $environment ?? config('app.env', 'production');

        $current = SystemVersion::getCurrentVersion($environment);
        if ($current && $current->version === self::APP_VERSION && $current->deployment_status === 'success') {
            Log::info("System already at version " . self::APP_VERSION . " - skipping duplicate deployment");
            
            return [
                'success' => true,
                'version' => self::APP_VERSION,
                'migrations_run' => 0,
                'health_status' => 'healthy',
                'duration' => '0s',
                'skipped' => true,
            ];
        }

        Log::info("Starting auto-update to version " . self::APP_VERSION, [
            'environment' => $environment,
            'previous_version' => $current?->version ?? 'none',
        ]);

        $deployment = SystemVersion::recordDeployment(
            self::APP_VERSION,
            $environment,
            'auto-update-system'
        );

        try {
            $migrationsRun = $this->runMigrations();
            $deployment->recordMigrations($migrationsRun);

            $healthChecks = $this->runHealthChecks();
            $deployment->markAsSuccess($healthChecks);

            $this->clearCaches();

            Log::info("Auto-update completed successfully", [
                'version' => self::APP_VERSION,
                'migrations_count' => count($migrationsRun),
            ]);

            return [
                'success' => true,
                'version' => self::APP_VERSION,
                'migrations_run' => count($migrationsRun),
                'health_status' => $deployment->is_healthy ? 'healthy' : 'degraded',
                'duration' => $deployment->duration_formatted,
            ];

        } catch (\Exception $e) {
            $deployment->markAsFailed($e->getMessage());

            Log::error("Auto-update failed", [
                'version' => self::APP_VERSION,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'version' => self::APP_VERSION,
            ];
        }
    }

    private function runMigrations(): array
    {
        $migrationsRun = [];

        try {
            $pendingMigrations = $this->getPendingMigrations();

            if (empty($pendingMigrations)) {
                Log::info("No pending migrations to run");
                return [];
            }

            Log::info("Running migrations", ['count' => count($pendingMigrations)]);

            Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]);

            $output = Artisan::output();
            $migrationsRun = $this->parseMigrationOutput($output);

            Log::info("Migrations completed", [
                'migrations' => $migrationsRun,
            ]);

            return $migrationsRun;

        } catch (\Exception $e) {
            Log::error("Migration failed", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function getPendingMigrations(): array
    {
        try {
            $ran = DB::table('migrations')->pluck('migration')->toArray();
            $allMigrations = $this->getAllMigrationFiles();

            return array_diff($allMigrations, $ran);
        } catch (\Exception $e) {
            Log::warning("Could not determine pending migrations", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function getAllMigrationFiles(): array
    {
        $migrations = [];
        $files = glob(database_path('migrations/*.php'));

        foreach ($files as $file) {
            $filename = basename($file, '.php');
            $migrations[] = $filename;
        }

        return $migrations;
    }

    private function parseMigrationOutput(string $output): array
    {
        $migrations = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (preg_match('/Migrating:\s+(.+)/', $line, $matches)) {
                $migrations[] = [
                    'migration' => trim($matches[1]),
                    'status' => 'migrated',
                ];
            }
        }

        return $migrations;
    }

    private function runHealthChecks(): array
    {
        $checks = [];

        $checks['database'] = $this->checkDatabase();
        $checks['cache'] = $this->checkCache();
        $checks['storage'] = $this->checkStorage();
        $checks['queue'] = $this->checkQueue();
        $checks['critical_tables'] = $this->checkCriticalTables();

        return $checks;
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $tableCount = count(DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'"));

            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
                'tables_count' => $tableCount,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            cache()->put('health_check', true, 60);
            $value = cache()->get('health_check');

            return [
                'status' => $value === true ? 'ok' : 'warning',
                'message' => 'Cache operational',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache error: ' . $e->getMessage(),
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $path = storage_path('logs');
            $writable = is_writable($path);

            return [
                'status' => $writable ? 'ok' : 'warning',
                'message' => $writable ? 'Storage writable' : 'Storage not writable',
                'path' => $path,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            $pendingJobs = DB::table('jobs')->count();

            return [
                'status' => $failedJobs < 50 ? 'ok' : 'warning',
                'message' => 'Queue operational',
                'failed_jobs' => $failedJobs,
                'pending_jobs' => $pendingJobs,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Queue check skipped: ' . $e->getMessage(),
            ];
        }
    }

    private function checkCriticalTables(): array
    {
        $criticalTables = [
            'users',
            'cpe_devices',
            'configuration_templates',
            'system_versions',
        ];

        $missing = [];
        foreach ($criticalTables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $missing[] = $table;
            }
        }

        return [
            'status' => empty($missing) ? 'ok' : 'error',
            'message' => empty($missing) ? 'All critical tables present' : 'Missing tables',
            'missing_tables' => $missing,
        ];
    }

    private function clearCaches(): void
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            Log::info("Caches cleared and rebuilt");
        } catch (\Exception $e) {
            Log::warning("Cache rebuild failed", ['error' => $e->getMessage()]);
        }
    }

    public function getUpdateHistory(int $limit = 10): array
    {
        return SystemVersion::latest('deployed_at')
            ->limit($limit)
            ->get()
            ->map(function ($version) {
                return [
                    'version' => $version->version,
                    'status' => $version->deployment_status,
                    'deployed_at' => $version->deployed_at?->format('Y-m-d H:i:s'),
                    'duration' => $version->duration_formatted,
                    'is_healthy' => $version->is_healthy,
                    'migrations_count' => count($version->migrations_run ?? []),
                ];
            })
            ->toArray();
    }

    public function getSystemStatus(?string $environment = null): array
    {
        $environment = $environment ?? config('app.env', 'production');
        $current = SystemVersion::getCurrentVersion($environment);
        $lastDeployment = SystemVersion::forEnvironment($environment)->latest('deployed_at')->first();

        return [
            'current_version' => $this->getCurrentVersion($environment),
            'app_version' => self::APP_VERSION,
            'update_available' => $this->checkForUpdate($environment),
            'last_deployment' => $lastDeployment ? [
                'version' => $lastDeployment->version,
                'status' => $lastDeployment->deployment_status,
                'deployed_at' => $lastDeployment->deployed_at?->format('Y-m-d H:i:s'),
                'is_healthy' => $lastDeployment->is_healthy,
            ] : null,
            'environment' => $environment,
            'deployment_count' => SystemVersion::forEnvironment($environment)->count(),
        ];
    }
}
