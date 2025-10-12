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
        return NeighborCellList::updateOrCreate(
            [
                'femtocell_config_id' => $config->id,
                'neighbor_type' => $cellData['neighbor_type'],
                'neighbor_arfcn' => $cellData['neighbor_arfcn'] ?? null,
                'neighbor_pci' => $cellData['neighbor_pci'] ?? null
            ],
            [
                'rssi' => $cellData['rssi'] ?? null,
                'rsrp' => $cellData['rsrp'] ?? null,
                'rsrq' => $cellData['rsrq'] ?? null,
                'is_blacklisted' => $cellData['is_blacklisted'] ?? false,
                'rem_data' => $cellData['rem_data'] ?? [],
                'last_scanned' => now()
            ]
        );
    }

    public function scanRadioEnvironment(FemtocellConfig $config): array
    {
        $cellsFound = 0;
        $measurements = [];

        // Simulate REM scanning workflow for TR-196 compliance
        $scanFrequencies = $this->getScanFrequencies($config);
        
        foreach ($scanFrequencies as $freq) {
            $measurement = [
                'arfcn' => $freq,
                'pci' => rand(0, 503),
                'rssi' => rand(-100, -40),
                'rsrp' => rand(-110, -50),
                'rsrq' => rand(-20, -3),
                'scan_time' => now()->toIso8601String()
            ];
            
            $this->updateNeighborCell($config, [
                'neighbor_type' => $this->determineNeighborType($config, $freq),
                'neighbor_arfcn' => $freq,
                'neighbor_pci' => $measurement['pci'],
                'rssi' => $measurement['rssi'],
                'rsrp' => $measurement['rsrp'],
                'rsrq' => $measurement['rsrq'],
                'rem_data' => $measurement
            ]);
            
            $measurements[] = $measurement;
            $cellsFound++;
        }

        $config->update(['status' => 'active']);

        return [
            'status' => 'completed',
            'cells_found' => $cellsFound,
            'measurements' => $measurements,
            'scan_timestamp' => now()->toIso8601String()
        ];
    }

    private function getScanFrequencies(FemtocellConfig $config): array
    {
        $baseFreq = $config->earfcn ?? $config->uarfcn ?? 0;
        return $baseFreq > 0 ? range($baseFreq - 10, $baseFreq + 10, 5) : [1800, 1850, 1900];
    }

    private function determineNeighborType(FemtocellConfig $config, int $freq): string
    {
        $myFreq = $config->earfcn ?? $config->uarfcn ?? 0;
        if ($freq == $myFreq) return 'intra_freq';
        if ($config->technology == 'LTE' && $freq != $myFreq) return 'inter_freq';
        return 'inter_rat';
    }
}
