<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SystemMetric extends Model
{
    protected $fillable = [
        'metric_name',
        'metric_type',
        'value',
        'tags',
        'recorded_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'recorded_at' => 'datetime',
    ];

    public static function record(string $name, float $value, string $type = 'gauge', array $tags = [])
    {
        return static::create([
            'metric_name' => $name,
            'metric_type' => $type,
            'value' => $value,
            'tags' => $tags,
            'recorded_at' => Carbon::now(),
        ]);
    }

    public static function getMetricHistory(string $name, int $hours = 24)
    {
        return static::where('metric_name', $name)
            ->where('recorded_at', '>=', Carbon::now()->subHours($hours))
            ->orderBy('recorded_at')
            ->get();
    }

    public static function getAverageValue(string $name, int $minutes = 60)
    {
        return static::where('metric_name', $name)
            ->where('recorded_at', '>=', Carbon::now()->subMinutes($minutes))
            ->avg('value');
    }
}
