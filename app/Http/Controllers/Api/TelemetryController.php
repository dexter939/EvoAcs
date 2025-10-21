<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemMetric;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TelemetryController extends Controller
{
    public function current(Request $request)
    {
        $metrics = $request->input('metrics', [
            'cpu_load_1min', 'cpu_load_5min', 'cpu_load_15min',
            'memory_usage_mb', 'disk_usage_percent',
            'db_active_connections', 'db_query_time_ms', 'db_size_mb',
            'devices_total', 'devices_online', 'devices_offline',
            'queue_pending_jobs', 'queue_failed_jobs',
            'alarms_active', 'alarms_critical',
            'cache_operational'
        ]);

        $result = [];
        foreach ($metrics as $metric) {
            $latest = SystemMetric::where('metric_name', $metric)
                ->orderBy('recorded_at', 'desc')
                ->first();
            
            if ($latest) {
                $result[$metric] = [
                    'value' => $latest->value,
                    'type' => $latest->metric_type,
                    'tags' => $latest->tags,
                    'recorded_at' => $latest->recorded_at->toIso8601String(),
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'timestamp' => Carbon::now()->toIso8601String(),
            'metrics' => $result,
        ]);
    }

    public function history(Request $request)
    {
        $metric = $request->input('metric', 'cpu_load_1min');
        $hours = $request->input('hours', 24);

        $history = SystemMetric::where('metric_name', $metric)
            ->where('recorded_at', '>=', Carbon::now()->subHours($hours))
            ->orderBy('recorded_at')
            ->get()
            ->map(function ($item) {
                return [
                    'timestamp' => $item->recorded_at->toIso8601String(),
                    'value' => $item->value,
                    'tags' => $item->tags,
                ];
            });

        return response()->json([
            'status' => 'success',
            'metric' => $metric,
            'hours' => $hours,
            'data_points' => $history->count(),
            'history' => $history,
        ]);
    }

    public function summary()
    {
        $summary = SystemMetric::select('metric_name')
            ->selectRaw('COUNT(*) as data_points')
            ->selectRaw('AVG(value) as avg_value')
            ->selectRaw('MIN(value) as min_value')
            ->selectRaw('MAX(value) as max_value')
            ->selectRaw('MAX(recorded_at) as last_recorded')
            ->groupBy('metric_name')
            ->orderBy('metric_name')
            ->get()
            ->map(function ($item) {
                return [
                    'metric' => $item->metric_name,
                    'data_points' => $item->data_points,
                    'avg' => round($item->avg_value, 2),
                    'min' => round($item->min_value, 2),
                    'max' => round($item->max_value, 2),
                    'last_recorded' => Carbon::parse($item->last_recorded)->toIso8601String(),
                ];
            });

        return response()->json([
            'status' => 'success',
            'timestamp' => Carbon::now()->toIso8601String(),
            'total_metrics' => $summary->count(),
            'summary' => $summary,
        ]);
    }

    public function health()
    {
        $latest = SystemMetric::orderBy('recorded_at', 'desc')->first();
        
        if (!$latest) {
            return response()->json([
                'status' => 'warning',
                'message' => 'No metrics collected yet',
                'healthy' => false,
            ]);
        }

        $minutesSinceLastMetric = Carbon::now()->diffInMinutes($latest->recorded_at);
        $healthy = $minutesSinceLastMetric < 10;

        $criticalMetrics = [
            'cache_operational' => ['expected' => 1, 'operator' => '=='],
            'db_active_connections' => ['expected' => 0, 'operator' => '>'],
        ];

        $issues = [];
        foreach ($criticalMetrics as $metric => $check) {
            $current = SystemMetric::where('metric_name', $metric)
                ->orderBy('recorded_at', 'desc')
                ->first();
            
            if ($current) {
                $value = $current->value;
                $expected = $check['expected'];
                $operator = $check['operator'];
                
                $pass = match($operator) {
                    '==' => $value == $expected,
                    '>' => $value > $expected,
                    '<' => $value < $expected,
                    default => true,
                };
                
                if (!$pass) {
                    $issues[] = [
                        'metric' => $metric,
                        'current' => $value,
                        'expected' => $expected,
                        'operator' => $operator,
                    ];
                }
            }
        }

        return response()->json([
            'status' => $healthy && empty($issues) ? 'healthy' : 'degraded',
            'healthy' => $healthy && empty($issues),
            'last_metric_collected' => $latest->recorded_at->toIso8601String(),
            'minutes_since_last_metric' => $minutesSinceLastMetric,
            'issues' => $issues,
        ]);
    }
}
