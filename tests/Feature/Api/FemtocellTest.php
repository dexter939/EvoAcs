<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\CpeDevice;
use App\Models\FemtocellConfig;
use App\Models\NeighborCellList;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FemtocellTest extends TestCase
{
    use RefreshDatabase;

    public function test_configure_femtocell(): void
    {
        $device = CpeDevice::factory()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/femtocell/configure", [
            'technology' => 'LTE',
            'tx_power' => 15,
            'gps_latitude' => 45.4642,
            'gps_longitude' => 9.1900,
            'earfcn' => 1850
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'config' => ['technology', 'tx_power', 'gps_latitude', 'earfcn']
            ]);

        $this->assertDatabaseHas('femtocell_configs', [
            'cpe_device_id' => $device->id,
            'technology' => 'LTE',
            'tx_power' => 15
        ]);
    }

    public function test_configure_validates_required_fields(): void
    {
        $device = CpeDevice::factory()->create();

        $response = $this->apiPost("/api/v1/devices/{$device->id}/femtocell/configure", [
            'technology' => 'LTE'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tx_power']);
    }

    public function test_add_neighbor_cell(): void
    {
        $device = CpeDevice::factory()->create();
        
        $config = FemtocellConfig::create([
            'cpe_device_id' => $device->id,
            'technology' => 'LTE',
            'tx_power' => 20,
            'earfcn' => 1800
        ]);

        $response = $this->apiPost("/api/v1/femtocell-configs/{$config->id}/neighbor-cells", [
            'neighbor_type' => 'inter_freq',
            'neighbor_arfcn' => 1850,
            'neighbor_pci' => 150,
            'rssi' => -75,
            'rsrp' => -85,
            'rsrq' => -10
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'cell' => ['neighbor_type', 'neighbor_arfcn', 'rssi']
            ]);

        $this->assertDatabaseHas('neighbor_cell_lists', [
            'femtocell_config_id' => $config->id,
            'neighbor_type' => 'inter_freq',
            'neighbor_arfcn' => 1850
        ]);
    }

    public function test_scan_radio_environment(): void
    {
        $device = CpeDevice::factory()->create();
        
        $config = FemtocellConfig::create([
            'cpe_device_id' => $device->id,
            'technology' => 'LTE',
            'tx_power' => 20,
            'earfcn' => 1800
        ]);

        $response = $this->apiPost("/api/v1/femtocell-configs/{$config->id}/scan");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'cells_found',
                'measurements',
                'scan_timestamp'
            ]);

        $result = $response->json();
        $this->assertEquals('completed', $result['status']);
        $this->assertGreaterThan(0, $result['cells_found']);
    }

    public function test_neighbor_cell_upsert_prevents_duplicates(): void
    {
        $device = CpeDevice::factory()->create();
        
        $config = FemtocellConfig::create([
            'cpe_device_id' => $device->id,
            'technology' => 'LTE',
            'tx_power' => 20,
            'earfcn' => 1800
        ]);

        $cellData = [
            'neighbor_type' => 'intra_freq',
            'neighbor_arfcn' => 1800,
            'neighbor_pci' => 100,
            'rssi' => -70
        ];

        $this->apiPost("/api/v1/femtocell-configs/{$config->id}/neighbor-cells", $cellData);
        
        $cellData['rssi'] = -65;
        $this->apiPost("/api/v1/femtocell-configs/{$config->id}/neighbor-cells", $cellData);

        $count = NeighborCellList::where([
            'femtocell_config_id' => $config->id,
            'neighbor_type' => 'intra_freq',
            'neighbor_arfcn' => 1800,
            'neighbor_pci' => 100
        ])->count();

        $this->assertEquals(1, $count);
    }
}
