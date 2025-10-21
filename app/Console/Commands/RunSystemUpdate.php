<?php

namespace App\Console\Commands;

use App\Services\SystemUpdateService;
use Illuminate\Console\Command;

class RunSystemUpdate extends Command
{
    protected $signature = 'system:update
                          {--force : Force update even if no changes detected}
                          {--env= : Specify environment (development, staging, production)}';

    protected $description = 'Run automatic system update with migrations and health checks';

    public function handle(SystemUpdateService $updateService): int
    {
        $this->info('🚀 ACS Auto-Update System');
        $this->newLine();

        $currentVersion = $updateService->getCurrentVersion();
        $this->line("Current version: <fg=cyan>{$currentVersion}</>");

        if (!$this->option('force') && !$updateService->checkForUpdate()) {
            $this->info('✓ System is already up-to-date');
            return Command::SUCCESS;
        }

        $this->warn('⚠️  Update available. Starting deployment...');
        $this->newLine();

        $bar = $this->output->createProgressBar(4);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        $bar->setMessage('Checking prerequisites...');
        $bar->start();
        sleep(1);

        $bar->advance();
        $bar->setMessage('Running database migrations...');
        sleep(1);

        $bar->advance();
        $bar->setMessage('Performing health checks...');

        $environment = $this->option('env') ?? config('app.env', 'production');
        $result = $updateService->performAutoUpdate($environment);

        $bar->advance();
        $bar->setMessage('Clearing caches...');
        sleep(1);

        $bar->advance();
        $bar->setMessage('Complete!');
        $bar->finish();

        $this->newLine(2);

        if ($result['success']) {
            $this->info('✓ Update completed successfully!');
            $this->newLine();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Version', $result['version']],
                    ['Migrations Run', $result['migrations_run']],
                    ['Health Status', $result['health_status']],
                    ['Duration', $result['duration'] ?? 'N/A'],
                ]
            );

            return Command::SUCCESS;
        } else {
            $this->error('✗ Update failed!');
            $this->error('Error: ' . $result['error']);

            return Command::FAILURE;
        }
    }
}
