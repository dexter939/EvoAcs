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
    
    /**
     * Crea nuovo profilo configurazione
     * Create new configuration profile
     */
    public function storeProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parameters' => 'required|json',
            'is_active' => 'nullable|boolean',
        ]);
        
        $validated['parameters'] = json_decode($validated['parameters'], true);
        $validated['is_active'] = $request->has('is_active');
        
        ConfigurationProfile::create($validated);
        
        return redirect()->route('acs.profiles')->with('success', 'Profilo creato con successo');
    }
    
    /**
     * Aggiorna profilo configurazione
     * Update configuration profile
     */
    public function updateProfile(Request $request, $id)
    {
        $profile = ConfigurationProfile::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parameters' => 'required|json',
            'is_active' => 'nullable|boolean',
        ]);
        
        $validated['parameters'] = json_decode($validated['parameters'], true);
        $validated['is_active'] = $request->has('is_active');
        
        $profile->update($validated);
        
        return redirect()->route('acs.profiles')->with('success', 'Profilo aggiornato con successo');
    }
    
    /**
     * Elimina profilo configurazione
     * Delete configuration profile
     */
    public function destroyProfile($id)
    {
        $profile = ConfigurationProfile::findOrFail($id);
        $profile->delete();
        
        return redirect()->route('acs.profiles')->with('success', 'Profilo eliminato con successo');
    }
    
    public function uploadFirmware(Request $request)
    {
        $validated = $request->validate([
            'manufacturer' => 'required|string',
            'model' => 'required|string',
            'version' => 'required|string',
            'firmware_file' => 'nullable|file',
            'download_url' => 'nullable|url',
            'is_stable' => 'nullable|boolean',
        ]);
        
        if (!$request->hasFile('firmware_file') && empty($validated['download_url'])) {
            return redirect()->back()->withErrors([
                'firmware_file' => 'Devi fornire almeno un file firmware o un URL di download.'
            ])->withInput();
        }
        
        $filename = null;
        $file_hash = null;
        
        if ($request->hasFile('firmware_file')) {
            $file = $request->file('firmware_file');
            $filename = $file->getClientOriginalName();
            $file_hash = hash_file('sha256', $file->path());
            $file->storeAs('firmware', $filename, 'public');
        }
        
        FirmwareVersion::create([
            'manufacturer' => $validated['manufacturer'],
            'model' => $validated['model'],
            'version' => $validated['version'],
            'filename' => $filename,
            'file_hash' => $file_hash,
            'download_url' => $validated['download_url'] ?? null,
            'is_stable' => $request->has('is_stable'),
            'is_active' => true,
        ]);
        
        return redirect()->route('acs.firmware')->with('success', 'Firmware caricato con successo');
    }
    
    public function deployFirmware(Request $request, $id)
    {
        $firmware = FirmwareVersion::findOrFail($id);
        
        $validated = $request->validate([
            'device_ids' => 'required|array',
            'device_ids.*' => 'exists:cpe_devices,id',
            'scheduled_at' => 'nullable|date',
        ]);
        
        foreach ($validated['device_ids'] as $deviceId) {
            FirmwareDeployment::create([
                'firmware_version_id' => $firmware->id,
                'cpe_device_id' => $deviceId,
                'status' => 'scheduled',
                'scheduled_at' => $validated['scheduled_at'] ?? now(),
            ]);
        }
        
        return redirect()->route('acs.firmware')->with('success', 'Deploy firmware avviato per ' . count($validated['device_ids']) . ' dispositivi');
    }
    
    public function provisionDevice(Request $request, $id)
    {
        $device = CpeDevice::findOrFail($id);
        
        $validated = $request->validate([
            'profile_id' => 'required|exists:configuration_profiles,id',
        ]);
        
        ProvisioningTask::create([
            'cpe_device_id' => $device->id,
            'task_type' => 'set_parameters',
            'parameters' => ['profile_id' => $validated['profile_id']],
            'status' => 'pending',
            'max_retries' => 3,
        ]);
        
        return redirect()->route('acs.devices')->with('success', 'Task di provisioning creato per ' . $device->serial_number);
    }
    
    public function rebootDevice($id)
    {
        $device = CpeDevice::findOrFail($id);
        
        ProvisioningTask::create([
            'cpe_device_id' => $device->id,
            'task_type' => 'reboot',
            'parameters' => [],
            'status' => 'pending',
            'max_retries' => 3,
        ]);
        
        return redirect()->route('acs.devices')->with('success', 'Comando di reboot inviato a ' . $device->serial_number);
    }
    
    public function showDevice($id)
    {
        $device = CpeDevice::with(['configurationProfile', 'deviceParameters', 'provisioningTasks', 'firmwareDeployments.firmwareVersion'])
            ->findOrFail($id);
        
        return view('acs.device-detail', compact('device'));
    }
}
