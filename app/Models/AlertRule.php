<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'rule_type',
        'metric',
        'condition',
        'threshold_value',
        'duration_minutes',
        'severity',
        'notification_channels',
        'recipients',
        'is_active',
        'last_triggered_at',
        'trigger_count',
    ];

    protected $casts = [
        'notification_channels' => 'array',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function evaluate($currentValue)
    {
        switch ($this->condition) {
            case '>':
                return $currentValue > $this->threshold_value;
            case '<':
                return $currentValue < $this->threshold_value;
            case '>=':
                return $currentValue >= $this->threshold_value;
            case '<=':
                return $currentValue <= $this->threshold_value;
            case '=':
                return $currentValue == $this->threshold_value;
            case '!=':
                return $currentValue != $this->threshold_value;
            default:
                return false;
        }
    }
}
