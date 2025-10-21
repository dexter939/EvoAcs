<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AlertRule;
use Carbon\Carbon;

class AlertRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'High CPU Load - Critical',
                'description' => 'Alert when CPU load (1min) exceeds 15 for sustained period',
                'rule_type' => 'system',
                'metric' => 'cpu_load_1min',
                'condition' => '>',
                'threshold_value' => 15.0,
                'duration_minutes' => 5,
                'severity' => 'critical',
                'notification_channels' => ['email', 'webhook'],
                'recipients' => ['ops@example.com'],
                'is_active' => true,
            ],
            [
                'name' => 'High CPU Load - Warning',
                'description' => 'Alert when CPU load (5min) exceeds 10',
                'rule_type' => 'system',
                'metric' => 'cpu_load_5min',
                'condition' => '>',
                'threshold_value' => 10.0,
                'duration_minutes' => 10,
                'severity' => 'high',
                'notification_channels' => ['email'],
                'recipients' => ['ops@example.com'],
                'is_active' => true,
            ],
            [
                'name' => 'High Memory Usage',
                'description' => 'Alert when memory usage exceeds 1GB',
                'rule_type' => 'system',
                'metric' => 'memory_usage_mb',
                'condition' => '>',
                'threshold_value' => 1024.0,
                'duration_minutes' => 10,
                'severity' => 'high',
                'notification_channels' => ['email'],
                'recipients' => ['ops@example.com'],
                'is_active' => true,
            ],
            [
                'name' => 'High Disk Usage',
                'description' => 'Alert when disk usage exceeds 85%',
                'rule_type' => 'system',
                'metric' => 'disk_usage_percent',
                'condition' => '>',
                'threshold_value' => 85.0,
                'duration_minutes' => 15,
                'severity' => 'high',
                'notification_channels' => ['email', 'webhook'],
                'recipients' => ['ops@example.com'],
                'is_active' => true,
            ],
            [
                'name' => 'Database Slow Queries',
                'description' => 'Alert when average DB query time exceeds 200ms',
                'rule_type' => 'database',
                'metric' => 'db_query_time_ms',
                'condition' => '>',
                'threshold_value' => 200.0,
                'duration_minutes' => 10,
                'severity' => 'medium',
                'notification_channels' => ['email'],
                'recipients' => ['ops@example.com'],
                'is_active' => true,
            ],
            [
                'name' => 'Database Size Warning',
                'description' => 'Alert when database size exceeds 5GB',
                'rule_type' => 'database',
                'metric' => 'db_size_mb',
                'condition' => '>',
                'threshold_value' => 5120.0,
                'duration_minutes' => 60,
                'severity' => 'medium',
                'notification_channels' => ['email'],
                'recipients' => ['ops@example.com'],
                'is_active' => true,
            ],
            [
                'name' => 'Many Devices Offline',
                'description' => 'Alert when more than 100 devices go offline',
                'rule_type' => 'device',
                'metric' => 'devices_offline',
                'condition' => '>',
                'threshold_value' => 100.0,
                'duration_minutes' => 10,
                'severity' => 'high',
                'notification_channels' => ['email', 'webhook'],
                'recipients' => ['ops@example.com'],
                'is_active' => true,
            ],
            [
                'name' => 'Low Device Online Rate',
                'description' => 'Alert when online device percentage drops below 80%',
                'rule_type' => 'device',
                'metric' => 'devices_online_percent',
                'condition' => '<',
                'threshold_value' => 80.0,
                'duration_minutes' => 15,
                'severity' => 'high',
                'notification_channels' => ['email'],
                'recipients' => ['ops@example.com'],
                'is_active' => false, // Disabled initially (no devices yet)
            ],
            [
                'name' => 'Queue Jobs Backlog',
                'description' => 'Alert when pending jobs exceed 1000',
                'rule_type' => 'queue',
                'metric' => 'queue_pending_jobs',
                'condition' => '>',
                'threshold_value' => 1000.0,
                'duration_minutes' => 10,
                'severity' => 'high',
                'notification_channels' => ['email', 'webhook'],
                'recipients' => ['ops@example.com'],
                'is_active' => true,
            ],
            [
                'name' => 'Queue Failed Jobs',
                'description' => 'Alert when failed jobs exceed 50',
                'rule_type' => 'queue',
                'metric' => 'queue_failed_jobs',
                'condition' => '>',
                'threshold_value' => 50.0,
                'duration_minutes' => 5,
                'severity' => 'critical',
                'notification_channels' => ['email', 'webhook'],
                'recipients' => ['ops@example.com'],
                'is_active' => true,
            ],
            [
                'name' => 'Critical Alarms Active',
                'description' => 'Alert when critical alarms are active',
                'rule_type' => 'alarm',
                'metric' => 'alarms_critical',
                'condition' => '>',
                'threshold_value' => 0.0,
                'duration_minutes' => 1,
                'severity' => 'critical',
                'notification_channels' => ['email', 'webhook'],
                'recipients' => ['ops@example.com'],
                'is_active' => true,
            ],
            [
                'name' => 'Many Active Alarms',
                'description' => 'Alert when total active alarms exceed 100',
                'rule_type' => 'alarm',
                'metric' => 'alarms_active',
                'condition' => '>',
                'threshold_value' => 100.0,
                'duration_minutes' => 10,
                'severity' => 'high',
                'notification_channels' => ['email'],
                'recipients' => ['ops@example.com'],
                'is_active' => true,
            ],
            [
                'name' => 'Cache System Down',
                'description' => 'Alert when cache system is not operational',
                'rule_type' => 'system',
                'metric' => 'cache_operational',
                'condition' => '=',
                'threshold_value' => 0.0,
                'duration_minutes' => 1,
                'severity' => 'critical',
                'notification_channels' => ['email', 'webhook'],
                'recipients' => ['ops@example.com'],
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            AlertRule::updateOrCreate(
                ['name' => $rule['name']],
                array_merge($rule, [
                    'trigger_count' => 0,
                    'last_triggered_at' => null,
                ])
            );
        }

        $this->command->info('Alert rules seeded successfully: ' . count($rules) . ' rules created/updated.');
    }
}
