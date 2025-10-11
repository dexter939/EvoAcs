<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CpeDevice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'serial_number',
        'oui',
        'product_class',
        'manufacturer',
        'model_name',
        'hardware_version',
        'software_version',
        'connection_request_url',
        'connection_request_username',
        'connection_request_password',
        'ip_address',
        'mac_address',
        'status',
        'last_inform',
        'last_contact',
        'configuration_profile_id',
        'device_info',
        'wan_info',
        'wifi_info',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'device_info' => 'array',
        'wan_info' => 'array',
        'wifi_info' => 'array',
        'is_active' => 'boolean',
        'last_inform' => 'datetime',
        'last_contact' => 'datetime',
    ];

    public function configurationProfile(): BelongsTo
    {
        return $this->belongsTo(ConfigurationProfile::class);
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(DeviceParameter::class);
    }

    public function provisioningTasks(): HasMany
    {
        return $this->hasMany(ProvisioningTask::class);
    }

    public function firmwareDeployments(): HasMany
    {
        return $this->hasMany(FirmwareDeployment::class);
    }
}
