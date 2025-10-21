<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ExecutionUnit Model (TR-157)
 * 
 * Represents a running execution unit (process/service) on a CPE device.
 * Maps to Device.SoftwareModules.ExecutionUnit.{i}.* in TR-157
 */
class ExecutionUnit extends Model
{
    protected $fillable = [
        'cpe_device_id',
        'deployment_unit_id',
        'euid',
        'name',
        'status',
        'requested_state',
        'execution_fault_code',
        'execution_fault_message',
        'vendor',
        'version',
        'run_level',
        'auto_start',
        'exec_env_label',
    ];

    protected $casts = [
        'auto_start' => 'boolean',
        'run_level' => 'integer',
    ];

    /**
     * Get the CPE device that owns this execution unit
     */
    public function cpeDevice(): BelongsTo
    {
        return $this->belongsTo(CpeDevice::class);
    }

    /**
     * Get the deployment unit that owns this execution unit
     */
    public function deploymentUnit(): BelongsTo
    {
        return $this->belongsTo(DeploymentUnit::class);
    }

    /**
     * Boot method to generate stable EUID on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($executionUnit) {
            if (empty($executionUnit->euid)) {
                $executionUnit->euid = 'EU_' . $executionUnit->cpe_device_id . '_' . uniqid();
            }
        });
    }
}
