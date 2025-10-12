<?php

namespace App\Services;

use App\Models\CpeDevice;
use App\Models\FemtocellConfig;
use App\Models\NeighborCellList;

class FemtocellManagementService
{
    public function configureFemtocell(CpeDevice $cpeDevice, array $config): FemtocellConfig
    {
        return FemtocellConfig::updateOrCreate(
            ['cpe_device_id' => $cpeDevice->id],
            [
                'technology' => $config['technology'],
                'gps_latitude' => $config['gps_latitude'] ?? null,
                'gps_longitude' => $config['gps_longitude'] ?? null,
                'gps_altitude' => $config['gps_altitude'] ?? null,
                'uarfcn' => $config['uarfcn'] ?? null,
                'earfcn' => $config['earfcn'] ?? null,
                'physical_cell_id' => $config['physical_cell_id'] ?? null,
                'tx_power' => $config['tx_power'],
                'max_tx_power' => $config['max_tx_power'] ?? 20,
                'rf_parameters' => $config['rf_parameters'] ?? [],
                'plmn_list' => $config['plmn_list'] ?? [],
                'auto_config' => $config['auto_config'] ?? true
            ]
        );
    }

    public function updateNeighborCell(FemtocellConfig $config, array $cellData): NeighborCellList
    {
        return $config->neighborCells()->create([
            'neighbor_type' => $cellData['neighbor_type'],
            'neighbor_arfcn' => $cellData['neighbor_arfcn'] ?? null,
            'neighbor_pci' => $cellData['neighbor_pci'] ?? null,
            'rssi' => $cellData['rssi'] ?? null,
            'rsrp' => $cellData['rsrp'] ?? null,
            'rsrq' => $cellData['rsrq'] ?? null,
            'rem_data' => $cellData['rem_data'] ?? [],
            'last_scanned' => now()
        ]);
    }

    public function scanRadioEnvironment(FemtocellConfig $config): array
    {
        return ['status' => 'scanning', 'cells_found' => 0];
    }
}
