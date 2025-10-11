<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpeDevice;
use App\Models\ProvisioningTask;
use App\Models\FirmwareDeployment;
use App\Models\FirmwareVersion;
use App\Models\ConfigurationProfile;

/**
 * AcsController - Controller per interfaccia web dashboard ACS
 * AcsController - Controller for ACS web dashboard interface
 */
class AcsController extends Controller
{
    /**
     * Dashboard principale con statistiche
     * Main dashboard with statistics
     */
    public function dashboard()
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
        
        return view('acs.dashboard', compact('stats'));
    }
    
    /**
     * Pagina gestione dispositivi CPE
     * CPE devices management page
     */
    public function devices()
    {
        $devices = CpeDevice::with('configurationProfile')
            ->orderBy('last_inform', 'desc')
            ->paginate(25);
        
        return view('acs.devices', compact('devices'));
    }
    
    /**
     * Pagina provisioning
     * Provisioning page
     */
    public function provisioning()
    {
        $devices = CpeDevice::where('status', 'online')->get();
        $profiles = ConfigurationProfile::where('is_active', true)->get();
        $tasks = ProvisioningTask::with('cpeDevice')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('acs.provisioning', compact('devices', 'profiles', 'tasks'));
    }
    
    /**
     * Pagina gestione firmware
     * Firmware management page
     */
    public function firmware()
    {
        $firmwareVersions = FirmwareVersion::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $deployments = FirmwareDeployment::with(['firmwareVersion', 'cpeDevice'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        $devices = CpeDevice::where('status', 'online')->get();
        
        return view('acs.firmware', compact('firmwareVersions', 'deployments', 'devices'));
    }
    
    /**
     * Pagina task queue
     * Task queue page
     */
    public function tasks()
    {
        $tasks = ProvisioningTask::with('cpeDevice')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        
        return view('acs.tasks', compact('tasks'));
    }
    
    /**
     * Pagina profili configurazione
     * Configuration profiles page
     */
    public function profiles()
    {
        $profiles = ConfigurationProfile::orderBy('created_at', 'desc')->get();
        
        return view('acs.profiles', compact('profiles'));
    }
}
