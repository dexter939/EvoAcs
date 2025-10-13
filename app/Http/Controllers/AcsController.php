<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpeDevice;
use App\Models\ProvisioningTask;
use App\Models\FirmwareDeployment;
use App\Models\FirmwareVersion;
use App\Models\ConfigurationProfile;
use App\Models\UspSubscription;
use App\Services\ConnectionRequestService;
use App\Services\UspMessageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $stats = $this->getDashboardStats();
        return view('acs.dashboard', compact('stats'));
    }
    
    /**
     * Get dashboard statistics (helper method for reuse)
     * Ottieni statistiche dashboard (metodo helper per riutilizzo)
     */
    private function getDashboardStats()
    {
        return [
            'devices' => [
                'total' => CpeDevice::count(),
                'online' => CpeDevice::where('status', 'online')->count(),
                'offline' => CpeDevice::where('status', 'offline')->count(),
                'provisioning' => CpeDevice::where('status', 'provisioning')->count(),
                'error' => CpeDevice::where('status', 'error')->count(),
                'tr069' => CpeDevice::tr069()->count(),
                'tr369' => CpeDevice::tr369()->count(),
                'tr369_mqtt' => CpeDevice::uspMqtt()->count(),
                'tr369_http' => CpeDevice::tr369()->where('mtp_type', 'http')->count(),
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
            'diagnostics' => [
                'total' => \App\Models\DiagnosticTest::count(),
                'completed' => \App\Models\DiagnosticTest::where('status', 'completed')->count(),
                'pending' => \App\Models\DiagnosticTest::where('status', 'pending')->count(),
                'failed' => \App\Models\DiagnosticTest::where('status', 'failed')->count(),
                'by_type' => [
                    'ping' => \App\Models\DiagnosticTest::where('diagnostic_type', 'ping')->count(),
                    'traceroute' => \App\Models\DiagnosticTest::where('diagnostic_type', 'traceroute')->count(),
                    'download' => \App\Models\DiagnosticTest::where('diagnostic_type', 'download')->count(),
                    'upload' => \App\Models\DiagnosticTest::where('diagnostic_type', 'upload')->count(),
                ],
            ],
            'profiles_active' => \App\Models\ConfigurationProfile::where('is_active', true)->count(),
            'firmware_versions' => \App\Models\FirmwareVersion::count(),
            'unique_parameters' => \App\Models\DeviceParameter::select('parameter_path')->distinct()->count(),
        ];
    }
    
    /**
     * API endpoint for real-time dashboard stats
     * Endpoint API per statistiche dashboard in real-time
     */
    public function dashboardStatsApi()
    {
        return response()->json($this->getDashboardStats());
    }
    
    /**
     * Pagina gestione dispositivi CPE
     * CPE devices management page
     */
    public function devices(Request $request)
    {
        $query = CpeDevice::with('configurationProfile');
        
        // Filter by protocol type
        if ($request->has('protocol') && $request->protocol !== 'all') {
            if ($request->protocol === 'tr069') {
                $query->tr069();
            } elseif ($request->protocol === 'tr369') {
                $query->tr369();
            }
        }
        
        // Filter by MTP type (for TR-369 devices)
        if ($request->has('mtp_type') && $request->mtp_type !== 'all') {
            $query->where('mtp_type', $request->mtp_type);
        }
        
        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        $devices = $query->orderBy('last_contact_at', 'desc')
            ->paginate(25)
            ->appends($request->all());
        
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

    /**
     * Connection Request - Sveglia dispositivo per iniziare sessione TR-069
     * Connection Request - Wake up device to start TR-069 session
     * 
     * Invia richiesta HTTP alla ConnectionRequestURL del dispositivo.
     * Usato dall'interfaccia web per comunicazione bidirezionale ACS→CPE.
     * 
     * Sends HTTP request to device's ConnectionRequestURL.
     * Used by web interface for bidirectional ACS→CPE communication.
     * 
     * @param int $id ID dispositivo / Device ID
     * @param ConnectionRequestService $service Servizio Connection Request / Connection Request service
     * @return \Illuminate\Http\JsonResponse Risultato JSON / JSON result
     */
    public function connectionRequest($id, ConnectionRequestService $service)
    {
        $device = CpeDevice::findOrFail($id);

        // Verifica se dispositivo supporta Connection Request
        // Check if device supports Connection Request
        if (!$service->isConnectionRequestSupported($device)) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo non ha ConnectionRequestURL configurata',
                'error_code' => 'NOT_SUPPORTED'
            ], 400);
        }

        // Invia Connection Request con test POST fallback
        // Send Connection Request with POST fallback test
        $result = $service->testConnectionRequest($device);

        // Ritorna risultato JSON per AJAX
        // Return JSON result for AJAX
        $statusCode = $result['success'] ? 200 : 500;

        return response()->json($result, $statusCode);
    }

    /**
     * Esegue test diagnostico TR-143 su dispositivo
     * Run TR-143 diagnostic test on device
     * 
     * @param int $id Device ID
     * @param string $type Test type: ping, traceroute, download, upload
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function runDiagnostic($id, $type, Request $request)
    {
        $device = CpeDevice::findOrFail($id);
        
        $validationRules = $this->getDiagnosticValidationRules($type);
        if (!$validationRules) {
            return response()->json(['success' => false, 'message' => 'Tipo diagnostico non valido'], 400);
        }
        
        $validated = $request->validate($validationRules);
        
        try {
            [$diagnostic, $task] = \DB::transaction(function () use ($device, $type, $validated) {
                $diagnostic = \App\Models\DiagnosticTest::create([
                    'cpe_device_id' => $device->id,
                    'diagnostic_type' => $type,
                    'status' => 'pending',
                    'parameters' => $validated,
                    'command_key' => ucfirst($type) . '_' . time()
                ]);

                $task = \App\Models\ProvisioningTask::create([
                    'cpe_device_id' => $device->id,
                    'task_type' => 'diagnostic_' . $type,
                    'status' => 'pending',
                    'parameters' => array_merge(['diagnostic_id' => $diagnostic->id], $validated)
                ]);

                return [$diagnostic, $task];
            });

            \App\Jobs\ProcessProvisioningTask::dispatch($task);

            return response()->json(['success' => true, 'message' => ucfirst($type) . ' test started', 'diagnostic' => $diagnostic, 'task' => $task], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    private function getDiagnosticValidationRules($type)
    {
        $rules = [
            'ping' => ['host' => 'required|string|max:255', 'packets' => 'integer|min:1|max:100', 'timeout' => 'integer|min:100|max:10000', 'size' => 'integer|min:32|max:1500'],
            'traceroute' => ['host' => 'required|string|max:255', 'tries' => 'integer|min:1|max:10', 'timeout' => 'integer|min:100|max:30000', 'max_hops' => 'integer|min:1|max:64'],
            'download' => ['url' => 'required|url|max:500', 'file_size' => 'integer|min:0'],
            'upload' => ['url' => 'required|url|max:500', 'file_size' => 'integer|min:0|max:104857600']
        ];
        return $rules[$type] ?? null;
    }

    /**
     * Ottiene risultati test diagnostico per polling real-time
     * Get diagnostic test results for real-time polling
     * 
     * NOTE: ACS Web Dashboard è trusted admin environment senza auth layer.
     * Device scoping implementato come best practice ma non sostituisce authorization.
     * TODO: Aggiungere auth middleware + user→devices relationship per multi-tenant.
     * 
     * @param int $id Diagnostic test ID
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDiagnosticResults($id, Request $request)
    {
        $deviceId = $request->query('device_id');
        if (!$deviceId) {
            abort(400, 'Device ID required for scoping');
        }
        
        $device = CpeDevice::findOrFail($deviceId);
        
        $diagnostic = $device->diagnosticTests()
            ->where('id', $id)
            ->firstOrFail();
        
        return response()->json([
            'diagnostic' => [
                'id' => $diagnostic->id,
                'diagnostic_type' => $diagnostic->diagnostic_type,
                'status' => $diagnostic->status,
                'error_message' => $diagnostic->error_message
            ],
            'summary' => $diagnostic->getResultsSummary(),
            'duration_seconds' => $diagnostic->duration
        ]);
    }
    
    public function showDevice($id)
    {
        $device = CpeDevice::with(['configurationProfile', 'deviceParameters', 'provisioningTasks', 'firmwareDeployments.firmwareVersion'])
            ->findOrFail($id);
        
        return view('acs.device-detail', compact('device'));
    }
    
    /**
     * Mostra sottoscrizioni eventi per dispositivo USP
     * Show event subscriptions for USP device
     */
    public function subscriptions($id)
    {
        $device = CpeDevice::findOrFail($id);
        
        // Only allow for TR-369 devices
        if ($device->protocol_type !== 'tr369') {
            return redirect()->route('acs.device', $device->id)
                ->with('error', 'Event subscriptions are only available for TR-369 USP devices');
        }
        
        $subscriptions = $device->uspSubscriptions()
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('acs.subscriptions', compact('device', 'subscriptions'));
    }
    
    /**
     * Crea nuova sottoscrizione evento
     * Create new event subscription
     */
    public function storeSubscription(Request $request, $id, UspMessageService $uspService)
    {
        $device = CpeDevice::findOrFail($id);
        
        // Validate TR-369 device
        if ($device->protocol_type !== 'tr369') {
            return back()->with('error', 'Event subscriptions are only available for TR-369 USP devices');
        }
        
        $validated = $request->validate([
            'event_path' => 'required|string',
            'reference_list' => 'nullable|string'
        ]);
        
        // Get notification_retry as boolean (checkbox: checked=1, unchecked=null->false)
        $notificationRetry = $request->boolean('notification_retry', true);
        
        try {
            $msgId = 'web-subscribe-' . Str::random(10);
            $subscriptionId = (string) Str::uuid();
            
            // Parse reference_list from textarea (one path per line)
            $referenceList = [];
            if (!empty($validated['reference_list'])) {
                $referenceList = array_filter(
                    array_map('trim', explode("\n", $validated['reference_list'])),
                    fn($path) => !empty($path)
                );
            }
            
            // Use transaction
            DB::transaction(function () use ($device, $validated, $referenceList, $notificationRetry, $subscriptionId, $msgId, $uspService) {
                // Create subscription record
                $subscription = UspSubscription::create([
                    'cpe_device_id' => $device->id,
                    'subscription_id' => $subscriptionId,
                    'event_path' => $validated['event_path'],
                    'reference_list' => $referenceList,
                    'notification_retry' => $notificationRetry,
                    'is_active' => true
                ]);
                
                // Send subscribe message
                $uspService->sendSubscriptionRequest(
                    $device,
                    $validated['event_path'],
                    $subscriptionId,
                    $referenceList,
                    $notificationRetry,
                    $msgId
                );
            });
            
            return back()->with('success', "Event subscription created successfully (ID: {$subscriptionId})");
            
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create subscription: ' . $e->getMessage());
        }
    }
    
    /**
     * Elimina sottoscrizione evento
     * Delete event subscription
     */
    public function destroySubscription($deviceId, $subscriptionId, UspMessageService $uspService)
    {
        $device = CpeDevice::findOrFail($deviceId);
        $subscription = UspSubscription::where('cpe_device_id', $device->id)
            ->where('id', $subscriptionId)
            ->firstOrFail();
        
        try {
            $msgId = 'web-unsubscribe-' . Str::random(10);
            $objectPath = "Device.LocalAgent.Subscription.{$subscription->subscription_id}.";
            
            // Use transaction
            DB::transaction(function () use ($subscription, $device, $objectPath, $msgId, $uspService) {
                // Mark as inactive
                $subscription->update(['is_active' => false]);
                
                // Send delete message
                $deleteMsg = $uspService->createDeleteMessage([$objectPath], false, $msgId);
                
                // Send via MQTT if available
                if ($device->mtp_type === 'mqtt') {
                    $record = $uspService->wrapInRecord(
                        $deleteMsg,
                        $device->usp_endpoint_id,
                        config('usp.controller_endpoint_id'),
                        '1.3'
                    );
                    
                    $binaryPayload = $uspService->serializeRecord($record);
                    $topic = "usp/agent/{$device->usp_endpoint_id}/request";
                    
                    app(\App\Services\UspMqttService::class)->publish($topic, $binaryPayload);
                }
            });
            
            return back()->with('success', 'Event subscription deleted successfully');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete subscription: ' . $e->getMessage());
        }
    }
}
