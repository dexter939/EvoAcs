<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageService extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cpe_device_id',
        'service_instance',
        'enabled',
        'total_capacity',
        'used_capacity',
        'raid_supported',
        'supported_raid_types',
        'ftp_supported',
        'sftp_supported',
        'http_supported',
        'https_supported',
        'samba_supported',
        'nfs_supported',
        'physical_mediums_count',
        'logical_volumes_count',
        'health_status',
        'temperature',
        'smart_status',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'raid_supported' => 'boolean',
        'supported_raid_types' => 'array',
        'ftp_supported' => 'boolean',
        'sftp_supported' => 'boolean',
        'http_supported' => 'boolean',
        'https_supported' => 'boolean',
        'samba_supported' => 'boolean',
        'nfs_supported' => 'boolean',
    ];

    public function cpeDevice(): BelongsTo
    {
        return $this->belongsTo(CpeDevice::class);
    }

    public function logicalVolumes(): HasMany
    {
        return $this->hasMany(LogicalVolume::class);
    }

    public function fileServers(): HasMany
    {
        return $this->hasMany(FileServer::class);
    }

    public function getUsagePercentAttribute(): float
    {
        if ($this->total_capacity === 0) {
            return 0;
        }
        return round(($this->used_capacity / $this->total_capacity) * 100, 2);
    }

    public function getFreeCapacityAttribute(): int
    {
        return $this->total_capacity - $this->used_capacity;
    }
}
