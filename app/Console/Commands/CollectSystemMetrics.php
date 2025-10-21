<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SystemMetric;
use App\Models\CpeDevice;
use App\Models\Alarm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CollectSystemMetrics extends Command
{
    protected $signature = 'metrics:collect';
    protected $description = 'Collect and store system telemetry metrics';

    public function handle()
    {
        $this->info('Collecting system metrics...');

        $this->collectSystemResources();
        $this->collectDatabaseMetrics();
        $this->collectDeviceMetrics();
        $this->collectQueueMetrics();
        $this->collectCacheMetrics();
        $this->collectAlarmMetrics();

        $this->info('Metrics collection completed.');
    }

    protected function collectSystemResources()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            SystemMetric::record('cpu_load_1min', $load[0] ?? 0, 'gauge', ['interval' => '1min']);
            SystemMetric::record('cpu_load_5min', $load[1] ?? 0, 'gauge', ['interval' => '5min']);
            SystemMetric::record('cpu_load_15min', $load[2] ?? 0, 'gauge', ['interval' => '15min']);
        }

        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        SystemMetric::record('memory_usage_mb', $memoryUsage, 'gauge', ['unit' => 'MB']);

        $memoryPeak = memory_get_peak_usage(true) / 1024 / 1024;
        SystemMetric::record('memory_peak_mb', $memoryPeak, 'gauge', ['unit' => 'MB']);

        if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
            $diskFree = @disk_free_space('/');
            $diskTotal = @disk_total_space('/');
            if ($diskFree !== false && $diskTotal !== false && $diskTotal > 0) {
                $diskUsagePercent = (($diskTotal - $diskFree) / $diskTotal) * 100;
                SystemMetric::record('disk_usage_percent', $diskUsagePercent, 'gauge', ['unit' => 'percent']);
            }
        }
    }

    protected function collectDatabaseMetrics()
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        $queryTime = (microtime(true) - $start) * 1000;
        SystemMetric::record('db_query_time_ms', $queryTime, 'gauge', ['query' => 'health_check']);

        $activeConnections = DB::select("SELECT count(*) as count FROM pg_stat_activity WHERE state = 'active'");
        SystemMetric::record('db_active_connections', $activeConnections[0]->count ?? 0, 'gauge');

        $totalConnections = DB::select("SELECT count(*) as count FROM pg_stat_activity");
        SystemMetric::record('db_total_connections', $totalConnections[0]->count ?? 0, 'gauge');

        $dbSize = DB::select("SELECT pg_database_size(current_database()) as size");
        $dbSizeMB = ($dbSize[0]->size ?? 0) / 1024 / 1024;
        SystemMetric::record('db_size_mb', $dbSizeMB, 'gauge', ['unit' => 'MB']);
    }

    protected function collectDeviceMetrics()
    {
        $totalDevices = CpeDevice::count();
        SystemMetric::record('devices_total', $totalDevices, 'gauge');

        $onlineDevices = CpeDevice::where('status', 'online')->count();
        SystemMetric::record('devices_online', $onlineDevices, 'gauge');

        $offlineDevices = CpeDevice::where('status', 'offline')->count();
        SystemMetric::record('devices_offline', $offlineDevices, 'gauge');

        $recentDevices = CpeDevice::where('last_inform', '>=', now()->subHour())->count();
        SystemMetric::record('devices_active_last_hour', $recentDevices, 'counter');

        $recentlyRegistered = CpeDevice::where('created_at', '>=', now()->subHour())->count();
        SystemMetric::record('devices_registered_last_hour', $recentlyRegistered, 'counter');

        if ($totalDevices > 0) {
            $onlinePercent = ($onlineDevices / $totalDevices) * 100;
            SystemMetric::record('devices_online_percent', $onlinePercent, 'gauge', ['unit' => 'percent']);
        }
    }

    protected function collectQueueMetrics()
    {
        $failedJobs = DB::table('failed_jobs')->count();
        SystemMetric::record('queue_failed_jobs', $failedJobs, 'gauge');

        $pendingJobs = DB::table('jobs')->count();
        SystemMetric::record('queue_pending_jobs', $pendingJobs, 'gauge');

        if (DB::getSchemaBuilder()->hasTable('job_batches')) {
            $failedBatches = DB::table('job_batches')->where('failed_jobs', '>', 0)->count();
            SystemMetric::record('queue_failed_batches', $failedBatches, 'gauge');
        }
    }

    protected function collectCacheMetrics()
    {
        try {
            Cache::put('metrics_test_key', 'test_value', 60);
            $retrieved = Cache::get('metrics_test_key');
            
            if ($retrieved === 'test_value') {
                SystemMetric::record('cache_operational', 1, 'gauge');
            } else {
                SystemMetric::record('cache_operational', 0, 'gauge');
            }
            
            Cache::forget('metrics_test_key');
        } catch (\Exception $e) {
            SystemMetric::record('cache_operational', 0, 'gauge');
        }
    }

    protected function collectAlarmMetrics()
    {
        $activeAlarms = Alarm::where('status', 'active')->count();
        SystemMetric::record('alarms_active', $activeAlarms, 'gauge');

        $criticalAlarms = Alarm::where('status', 'active')
            ->where('severity', 'critical')
            ->count();
        SystemMetric::record('alarms_critical', $criticalAlarms, 'gauge');

        $recentAlarms = Alarm::where('created_at', '>=', now()->subHour())->count();
        SystemMetric::record('alarms_last_hour', $recentAlarms, 'counter');
    }
}
