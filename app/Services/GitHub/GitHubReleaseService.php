<?php

namespace App\Services\GitHub;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GitHubReleaseService
{
    private string $owner;
    private string $repo;
    private ?string $token = null;

    public function __construct(?string $owner = null, ?string $repo = null)
    {
        $this->owner = $owner ?? config('services.github.owner');
        $this->repo = $repo ?? config('services.github.repo');
        $this->token = $this->getGitHubToken();
    }

    private function getGitHubToken(): ?string
    {
        $hostname = env('REPLIT_CONNECTORS_HOSTNAME');
        $xReplitToken = env('REPL_IDENTITY') 
            ? 'repl ' . env('REPL_IDENTITY') 
            : (env('WEB_REPL_RENEWAL') 
                ? 'depl ' . env('WEB_REPL_RENEWAL') 
                : null);

        if (!$xReplitToken || !$hostname) {
            Log::warning('GitHub connection not available - missing REPL_IDENTITY or CONNECTORS_HOSTNAME');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X_REPLIT_TOKEN' => $xReplitToken,
            ])->get("https://{$hostname}/api/v2/connection", [
                'include_secrets' => 'true',
                'connector_names' => 'github',
            ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch GitHub connection', [
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();
            $connection = $data['items'][0] ?? null;
            
            return $connection['settings']['access_token'] 
                ?? $connection['settings']['oauth']['credentials']['access_token'] 
                ?? null;
        } catch (\Exception $e) {
            Log::error('Exception fetching GitHub token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getLatestRelease(): ?array
    {
        try {
            $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/releases/latest";
            
            $headers = [
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'ACS-Auto-Update-System',
            ];

            if ($this->token) {
                $headers['Authorization'] = "Bearer {$this->token}";
            }

            $response = Http::withHeaders($headers)->get($url);

            if (!$response->successful()) {
                Log::error('Failed to fetch GitHub release', [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Exception fetching GitHub release', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function downloadRelease(array $release, string $assetName = null): ?array
    {
        $assets = $release['assets'] ?? [];
        
        if (empty($assets)) {
            Log::warning('No assets found in release', ['release' => $release['tag_name']]);
            return null;
        }

        $asset = null;
        if ($assetName) {
            foreach ($assets as $a) {
                if ($a['name'] === $assetName) {
                    $asset = $a;
                    break;
                }
            }
        } else {
            foreach ($assets as $a) {
                if (str_ends_with($a['name'], '.tar.gz') || str_ends_with($a['name'], '.zip')) {
                    $asset = $a;
                    break;
                }
            }
        }

        if (!$asset) {
            Log::warning('No suitable asset found in release', [
                'release' => $release['tag_name'],
                'assets' => array_column($assets, 'name'),
            ]);
            return null;
        }

        try {
            $downloadUrl = $asset['browser_download_url'];
            $version = ltrim($release['tag_name'], 'v');
            $storagePath = "updates/{$version}";
            $filename = $asset['name'];
            $fullPath = "{$storagePath}/{$filename}";

            Storage::makeDirectory($storagePath);

            $headers = [
                'User-Agent' => 'ACS-Auto-Update-System',
            ];

            if ($this->token) {
                $headers['Authorization'] = "Bearer {$this->token}";
            }

            $response = Http::withHeaders($headers)
                ->timeout(300)
                ->get($downloadUrl);

            if (!$response->successful()) {
                Log::error('Failed to download release asset', [
                    'url' => $downloadUrl,
                    'status' => $response->status(),
                ]);
                return null;
            }

            Storage::put($fullPath, $response->body());

            $checksum = hash('sha256', $response->body());

            Log::info('Release asset downloaded successfully', [
                'version' => $version,
                'file' => $filename,
                'size' => strlen($response->body()),
                'checksum' => $checksum,
            ]);

            return [
                'version' => $version,
                'path' => Storage::path($fullPath),
                'storage_path' => $fullPath,
                'filename' => $filename,
                'checksum' => $checksum,
                'size' => strlen($response->body()),
                'release_url' => $release['html_url'],
                'release_tag' => $release['tag_name'],
                'changelog' => $release['body'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('Exception downloading release', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function validateChecksum(string $filePath, string $expectedChecksum): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $actualChecksum = hash_file('sha256', $filePath);
        return hash_equals($expectedChecksum, $actualChecksum);
    }

    public function extractPackage(string $packagePath, string $extractTo): bool
    {
        try {
            if (!file_exists($packagePath)) {
                Log::error('Package file not found', ['path' => $packagePath]);
                return false;
            }

            if (!is_dir($extractTo)) {
                mkdir($extractTo, 0755, true);
            }

            if (str_ends_with($packagePath, '.tar.gz')) {
                $phar = new \PharData($packagePath);
                $phar->extractTo($extractTo, null, true);
                return true;
            } elseif (str_ends_with($packagePath, '.zip')) {
                $zip = new \ZipArchive();
                if ($zip->open($packagePath) === true) {
                    $zip->extractAll($extractTo);
                    $zip->close();
                    return true;
                }
            }

            Log::error('Unsupported package format', ['path' => $packagePath]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception extracting package', [
                'path' => $packagePath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function compareVersions(string $current, string $latest): int
    {
        $current = ltrim($current, 'v');
        $latest = ltrim($latest, 'v');

        return version_compare($latest, $current);
    }
}
