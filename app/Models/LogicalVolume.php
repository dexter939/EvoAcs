<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogicalVolume extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'storage_service_id',
        'volume_instance',
        'enabled',
        'volume_name',
        'filesystem',
        'capacity',
        'used_space',
        'free_space',
        'usage_percent',
        'raid_level',
        'raid_status',
        'rebuild_progress',
        'mount_point',
        'auto_mount',
        'read_only',
        'quota_enabled',
        'quota_size',
        'quota_warning_threshold',
        'encrypted',
        'encryption_algorithm',
        'status',
        'last_check',
        'health_percentage',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'auto_mount' => 'boolean',
        'read_only' => 'boolean',
        'quota_enabled' => 'boolean',
        'encrypted' => 'boolean',
        'last_check' => 'datetime',
    ];

    public function storageService(): BelongsTo
    {
        return $this->belongsTo(StorageService::class);
    }

    public function updateUsageStats(): void
    {
        $this->free_space = $this->capacity - $this->used_space;
        $this->usage_percent = $this->capacity > 0 
            ? round(($this->used_space / $this->capacity) * 100, 2) 
            : 0;
        $this->save();
    }
}
