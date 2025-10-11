<?php

namespace App\Http\Controllers;

use App\Models\CpeDevice;
use App\Models\ProvisioningTask;
use App\Models\FirmwareDeployment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'devices' => [
                'total' => CpeDevice::count(),
                'online' => CpeDevice::where('status', 'online')->count(),
                'offline' => CpeDevice::where('status', 'offline')->count(),
                'provisioning' => CpeDevice::where('status', 'provisioning')->count(),
                'error' => CpeDevice::where('status', 'error')->count(),
            ],
            'tasks' => [
                'total' => ProvisioningTask::count(),
                'pending' => ProvisioningTask::where('status', 'pending')->count(),
                'processing' => ProvisioningTask::where('status', 'processing')->count(),
                'completed' => ProvisioningTask::where('status', 'completed')->count(),
                'failed' => ProvisioningTask::where('status', 'failed')->count(),
            ],
            'firmware' => [
                'total_deployments' => FirmwareDeployment::count(),
                'scheduled' => FirmwareDeployment::where('status', 'scheduled')->count(),
                'downloading' => FirmwareDeployment::where('status', 'downloading')->count(),
                'installing' => FirmwareDeployment::where('status', 'installing')->count(),
                'completed' => FirmwareDeployment::where('status', 'completed')->count(),
                'failed' => FirmwareDeployment::where('status', 'failed')->count(),
            ],
            'recent_devices' => CpeDevice::orderBy('last_inform', 'desc')->limit(10)->get(),
            'recent_tasks' => ProvisioningTask::with('cpeDevice')->orderBy('created_at', 'desc')->limit(10)->get(),
        ];
        
        return response()->json($stats);
    }
}
