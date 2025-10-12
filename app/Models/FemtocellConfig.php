<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FemtocellConfig extends Model
{
    protected $fillable = [
        'cpe_device_id', 'technology', 'gps_latitude', 'gps_longitude', 'gps_altitude',
        'uarfcn', 'earfcn', 'physical_cell_id', 'tx_power', 'max_tx_power',
        'rf_parameters', 'plmn_list', 'auto_config', 'status'
    ];

    protected $casts = [
        'gps_latitude' => 'decimal:7',
        'gps_longitude' => 'decimal:7',
        'gps_altitude' => 'decimal:2',
        'rf_parameters' => 'array',
        'plmn_list' => 'array',
        'auto_config' => 'boolean'
    ];

    public function cpeDevice()
    {
        return $this->belongsTo(CpeDevice::class);
    }

    public function neighborCells()
    {
        return $this->hasMany(NeighborCellList::class);
    }

    public function scopeByTechnology($query, $tech)
    {
        return $query->where('technology', $tech);
    }
}
