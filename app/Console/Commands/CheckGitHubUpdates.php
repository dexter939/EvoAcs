<?php

namespace App\Console\Commands;

use App\Models\SystemVersion;
use App\Services\GitHub\GitHubReleaseService;
use App\Services\GitHub\UpdateStagingService;
use Illuminate\Console\Command;

class CheckGitHubUpdates extends Command
{
    protected $signature = 'system:check-updates 
                            {--environment=production : Environment to check updates for}
                            {--auto-stage : Automatically stage new updates}';

    protected $description = 'Check GitHub for new ACS releases and notify administrators';

    private GitHubReleaseService $githubService;
    private UpdateStagingService $stagingService;

    public function __construct(GitHubReleaseService $githubService, UpdateStagingService $stagingService)
    {
        parent::__construct();
        $this->githubService = $githubService;
        $this->stagingService = $stagingService;
    }

    public function handle(): int
    {
        $environment = $this->option('environment');
        
        $this->info('🔍 Checking for ACS updates...');
        $this->newLine();

        $release = $this->githubService->getLatestRelease();

        if (!$release) {
            $this->error('❌ Failed to fetch latest release from GitHub');
            return self::FAILURE;
        }

        $latestVersion = ltrim($release['tag_name'], 'v');
        $this->info("📦 Latest GitHub Release: v{$latestVersion}");
        
        $current = SystemVersion::getCurrentVersion($environment);
        $currentVersion = $current ? $current->version : '0.0.0';
        
        $this->info("💻 Current System Version: v{$currentVersion}");
        $this->newLine();

        $comparison = $this->githubService->compareVersions($currentVersion, $latestVersion);

        if ($comparison <= 0) {
            $this->info('✅ System is up-to-date!');
            return self::SUCCESS;
        }

        $this->warn("🆕 New version available: v{$latestVersion}");
        $this->newLine();

        $this->table(
            ['Field', 'Value'],
            [
                ['Release Name', $release['name'] ?? 'N/A'],
                ['Tag', $release['tag_name']],
                ['Published', date('Y-m-d H:i', strtotime($release['published_at']))],
                ['Author', $release['author']['login'] ?? 'N/A'],
                ['Assets', count($release['assets'] ?? [])],
            ]
        );

        $this->newLine();
        
        if (!empty($release['body'])) {
            $this->info('📝 Changelog:');
            $this->line($release['body']);
            $this->newLine();
        }

        $pendingUpdate = SystemVersion::where('version', $latestVersion)
            ->where('environment', $environment)
            ->first();

        if ($pendingUpdate) {
            $this->info("ℹ️  Update already staged with status: {$pendingUpdate->approval_status}");
            return self::SUCCESS;
        }

        if ($this->option('auto-stage') || $this->confirm('Would you like to stage this update for approval?', true)) {
            $this->info('⬇️  Downloading and staging update...');
            
            $progressBar = $this->output->createProgressBar(3);
            $progressBar->setFormat('[%bar%] %percent:3s%% - %message%');
            
            $progressBar->setMessage('Downloading package...');
            $progressBar->start();
            
            $staged = $this->stagingService->stageUpdate($release, $environment);
            $progressBar->advance();

            if (!$staged) {
                $progressBar->finish();
                $this->newLine(2);
                $this->error('❌ Failed to stage update');
                return self::FAILURE;
            }

            $progressBar->setMessage('Validating package...');
            $progressBar->advance();
            
            $validation = $this->stagingService->validateStagedUpdate($staged);
            $progressBar->advance();
            
            $progressBar->finish();
            $this->newLine(2);

            $this->info('✅ Update staged successfully!');
            $this->newLine();

            $this->table(
                ['Check', 'Status', 'Message'],
                collect($validation)->map(function ($check, $name) {
                    return [
                        $name,
                        $check['status'] === 'ok' ? '✓' : ($check['status'] === 'failed' ? '✗' : '⚠'),
                        $check['message'],
                    ];
                })->toArray()
            );

            $this->newLine();
            $this->info('👉 Next steps:');
            $this->line('   1. Review the update in the admin dashboard');
            $this->line('   2. Approve or reject the update');
            $this->line('   3. Apply the update when ready');
        } else {
            $this->info('Update check completed. No action taken.');
        }

        return self::SUCCESS;
    }
}
