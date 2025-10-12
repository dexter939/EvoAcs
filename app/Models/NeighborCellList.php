<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NeighborCellList extends Model
{
    protected $fillable = [
        'femtocell_config_id', 'neighbor_type', 'neighbor_arfcn', 'neighbor_pci',
        'rssi', 'rsrp', 'rsrq', 'is_blacklisted', 'rem_data', 'last_scanned'
    ];

    protected $casts = [
        'is_blacklisted' => 'boolean',
        'rem_data' => 'array',
        'last_scanned' => 'datetime'
    ];

    public function femtocellConfig()
    {
        return $this->belongsTo(FemtocellConfig::class);
    }

    public function scopeNotBlacklisted($query)
    {
        return $query->where('is_blacklisted', false);
    }
}
