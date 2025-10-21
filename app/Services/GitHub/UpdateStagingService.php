<?php

namespace App\Services\GitHub;

use App\Models\SystemVersion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UpdateStagingService
{
    private GitHubReleaseService $githubService;

    public function __construct(GitHubReleaseService $githubService)
    {
        $this->githubService = $githubService;
    }

    public function stageUpdate(array $releaseData, string $environment = 'production'): ?SystemVersion
    {
        try {
            $version = ltrim($releaseData['tag_name'], 'v');

            $existing = SystemVersion::where('version', $version)
                ->where('environment', $environment)
                ->first();

            if ($existing) {
                Log::info('Version already staged', [
                    'version' => $version,
                    'status' => $existing->approval_status,
                ]);
                return $existing;
            }

            $downloadResult = $this->githubService->downloadRelease($releaseData);

            if (!$downloadResult) {
                Log::error('Failed to download release', ['version' => $version]);
                return null;
            }

            $extractPath = Storage::path("updates/{$version}/extracted");
            
            if ($this->githubService->extractPackage($downloadResult['path'], $extractPath)) {
                Log::info('Package extracted successfully', [
                    'version' => $version,
                    'extract_path' => $extractPath,
                ]);
            } else {
                Log::warning('Failed to extract package, continuing with staging', [
                    'version' => $version,
                ]);
            }

            $stagedVersion = SystemVersion::create([
                'version' => $version,
                'environment' => $environment,
                'deployment_status' => 'pending',
                'approval_status' => 'pending',
                'github_release_url' => $downloadResult['release_url'],
                'github_release_tag' => $downloadResult['release_tag'],
                'download_path' => $downloadResult['storage_path'],
                'package_checksum' => $downloadResult['checksum'],
                'changelog' => $downloadResult['changelog'],
                'release_notes' => $releaseData['name'] ?? "Release {$version}",
                'deployed_by' => 'github-release-checker',
            ]);

            Log::info('Update staged successfully', [
                'id' => $stagedVersion->id,
                'version' => $version,
                'environment' => $environment,
            ]);

            return $stagedVersion;
        } catch (\Exception $e) {
            Log::error('Exception staging update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    public function validateStagedUpdate(SystemVersion $version): array
    {
        $checks = [];

        if (!$version->download_path) {
            $checks['package_downloaded'] = [
                'status' => 'failed',
                'message' => 'No package download path',
            ];
            return $checks;
        }

        $fullPath = Storage::path($version->download_path);
        
        if (!file_exists($fullPath)) {
            $checks['package_exists'] = [
                'status' => 'failed',
                'message' => 'Package file not found',
                'path' => $fullPath,
            ];
        } else {
            $checks['package_exists'] = [
                'status' => 'ok',
                'message' => 'Package file exists',
                'size' => filesize($fullPath),
            ];
        }

        if ($version->package_checksum && file_exists($fullPath)) {
            $isValid = $this->githubService->validateChecksum($fullPath, $version->package_checksum);
            $checks['checksum_validation'] = [
                'status' => $isValid ? 'ok' : 'failed',
                'message' => $isValid ? 'Checksum valid' : 'Checksum mismatch',
                'expected' => $version->package_checksum,
                'actual' => hash_file('sha256', $fullPath),
            ];
        }

        $extractPath = Storage::path("updates/{$version->version}/extracted");
        if (is_dir($extractPath)) {
            $files = $this->scanDirectory($extractPath);
            $checks['extracted_files'] = [
                'status' => 'ok',
                'message' => 'Package extracted',
                'file_count' => count($files),
                'path' => $extractPath,
            ];
        } else {
            $checks['extracted_files'] = [
                'status' => 'warning',
                'message' => 'Package not extracted yet',
            ];
        }

        $diskSpace = disk_free_space(Storage::path(''));
        $requiredSpace = file_exists($fullPath) ? filesize($fullPath) * 3 : 100 * 1024 * 1024;
        
        $checks['disk_space'] = [
            'status' => $diskSpace > $requiredSpace ? 'ok' : 'failed',
            'message' => $diskSpace > $requiredSpace ? 'Sufficient disk space' : 'Insufficient disk space',
            'available' => $this->formatBytes($diskSpace),
            'required' => $this->formatBytes($requiredSpace),
        ];

        return $checks;
    }

    public function cleanupStagedUpdate(SystemVersion $version): bool
    {
        try {
            if ($version->download_path && Storage::exists($version->download_path)) {
                Storage::delete($version->download_path);
            }

            $updateDir = "updates/{$version->version}";
            if (Storage::exists($updateDir)) {
                Storage::deleteDirectory($updateDir);
            }

            Log::info('Staged update cleaned up', ['version' => $version->version]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup staged update', [
                'version' => $version->version,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function scanDirectory(string $path, int $maxDepth = 3, int $currentDepth = 0): array
    {
        if ($currentDepth >= $maxDepth || !is_dir($path)) {
            return [];
        }

        $files = [];
        $items = scandir($path);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;
            
            if (is_file($fullPath)) {
                $files[] = $fullPath;
            } elseif (is_dir($fullPath)) {
                $files = array_merge($files, $this->scanDirectory($fullPath, $maxDepth, $currentDepth + 1));
            }
        }

        return $files;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
