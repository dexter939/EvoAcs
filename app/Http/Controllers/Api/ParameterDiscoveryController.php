<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Models\CpeDevice;
use App\Services\ParameterDiscoveryService;
use Illuminate\Http\Request;

/**
 * Controller per TR-111 Parameter Discovery API
 * Controller for TR-111 Parameter Discovery API
 * 
 * Gestisce API per il discovery dinamico dei parametri
 * Handles API for dynamic parameter discovery
 */
class ParameterDiscoveryController extends Controller
{
    use ApiResponse;
    protected $discoveryService;

    public function __construct(ParameterDiscoveryService $discoveryService)
    {
        $this->discoveryService = $discoveryService;
    }

    /**
     * Avvia discovery parametri su dispositivo CPE
     * Start parameter discovery on CPE device
     * 
     * POST /api/v1/devices/{device}/discover-parameters
     * 
     * @param Request $request
     * @param CpeDevice $device
     * @return \Illuminate\Http\JsonResponse
     */
    public function discoverParameters(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'parameter_path' => 'nullable|string|max:500',
            'next_level_only' => 'boolean'
        ]);

        try {
            $task = $this->discoveryService->discoverParameters(
                $device,
                $validated['parameter_path'] ?? null,
                $validated['next_level_only'] ?? true
            );

            return response()->json([
                'success' => true,
                'message' => 'Parameter discovery initiated',
                'task' => $task,
                'device' => [
                    'id' => $device->id,
                    'serial_number' => $device->serial_number,
                    'model' => $device->model_name
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate parameter discovery: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ottiene capabilities scoperte per dispositivo
     * Get discovered capabilities for device
     * 
     * GET /api/v1/devices/{device}/capabilities
     * 
     * @param Request $request
     * @param CpeDevice $device
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCapabilities(Request $request, CpeDevice $device)
    {
        $filter = $request->input('filter');
        $format = $request->input('format', 'list'); // list | tree

        $query = $device->deviceCapabilities();

        // Apply filters
        if ($filter === 'writable') {
            $query->writable();
        } elseif ($filter === 'vendor_specific') {
            $query->vendorSpecific();
        } elseif ($filter === 'standard') {
            $query->standard();
        }

        if ($request->has('path_like')) {
            $query->pathLike($request->input('path_like'));
        }

        // Apply root_path filter for tree format
        $rootPath = $request->input('root_path');
        if ($rootPath) {
            $query->where('parameter_path', 'LIKE', $rootPath . '%');
        }

        // Pagination
        $perPage = $request->input('per_page', 50);
        
        if ($format === 'tree') {
            // Build tree structure from filtered capabilities
            $capabilities = $query->get();
            
            // Build tree using buildTreeFromCapabilities method with filtered data
            $tree = $this->discoveryService->buildTreeFromCapabilities($capabilities);
            
            return response()->json([
                'format' => 'tree',
                'device_id' => $device->id,
                'root_path' => $rootPath ?? null,
                'tree' => $tree,
                'total_capabilities' => $capabilities->count(),
                'applied_filters' => [
                    'filter' => $filter,
                    'path_like' => $request->input('path_like'),
                    'root_path' => $rootPath
                ]
            ]);
        }

        // List format with pagination
        $capabilities = $query->orderBy('parameter_path')->paginate($perPage);

        return response()->json([
            'format' => 'list',
            'device_id' => $device->id,
            'capabilities' => $capabilities->items(),
            'pagination' => [
                'current_page' => $capabilities->currentPage(),
                'per_page' => $capabilities->perPage(),
                'total' => $capabilities->total(),
                'last_page' => $capabilities->lastPage()
            ]
        ]);
    }

    /**
     * Ottiene statistiche discovery per dispositivo
     * Get discovery statistics for device
     * 
     * GET /api/v1/devices/{device}/capabilities/stats
     * 
     * @param CpeDevice $device
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(CpeDevice $device)
    {
        $stats = $this->discoveryService->getDiscoveryStats($device);

        return response()->json([
            'device_id' => $device->id,
            'serial_number' => $device->serial_number,
            'stats' => $stats
        ]);
    }

    /**
     * Ottiene capability specifica per path
     * Get specific capability by path
     * 
     * GET /api/v1/devices/{device}/capabilities/path
     * 
     * @param Request $request
     * @param CpeDevice $device
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCapabilityByPath(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'path' => 'required|string|max:500'
        ]);

        $capability = $device->deviceCapabilities()
            ->where('parameter_path', $validated['path'])
            ->first();

        if (!$capability) {
            return response()->json([
                'success' => false,
                'message' => 'Capability not found for path: ' . $validated['path']
            ], 404);
        }

        return response()->json([
            'success' => true,
            'capability' => $capability,
            'parent_path' => $capability->getParentPath(),
            'is_leaf' => $capability->isLeafParameter(),
            'needs_verification' => $capability->needsVerification()
        ]);
    }
}
