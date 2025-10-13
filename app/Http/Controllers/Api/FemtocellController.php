<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Models\CpeDevice;
use App\Models\FemtocellConfig;
use App\Services\FemtocellManagementService;
use Illuminate\Http\Request;

class FemtocellController extends Controller
{
    use ApiResponse;
    protected $femtoService;

    public function __construct(FemtocellManagementService $femtoService)
    {
        $this->femtoService = $femtoService;
    }

    public function configure(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'technology' => 'required|string',
            'tx_power' => 'required|integer',
            'gps_latitude' => 'nullable|numeric',
            'earfcn' => 'nullable|integer'
        ]);
        $config = $this->femtoService->configureFemtocell($device, $validated);
        return response()->json(['success' => true, 'config' => $config], 201);
    }

    public function addNeighborCell(Request $request, FemtocellConfig $config)
    {
        $validated = $request->validate([
            'neighbor_type' => 'required|string',
            'neighbor_arfcn' => 'nullable|integer',
            'neighbor_pci' => 'nullable|integer',
            'rssi' => 'nullable|integer',
            'rsrp' => 'nullable|integer',
            'rsrq' => 'nullable|integer',
            'is_blacklisted' => 'nullable|boolean',
            'rem_data' => 'nullable|array'
        ]);
        $cell = $this->femtoService->updateNeighborCell($config, $validated);
        return response()->json(['success' => true, 'cell' => $cell], 201);
    }

    public function scanEnvironment(FemtocellConfig $config)
    {
        $result = $this->femtoService->scanRadioEnvironment($config);
        return response()->json($result);
    }
}
