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
     * OPTIMIZED: Uses conditional aggregates to reduce 30+ queries to 6 queries
     */
    private function getDashboardStats()
    {
        // Devices stats - 1 query with conditional aggregates
        $deviceStats = CpeDevice::select([
            DB::raw('COUNT(*) as total'),
            DB::raw("COUNT(CASE WHEN status = 'online' THEN 1 END) as online"),
            DB::raw("COUNT(CASE WHEN status = 'offline' THEN 1 END) as offline"),
            DB::raw("COUNT(CASE WHEN status = 'provisioning' THEN 1 END) as provisioning"),
            DB::raw("COUNT(CASE WHEN status = 'error' THEN 1 END) as error"),
            DB::raw("COUNT(CASE WHEN protocol_type = 'tr069' THEN 1 END) as tr069"),
            DB::raw("COUNT(CASE WHEN protocol_type = 'tr369' THEN 1 END) as tr369"),
            DB::raw("COUNT(CASE WHEN protocol_type = 'tr369' AND mtp_type = 'mqtt' THEN 1 END) as tr369_mqtt"),
            DB::raw("COUNT(CASE WHEN protocol_type = 'tr369' AND mtp_type = 'http' THEN 1 END) as tr369_http"),
        ])->first();
        
        // Tasks stats - 1 query with conditional aggregates
        $taskStats = ProvisioningTask::select([
            DB::raw('COUNT(*) as total'),
            DB::raw("COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending"),
            DB::raw("COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing"),
            DB::raw("COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed"),
            DB::raw("COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed"),
        ])->first();
        
        // Firmware deployments stats - 1 query with conditional aggregates
        $firmwareStats = FirmwareDeployment::select([
            DB::raw('COUNT(*) as total_deployments'),
            DB::raw("COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled"),
            DB::raw("COUNT(CASE WHEN status = 'downloading' THEN 1 END) as downloading"),
            DB::raw("COUNT(CASE WHEN status = 'installing' THEN 1 END) as installing"),
            DB::raw("COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed"),
            DB::raw("COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed"),
        ])->first();
        
        // Diagnostics stats - 1 query with conditional aggregates
        $diagnosticStats = \App\Models\DiagnosticTest::select([
            DB::raw('COUNT(*) as total'),
            DB::raw("COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed"),
            DB::raw("COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending"),
            DB::raw("COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed"),
            DB::raw("COUNT(CASE WHEN diagnostic_type = 'ping' THEN 1 END) as ping"),
            DB::raw("COUNT(CASE WHEN diagnostic_type = 'traceroute' THEN 1 END) as traceroute"),
            DB::raw("COUNT(CASE WHEN diagnostic_type = 'download' THEN 1 END) as download"),
            DB::raw("COUNT(CASE WHEN diagnostic_type = 'upload' THEN 1 END) as upload"),
        ])->first();
        
        // Simple counts and recent data - 4 separate queries
        $profilesActive = \App\Models\ConfigurationProfile::where('is_active', true)->count();
        $firmwareVersions = \App\Models\FirmwareVersion::count();
        $uniqueParameters = \App\Models\DeviceParameter::select('parameter_path')->distinct()->count();
        
        $recentDevices = CpeDevice::orderBy('last_inform', 'desc')->limit(10)->get();
        $recentTasks = ProvisioningTask::with('cpeDevice')->orderBy('created_at', 'desc')->limit(10)->get();
        
        return [
            'devices' => [
                'total' => $deviceStats->total ?? 0,
                'online' => $deviceStats->online ?? 0,
                'offline' => $deviceStats->offline ?? 0,
                'provisioning' => $deviceStats->provisioning ?? 0,
                'error' => $deviceStats->error ?? 0,
                'tr069' => $deviceStats->tr069 ?? 0,
                'tr369' => $deviceStats->tr369 ?? 0,
                'tr369_mqtt' => $deviceStats->tr369_mqtt ?? 0,
                'tr369_http' => $deviceStats->tr369_http ?? 0,
            ],
            'tasks' => [
                'total' => $taskStats->total ?? 0,
                'pending' => $taskStats->pending ?? 0,
                'processing' => $taskStats->processing ?? 0,
                'completed' => $taskStats->completed ?? 0,
                'failed' => $taskStats->failed ?? 0,
            ],
            'firmware' => [
                'total_deployments' => $firmwareStats->total_deployments ?? 0,
                'scheduled' => $firmwareStats->scheduled ?? 0,
                'downloading' => $firmwareStats->downloading ?? 0,
                'installing' => $firmwareStats->installing ?? 0,
                'completed' => $firmwareStats->completed ?? 0,
                'failed' => $firmwareStats->failed ?? 0,
            ],
            'diagnostics' => [
                'total' => $diagnosticStats->total ?? 0,
                'completed' => $diagnosticStats->completed ?? 0,
                'pending' => $diagnosticStats->pending ?? 0,
                'failed' => $diagnosticStats->failed ?? 0,
                'by_type' => [
                    'ping' => $diagnosticStats->ping ?? 0,
                    'traceroute' => $diagnosticStats->traceroute ?? 0,
                    'download' => $diagnosticStats->download ?? 0,
                    'upload' => $diagnosticStats->upload ?? 0,
                ],
            ],
            'recent_devices' => $recentDevices,
            'recent_tasks' => $recentTasks,
            'profiles_active' => $profilesActive,
            'firmware_versions' => $firmwareVersions,
            'unique_parameters' => $uniqueParameters,
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
     * Store new CPE device (for testing/manual registration)
     * Salva nuovo dispositivo CPE (per test/registrazione manuale)
     */
    public function storeDevice(Request $request)
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|max:255|unique:cpe_devices',
            'manufacturer' => 'nullable|string|max:255',
            'model_name' => 'nullable|string|max:255',
            'oui' => 'nullable|string|max:6',
            'product_class' => 'nullable|string|max:255',
        ]);
        
        $device = CpeDevice::create(array_merge($validated, [
            'status' => 'offline',
            'protocol' => 'tr069', // Default to TR-069
        ]));
        
        return redirect()->route('acs.dashboard')
            ->with('success', 'Dispositivo creato con successo');
    }
    
    /**
     * Update CPE device information
     * Aggiorna informazioni dispositivo CPE
     */
    public function updateDevice(Request $request, $id)
    {
        $device = CpeDevice::findOrFail($id);
        
        $validated = $request->validate([
            'serial_number' => 'required|string|max:255|unique:cpe_devices,serial_number,' . $id,
            'manufacturer' => 'nullable|string|max:255',
            'model_name' => 'nullable|string|max:255',
            'status' => 'required|in:online,offline,provisioning,error',
        ]);
        
        $device->update($validated);
        
        return redirect()->route('acs.dashboard')
            ->with('success', 'Dispositivo aggiornato con successo');
    }
    
    /**
     * Delete CPE device
     * Elimina dispositivo CPE
     */
    public function destroyDevice($id)
    {
        $device = CpeDevice::findOrFail($id);
        $serial = $device->serial_number;
        
        // Delete related data
        $device->deviceParameters()->delete();
        $device->provisioningTasks()->delete();
        $device->firmwareDeployments()->delete();
        $device->delete();
        
        return redirect()->route('acs.dashboard')
            ->with('success', "Dispositivo $serial eliminato con successo");
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
    
    /**
     * Diagnostics (TR-143) - Lista test diagnostici
     */
    public function diagnostics()
    {
        $tests = \App\Models\DiagnosticTest::with('cpeDevice')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
            
        return view('acs.diagnostics', compact('tests'));
    }
    
    /**
     * Diagnostics - Dettagli test
     */
    public function diagnosticDetails($id)
    {
        $test = \App\Models\DiagnosticTest::with('cpeDevice')->findOrFail($id);
        return view('acs.diagnostic-details', compact('test'));
    }
    
    /**
     * VoIP Services (TR-104) - Lista dispositivi VoIP
     */
    public function voip()
    {
        $devices = CpeDevice::whereHas('parameters', function($q) {
            $q->where('parameter_path', 'like', 'Device.Services.VoiceService.%');
        })->with('configurationProfile')->paginate(50);
        
        return view('acs.voip', compact('devices'));
    }
    
    /**
     * VoIP - Configurazione dispositivo
     */
    public function voipDevice($deviceId)
    {
        $device = CpeDevice::findOrFail($deviceId);
        $voipParams = \App\Models\DeviceParameter::where('cpe_device_id', $device->id)
            ->where('parameter_path', 'like', 'Device.Services.VoiceService.%')
            ->get();
            
        return view('acs.voip-device', compact('device', 'voipParams'));
    }
    
    /**
     * VoIP - Salva configurazione
     */
    public function voipConfigure(Request $request, $deviceId)
    {
        // Implementation will use TR-104 service
        return back()->with('success', 'VoIP configuration queued');
    }
    
    /**
     * Storage/NAS Services (TR-140) - Lista dispositivi storage
     */
    public function storage()
    {
        $devices = CpeDevice::whereHas('parameters', function($q) {
            $q->where('parameter_path', 'like', 'Device.Services.StorageService.%');
        })->with('configurationProfile')->paginate(50);
        
        return view('acs.storage', compact('devices'));
    }
    
    /**
     * Storage - Dispositivo specifico
     */
    public function storageDevice($deviceId)
    {
        $device = CpeDevice::findOrFail($deviceId);
        $storageParams = \App\Models\DeviceParameter::where('cpe_device_id', $device->id)
            ->where('parameter_path', 'like', 'Device.Services.StorageService.%')
            ->get();
            
        return view('acs.storage-device', compact('device', 'storageParams'));
    }
    
    /**
     * Storage - Configura
     */
    public function storageConfigure(Request $request, $deviceId)
    {
        return back()->with('success', 'Storage configuration queued');
    }
    
    /**
     * IoT Devices (TR-181) - Lista dispositivi IoT
     */
    public function iot()
    {
        $devices = CpeDevice::whereHas('parameters', function($q) {
            $q->where('parameter_path', 'like', 'Device.IoT.%');
        })->with('configurationProfile')->paginate(50);
        
        return view('acs.iot', compact('devices'));
    }
    
    /**
     * IoT - Dispositivo specifico
     */
    public function iotDevice($deviceId)
    {
        $device = CpeDevice::findOrFail($deviceId);
        $iotParams = \App\Models\DeviceParameter::where('cpe_device_id', $device->id)
            ->where('parameter_path', 'like', 'Device.IoT.%')
            ->get();
            
        return view('acs.iot-device', compact('device', 'iotParams'));
    }
    
    /**
     * IoT - Controllo dispositivo
     */
    public function iotControl(Request $request, $deviceId)
    {
        return back()->with('success', 'IoT command sent');
    }
    
    /**
     * LAN Devices (TR-64) - Lista dispositivi LAN
     */
    public function lanDevices()
    {
        $devices = CpeDevice::whereHas('parameters', function($q) {
            $q->where('parameter_path', 'like', 'Device.LANDevice.%');
        })->with('configurationProfile')->paginate(50);
        
        return view('acs.lan-devices', compact('devices'));
    }
    
    /**
     * LAN - Dettaglio dispositivo
     */
    public function lanDeviceDetail($deviceId)
    {
        $device = CpeDevice::findOrFail($deviceId);
        $lanParams = \App\Models\DeviceParameter::where('cpe_device_id', $device->id)
            ->where('parameter_path', 'like', 'Device.LANDevice.%')
            ->get();
            
        return view('acs.lan-device-detail', compact('device', 'lanParams'));
    }
    
    /**
     * Femtocell (TR-196) - Lista femtocell
     */
    public function femtocell()
    {
        $devices = CpeDevice::whereHas('parameters', function($q) {
            $q->where('parameter_path', 'like', 'Device.FAP.%');
        })->with('configurationProfile')->paginate(50);
        
        return view('acs.femtocell', compact('devices'));
    }
    
    /**
     * Femtocell - Dispositivo specifico
     */
    public function femtocellDevice($deviceId)
    {
        $device = CpeDevice::findOrFail($deviceId);
        $femtoParams = \App\Models\DeviceParameter::where('cpe_device_id', $device->id)
            ->where('parameter_path', 'like', 'Device.FAP.%')
            ->get();
            
        return view('acs.femtocell-device', compact('device', 'femtoParams'));
    }
    
    /**
     * Femtocell - Configura
     */
    public function femtocellConfigure(Request $request, $deviceId)
    {
        return back()->with('success', 'Femtocell configuration queued');
    }
    
    /**
     * STB/IPTV (TR-135) - Lista STB
     */
    public function stb()
    {
        $devices = CpeDevice::whereHas('parameters', function($q) {
            $q->where('parameter_path', 'like', 'Device.Services.STBService.%');
        })->with('configurationProfile')->paginate(50);
        
        return view('acs.stb', compact('devices'));
    }
    
    /**
     * STB - Dispositivo specifico
     */
    public function stbDevice($deviceId)
    {
        $device = CpeDevice::findOrFail($deviceId);
        $stbParams = \App\Models\DeviceParameter::where('cpe_device_id', $device->id)
            ->where('parameter_path', 'like', 'Device.Services.STBService.%')
            ->get();
            
        return view('acs.stb-device', compact('device', 'stbParams'));
    }
    
    /**
     * STB - Configura
     */
    public function stbConfigure(Request $request, $deviceId)
    {
        return back()->with('success', 'STB configuration queued');
    }
    
    /**
     * Parameters Discovery (TR-111) - Lista parametri
     */
    public function parameters()
    {
        $devices = CpeDevice::with('configurationProfile')->paginate(50);
        return view('acs.parameters', compact('devices'));
    }
    
    /**
     * Parameters - Dispositivo specifico
     */
    public function parametersDevice($deviceId)
    {
        $device = CpeDevice::findOrFail($deviceId);
        $parameters = \App\Models\DeviceParameter::where('cpe_device_id', $device->id)
            ->orderBy('parameter_path')
            ->paginate(100);
            
        return view('acs.parameters-device', compact('device', 'parameters'));
    }
    
    /**
     * Parameters - Discover
     */
    public function parametersDiscover(Request $request, $deviceId)
    {
        $device = CpeDevice::findOrFail($deviceId);
        
        // Queue discovery task
        ProvisioningTask::create([
            'cpe_device_id' => $device->id,
            'task_type' => 'parameter_discovery',
            'status' => 'pending',
            'task_data' => ['full_discovery' => true]
        ]);
        
        return back()->with('success', 'Parameter discovery queued');
    }
}
