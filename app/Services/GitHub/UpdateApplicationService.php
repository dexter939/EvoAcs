<?php

namespace App\Services\GitHub;

use App\Models\SystemVersion;
use App\Services\SystemUpdateService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UpdateApplicationService
{
    private SystemUpdateService $systemUpdateService;

    public function __construct(SystemUpdateService $systemUpdateService)
    {
        $this->systemUpdateService = $systemUpdateService;
    }

    public function applyUpdate(SystemVersion $version): array
    {
        if ($version->deployment_status !== 'pending') {
            return [
                'success' => false,
                'error' => 'Update already applied or in progress',
                'status' => $version->deployment_status,
            ];
        }

        if ($version->approval_status !== 'approved') {
            return [
                'success' => false,
                'error' => 'Update not approved',
                'approval_status' => $version->approval_status,
            ];
        }

        $version->update(['deployment_status' => 'deploying', 'deployed_at' => now()]);

        try {
            Log::info('Starting update application', ['version' => $version->version]);

            $backupPath = $this->createBackup($version);
            if (!$backupPath) {
                throw new \Exception('Failed to create backup');
            }

            Log::info('Backup created', ['path' => $backupPath]);

            $extractedPath = Storage::path("updates/{$version->version}/extracted");
            if (!is_dir($extractedPath)) {
                throw new \Exception('Extracted package not found');
            }

            $this->copyFiles($extractedPath, base_path());

            Log::info('Files copied successfully');

            $migrationsRun = $this->runMigrations();
            $version->recordMigrations($migrationsRun);

            Log::info('Migrations completed', ['count' => count($migrationsRun)]);

            $this->clearCaches();

            $healthCheckResults = $this->systemUpdateService->runHealthChecks();
            
            $allHealthy = true;
            foreach ($healthCheckResults as $check) {
                if ($check['status'] !== 'ok') {
                    $allHealthy = false;
                    break;
                }
            }

            if (!$allHealthy) {
                Log::warning('Health checks failed after update', $healthCheckResults);
                $this->rollback($backupPath, base_path());
                
                $version->markAsFailed('Health checks failed after update: ' . json_encode($healthCheckResults));
                
                return [
                    'success' => false,
                    'error' => 'Health checks failed - update rolled back',
                    'health_checks' => $healthCheckResults,
                ];
            }

            $currentOld = SystemVersion::where('is_current', true)
                ->where('environment', $version->environment)
                ->where('id', '!=', $version->id)
                ->first();
            
            if ($currentOld) {
                $currentOld->update(['is_current' => false]);
            }

            $version->update([
                'is_current' => true,
                'deployment_status' => 'success',
                'completed_at' => now(),
                'duration_seconds' => $version->deployed_at ? (int) now()->diffInSeconds($version->deployed_at) : null,
                'health_check_results' => $healthCheckResults,
            ]);

            Log::info('Update applied successfully', ['version' => $version->version]);

            return [
                'success' => true,
                'version' => $version->version,
                'migrations_run' => count($migrationsRun),
                'health_checks' => $healthCheckResults,
                'backup_path' => $backupPath,
                'duration' => $version->duration_formatted,
            ];
        } catch (\Exception $e) {
            Log::error('Update application failed', [
                'version' => $version->version,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($backupPath)) {
                $this->rollback($backupPath, base_path());
            }

            $version->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function createBackup(SystemVersion $version): ?string
    {
        try {
            $backupDir = Storage::path("backups/{$version->version}-" . time());
            
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $criticalPaths = [
                'app',
                'config',
                'database/migrations',
                'routes',
                'resources',
                'public',
            ];

            foreach ($criticalPaths as $path) {
                $source = base_path($path);
                $destination = $backupDir . '/' . $path;
                
                if (is_dir($source)) {
                    $this->copyDirectory($source, $destination);
                } elseif (is_file($source)) {
                    $destDir = dirname($destination);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    copy($source, $destination);
                }
            }

            file_put_contents($backupDir . '/backup-info.json', json_encode([
                'version' => $version->version,
                'created_at' => now()->toIso8601String(),
                'previous_version' => $version->rollback_version,
            ], JSON_PRETTY_PRINT));

            return $backupDir;
        } catch (\Exception $e) {
            Log::error('Backup creation failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function copyFiles(string $source, string $destination): void
    {
        $excludePaths = [
            'storage',
            'vendor',
            'node_modules',
            '.git',
            '.env',
            'bootstrap/cache',
        ];

        $this->copyDirectory($source, $destination, $excludePaths);
    }

    private function copyDirectory(string $source, string $destination, array $excludePaths = []): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($source) + 1);
            
            $shouldExclude = false;
            foreach ($excludePaths as $exclude) {
                if (str_starts_with($relativePath, $exclude)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    private function rollback(string $backupPath, string $destination): void
    {
        try {
            Log::info('Starting rollback', ['backup' => $backupPath]);
            
            $this->copyDirectory($backupPath, $destination);
            
            Artisan::call('migrate:rollback', ['--force' => true]);
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            Log::info('Rollback completed');
        } catch (\Exception $e) {
            Log::error('Rollback failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function runMigrations(): array
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            $migrations = [];
            $lines = explode("\n", $output);
            
            foreach ($lines as $line) {
                if (preg_match('/(\d{4}_\d{2}_\d{2}_\d{6}_\S+)/', $line, $matches)) {
                    $migrations[] = trim($matches[1]);
                }
            }

            return $migrations;
        } catch (\Exception $e) {
            Log::error('Migration failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function clearCaches(): void
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
    }
}
