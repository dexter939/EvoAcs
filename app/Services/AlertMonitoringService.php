<?php

namespace App\Services;

use App\Models\AlertNotification;
use App\Models\AlertRule;
use App\Models\SystemMetric;
use App\Jobs\SendAlertNotificationJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * AlertMonitoringService - Servizio centralizzato per Advanced Monitoring
 * Gestisce alert rules, notifications e metrics collection per carrier-grade ACS
 */
class AlertMonitoringService
{
    /**
     * Valuta tutte le alert rules attive
     */
    public function evaluateAllRules()
    {
        $rules = AlertRule::active()->get();
        
        foreach ($rules as $rule) {
            $this->evaluateRule($rule);
        }
    }
    
    /**
     * Valuta una singola alert rule
     */
    public function evaluateRule(AlertRule $rule)
    {
        $currentValue = $this->getMetricValue($rule->metric);
        
        if ($rule->evaluate($currentValue)) {
            if ($this->shouldTriggerAlert($rule)) {
                $this->triggerAlert($rule, $currentValue);
            }
        }
    }
    
    /**
     * Recupera valore corrente di una metrica
     */
    protected function getMetricValue(string $metric)
    {
        switch ($metric) {
            case 'cpu_usage':
                return $this->getCpuUsage();
            case 'memory_usage':
                return $this->getMemoryUsage();
            case 'disk_usage':
                return $this->getDiskUsage();
            case 'avg_query_time':
                return $this->getAverageQueryTime();
            case 'devices_offline':
                return $this->getOfflineDevicesCount();
            case 'failed_jobs':
                return $this->getFailedJobsCount();
            case 'cache_hit_rate':
                return $this->getCacheHitRate();
            case 'active_alarms':
                return $this->getActiveAlarmsCount();
            default:
                return SystemMetric::getAverageValue($metric, 5);
        }
    }
    
    /**
     * Verifica se l'alert deve essere triggerato (evita spam)
     */
    protected function shouldTriggerAlert(AlertRule $rule)
    {
        if (!$rule->last_triggered_at) {
            return true;
        }
        
        $minutesSinceLastTrigger = Carbon::now()->diffInMinutes($rule->last_triggered_at);
        
        return $minutesSinceLastTrigger >= ($rule->duration_minutes * 2);
    }
    
    /**
     * Triggera un alert
     */
    protected function triggerAlert(AlertRule $rule, $currentValue)
    {
        $alert = AlertNotification::create([
            'alert_type' => 'system',
            'severity' => $rule->severity,
            'title' => $rule->name,
            'message' => "Alert triggered: {$rule->metric} {$rule->condition} {$rule->threshold_value}. Current value: {$currentValue}",
            'metadata' => [
                'rule_id' => $rule->id,
                'metric' => $rule->metric,
                'threshold' => $rule->threshold_value,
                'current_value' => $currentValue,
            ],
            'notification_channel' => 'multiple',
            'status' => 'pending',
        ]);
        
        $rule->update([
            'last_triggered_at' => Carbon::now(),
            'trigger_count' => $rule->trigger_count + 1,
        ]);
        
        foreach ($rule->notification_channels as $channel) {
            SendAlertNotificationJob::dispatch($alert, $channel, $rule->recipients);
        }
        
        Log::info("Alert triggered: {$rule->name}", ['value' => $currentValue]);
    }
    
    /**
     * Crea notification per evento specifico
     */
    public function createAlert(string $type, string $severity, string $title, string $message, array $metadata = [], $deviceId = null)
    {
        return AlertNotification::create([
            'alert_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
            'related_device_id' => $deviceId,
            'notification_channel' => 'multiple',
            'status' => 'pending',
        ]);
    }
    
    /**
     * Registra metrica di sistema
     */
    public function recordMetric(string $name, float $value, string $type = 'gauge', array $tags = [])
    {
        SystemMetric::record($name, $value, $type, $tags);
        
        $activeRules = AlertRule::active()
            ->where('metric', $name)
            ->get();
        
        foreach ($activeRules as $rule) {
            if ($rule->evaluate($value)) {
                $this->triggerAlert($rule, $value);
            }
        }
    }
    
    /**
     * System metrics getters
     */
    protected function getCpuUsage()
    {
        $load = sys_getloadavg();
        return $load[0] ?? 0;
    }
    
    protected function getMemoryUsage()
    {
        return round((memory_get_usage(true) / 1024 / 1024), 2);
    }
    
    protected function getDiskUsage()
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        return round((($total - $free) / $total) * 100, 2);
    }
    
    protected function getAverageQueryTime()
    {
        return SystemMetric::getAverageValue('query_time', 5) ?? 0;
    }
    
    protected function getOfflineDevicesCount()
    {
        return \App\Models\CpeDevice::where('status', 'offline')->count();
    }
    
    protected function getFailedJobsCount()
    {
        return \DB::table('failed_jobs')->count();
    }
    
    protected function getCacheHitRate()
    {
        $cacheService = app(CacheService::class);
        $stats = $cacheService->getCacheStatistics();
        return $stats['hit_rate'] ?? 0;
    }
    
    protected function getActiveAlarmsCount()
    {
        return \App\Models\Alarm::where('acknowledged', false)->count();
    }
    
    /**
     * Ottieni statistiche alerts
     */
    public function getAlertStatistics()
    {
        return [
            'total_alerts' => AlertNotification::count(),
            'critical_alerts' => AlertNotification::where('severity', 'critical')->count(),
            'pending_alerts' => AlertNotification::where('status', 'pending')->count(),
            'failed_alerts' => AlertNotification::where('status', 'failed')->count(),
            'last_24h_alerts' => AlertNotification::where('created_at', '>=', Carbon::now()->subDay())->count(),
            'active_rules' => AlertRule::where('is_active', true)->count(),
        ];
    }
    
    /**
     * Ottieni metriche di sistema recenti
     */
    public function getSystemMetrics(int $hours = 24)
    {
        return SystemMetric::where('recorded_at', '>=', Carbon::now()->subHours($hours))
            ->orderBy('recorded_at')
            ->get()
            ->groupBy('metric_name');
    }
}
