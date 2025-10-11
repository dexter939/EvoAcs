<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FirmwareVersion;
use App\Models\FirmwareDeployment;
use App\Models\CpeDevice;
use Illuminate\Http\Request;

class FirmwareController extends Controller
{
    public function index()
    {
        $firmware = FirmwareVersion::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        
        return response()->json($firmware);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string',
            'manufacturer' => 'required|string',
            'model' => 'required|string',
            'file_path' => 'required|string',
            'file_hash' => 'required|string',
            'file_size' => 'required|integer',
            'release_notes' => 'nullable|string',
            'is_stable' => 'boolean'
        ]);
        
        $firmware = FirmwareVersion::create($validated);
        
        return response()->json($firmware, 201);
    }
    
    public function show(FirmwareVersion $firmware)
    {
        return response()->json($firmware);
    }
    
    public function update(Request $request, FirmwareVersion $firmware)
    {
        $validated = $request->validate([
            'is_stable' => 'boolean',
            'is_active' => 'boolean',
            'release_notes' => 'nullable|string'
        ]);
        
        $firmware->update($validated);
        
        return response()->json($firmware);
    }
    
    public function deploy(Request $request, FirmwareVersion $firmware)
    {
        $validated = $request->validate([
            'device_ids' => 'required|array',
            'device_ids.*' => 'exists:cpe_devices,id',
            'scheduled_at' => 'nullable|date'
        ]);
        
        $deployments = [];
        foreach ($validated['device_ids'] as $deviceId) {
            $deployment = FirmwareDeployment::create([
                'firmware_version_id' => $firmware->id,
                'cpe_device_id' => $deviceId,
                'status' => 'scheduled',
                'scheduled_at' => $validated['scheduled_at'] ?? now()
            ]);
            
            \App\Jobs\ProcessFirmwareDeployment::dispatch($deployment);
            
            $deployments[] = $deployment;
        }
        
        return response()->json([
            'message' => 'Firmware deployment scheduled and queued',
            'deployments' => $deployments
        ]);
    }
}
