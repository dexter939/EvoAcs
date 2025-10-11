<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CpeDevice;
use App\Models\ProvisioningTask;
use Illuminate\Http\Request;

class ProvisioningController extends Controller
{
    public function provisionDevice(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'profile_id' => 'required|exists:configuration_profiles,id'
        ]);
        
        $profile = \App\Models\ConfigurationProfile::findOrFail($validated['profile_id']);
        
        $device->update(['configuration_profile_id' => $validated['profile_id']]);
        
        $parameters = $profile->parameters ?? [];
        
        $task = ProvisioningTask::create([
            'cpe_device_id' => $device->id,
            'task_type' => 'set_parameters',
            'status' => 'pending',
            'task_data' => ['parameters' => $parameters]
        ]);
        
        \App\Jobs\ProcessProvisioningTask::dispatch($task);
        
        return response()->json(['message' => 'Provisioning task created and queued', 'task' => $task]);
    }
    
    public function getParameters(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'parameters' => 'required|array'
        ]);
        
        $task = ProvisioningTask::create([
            'cpe_device_id' => $device->id,
            'task_type' => 'get_parameters',
            'status' => 'pending',
            'task_data' => ['parameters' => $validated['parameters']]
        ]);
        
        \App\Jobs\ProcessProvisioningTask::dispatch($task);
        
        return response()->json(['message' => 'Get parameters task created and queued', 'task' => $task]);
    }
    
    public function setParameters(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'parameters' => 'required|array'
        ]);
        
        $task = ProvisioningTask::create([
            'cpe_device_id' => $device->id,
            'task_type' => 'set_parameters',
            'status' => 'pending',
            'task_data' => ['parameters' => $validated['parameters']]
        ]);
        
        \App\Jobs\ProcessProvisioningTask::dispatch($task);
        
        return response()->json(['message' => 'Set parameters task created and queued', 'task' => $task]);
    }
    
    public function rebootDevice(CpeDevice $device)
    {
        $task = ProvisioningTask::create([
            'cpe_device_id' => $device->id,
            'task_type' => 'reboot',
            'status' => 'pending',
            'task_data' => []
        ]);
        
        \App\Jobs\ProcessProvisioningTask::dispatch($task);
        
        return response()->json(['message' => 'Reboot task created and queued', 'task' => $task]);
    }
    
    public function listTasks(Request $request)
    {
        $query = ProvisioningTask::with('cpeDevice');
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('device_id')) {
            $query->where('cpe_device_id', $request->device_id);
        }
        
        $tasks = $query->orderBy('created_at', 'desc')->paginate(50);
        
        return response()->json($tasks);
    }
    
    public function getTask(ProvisioningTask $task)
    {
        $task->load('cpeDevice');
        return response()->json($task);
    }
}
