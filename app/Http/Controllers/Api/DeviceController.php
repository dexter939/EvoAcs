<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CpeDevice;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $query = CpeDevice::with('configurationProfile');
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('manufacturer')) {
            $query->where('manufacturer', $request->manufacturer);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%")
                  ->orWhere('model_name', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }
        
        $devices = $query->paginate($request->get('per_page', 50));
        
        return response()->json($devices);
    }
    
    public function show(CpeDevice $device)
    {
        $device->load(['configurationProfile', 'parameters', 'provisioningTasks', 'firmwareDeployments']);
        return response()->json($device);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'serial_number' => 'required|unique:cpe_devices',
            'oui' => 'required',
            'manufacturer' => 'nullable|string',
            'model_name' => 'nullable|string',
            'configuration_profile_id' => 'nullable|exists:configuration_profiles,id'
        ]);
        
        $device = CpeDevice::create($validated);
        return response()->json($device, 201);
    }
    
    public function update(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'configuration_profile_id' => 'nullable|exists:configuration_profiles,id',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);
        
        $device->update($validated);
        return response()->json($device);
    }
    
    public function destroy(CpeDevice $device)
    {
        $device->delete();
        return response()->json(['message' => 'Device deleted successfully']);
    }
}
