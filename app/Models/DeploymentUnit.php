<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DeploymentUnit Model (TR-157)
 * 
 * Represents a software deployment unit (package) installed on a CPE device.
 * Maps to Device.SoftwareModules.DeploymentUnit.{i}.* in TR-157
 */
class DeploymentUnit extends Model
{
    protected $fillable = [
        'cpe_device_id',
        'uuid',
        'duid',
        'name',
        'status',
        'resolved',
        'url',
        'vendor',
        'version',
        'description',
        'execution_env_ref',
    ];

    protected $casts = [
        'resolved' => 'boolean',
    ];

    /**
     * Get the CPE device that owns this deployment unit
     */
    public function cpeDevice(): BelongsTo
    {
        return $this->belongsTo(CpeDevice::class);
    }

    /**
     * Get the execution units for this deployment unit
     */
    public function executionUnits(): HasMany
    {
        return $this->hasMany(ExecutionUnit::class);
    }

    /**
     * Boot method to generate stable UUID on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($deploymentUnit) {
            if (empty($deploymentUnit->uuid)) {
                $deploymentUnit->uuid = self::generateStableUuid(
                    $deploymentUnit->cpe_device_id,
                    'du',
                    $deploymentUnit->name
                );
            }
            
            if (empty($deploymentUnit->duid)) {
                $deploymentUnit->duid = 'DU_' . $deploymentUnit->cpe_device_id . '_' . uniqid();
            }
        });
    }

    /**
     * Generate stable UUID from device ID and component
     */
    private static function generateStableUuid(int $deviceId, string $component, string $name): string
    {
        $namespace = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $seed = "device_{$deviceId}_{$component}_{$name}";
        
        $hash = md5($namespace . $seed);
        
        return sprintf('%08s-%04s-%04s-%04s-%012s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            '5' . substr($hash, 13, 3),
            dechex(hexdec(substr($hash, 16, 4)) & 0x3fff | 0x8000),
            substr($hash, 20, 12)
        );
    }
}
