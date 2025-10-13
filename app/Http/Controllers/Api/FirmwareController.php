<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Models\FirmwareVersion;
use App\Models\FirmwareDeployment;
use App\Models\CpeDevice;
use Illuminate\Http\Request;

/**
 * FirmwareController - Controller per gestione firmware CPE
 * FirmwareController - Controller for CPE firmware management
 * 
 * Gestisce upload firmware, versionamento e deployment remoto via TR-069 Download
 * Manages firmware upload, versioning and remote deployment via TR-069 Download
 */
class FirmwareController extends Controller
{
    use ApiResponse;
    /**
     * Lista versioni firmware con filtri
     * List firmware versions with filters
     * 
     * Filtri disponibili / Available filters:
     * - model: Filtra per modello / Filter by model
     * - manufacturer: Filtra per produttore / Filter by manufacturer
     * - is_active: Filtra per stato attivo / Filter by active status
     * - is_stable: Filtra per versione stabile / Filter by stable version
     * - per_page: Risultati per pagina (default 50) / Results per page
     * 
     * @param Request $request Richiesta con filtri / Request with filters
     * @return \Illuminate\Http\JsonResponse Lista firmware paginata / Paginated firmware list
     */
    public function index(Request $request)
    {
        $query = FirmwareVersion::query();
        
        // Filtro per modello
        // Filter by model
        if ($request->has('model')) {
            $query->where('model', $request->model);
        }
        
        // Filtro per produttore
        // Filter by manufacturer
        if ($request->has('manufacturer')) {
            $query->where('manufacturer', $request->manufacturer);
        }
        
        // Filtro per stato attivo
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        
        // Filtro per versione stabile
        // Filter by stable version
        if ($request->has('is_stable')) {
            $query->where('is_stable', filter_var($request->is_stable, FILTER_VALIDATE_BOOLEAN));
        }
        
        // Ordinamento per data (più recenti prima)
        // Order by date (most recent first)
        $firmware = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));
        
        return $this->paginatedResponse($firmware);
    }
    
    /**
     * Carica nuova versione firmware
     * Upload new firmware version
     * 
     * Supporta upload file o specifica manuale di file_path/file_hash
     * Supports file upload or manual file_path/file_hash specification
     * 
     * @param Request $request Dati firmware / Firmware data
     * @return \Illuminate\Http\JsonResponse Firmware creato / Created firmware
     */
    public function store(Request $request)
    {
        // Validazione con file upload opzionale
        // Validation with optional file upload
        $validated = $request->validate([
            'version' => 'required|string',
            'manufacturer' => 'nullable|string',
            'model' => 'required|string',
            'file' => 'nullable|file|mimes:bin,img,fw,zip|max:102400', // 100MB max
            'file_path' => 'required_without:file|string',
            'file_hash' => 'required_without:file|string',
            'file_size' => 'required_without:file|integer',
            'release_notes' => 'nullable|string',
            'description' => 'nullable|string',
            'is_stable' => 'boolean'
        ]);
        
        // Se c'è upload file, gestiscilo
        // If file is uploaded, handle it
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('firmware', 'local');
            
            $validated['file_path'] = $path;
            $validated['file_hash'] = hash_file('sha256', $file->getRealPath());
            $validated['file_size'] = $file->getSize();
        }
        
        // Usa description come release_notes se presente
        // Use description as release_notes if present
        if (isset($validated['description']) && !isset($validated['release_notes'])) {
            $validated['release_notes'] = $validated['description'];
        }
        unset($validated['description'], $validated['file']);
        
        // Default manufacturer se non specificato
        // Default manufacturer if not specified
        if (!isset($validated['manufacturer'])) {
            $validated['manufacturer'] = 'Generic';
        }
        
        // Crea record firmware nel database
        // Create firmware record in database
        $firmware = FirmwareVersion::create($validated);
        
        return $this->dataResponse($firmware, 201);
    }
    
    /**
     * Dettagli singola versione firmware
     * Single firmware version details
     * 
     * @param FirmwareVersion $firmware Versione firmware / Firmware version
     * @return \Illuminate\Http\JsonResponse Dettagli firmware / Firmware details
     */
    public function show(FirmwareVersion $firmware)
    {
        return $this->dataResponse($firmware);
    }
    
    /**
     * Aggiorna stato firmware (stable, active)
     * Update firmware status (stable, active)
     * 
     * @param Request $request Campi da aggiornare / Fields to update
     * @param FirmwareVersion $firmware Firmware target / Target firmware
     * @return \Illuminate\Http\JsonResponse Firmware aggiornato / Updated firmware
     */
    public function update(Request $request, FirmwareVersion $firmware)
    {
        // Validazione campi aggiornabili
        // Validate updatable fields
        $validated = $request->validate([
            'is_stable' => 'boolean',
            'is_active' => 'boolean',
            'release_notes' => 'nullable|string'
        ]);
        
        $firmware->update($validated);
        
        return $this->dataResponse($firmware);
    }
    
    /**
     * Elimina versione firmware
     * Delete firmware version
     * 
     * @param FirmwareVersion $firmware Firmware da eliminare / Firmware to delete
     * @return \Illuminate\Http\JsonResponse Conferma eliminazione / Deletion confirmation
     */
    public function destroy(FirmwareVersion $firmware)
    {
        $firmware->delete();
        return response()->json(null, 204);
    }
    
    /**
     * Deploy firmware su dispositivi selezionati
     * Deploy firmware to selected devices
     * 
     * Crea deployment schedulati e dispatcha job asincroni per invio Download TR-069
     * Creates scheduled deployments and dispatches async jobs to send TR-069 Download
     * 
     * @param Request $request Lista device_ids e scheduled_at opzionale / List of device_ids and optional scheduled_at
     * @param FirmwareVersion $firmware Firmware da deployare / Firmware to deploy
     * @return \Illuminate\Http\JsonResponse Deployment creati / Created deployments
     */
    public function deploy(Request $request, FirmwareVersion $firmware)
    {
        // Validazione dispositivi target e schedulazione
        // Validate target devices and scheduling
        $validated = $request->validate([
            'device_ids' => 'required|array',
            'device_ids.*' => 'exists:cpe_devices,id',
            'scheduled_at' => 'nullable|date'
        ]);
        
        // Carica dispositivi target
        // Load target devices
        $devices = CpeDevice::whereIn('id', $validated['device_ids'])->get();
        
        // Validazione: tutti i dispositivi devono essere online
        // Validation: all devices must be online
        $offlineDevices = $devices->where('status', '!=', 'online');
        if ($offlineDevices->count() > 0) {
            return response()->json([
                'message' => 'All devices must be online for deployment',
                'offline_devices' => $offlineDevices->pluck('id')
            ], 422);
        }
        
        // Validazione: modello firmware deve corrispondere al modello dispositivo
        // Validation: firmware model must match device model
        foreach ($devices as $device) {
            if ($device->model_name && $device->model_name !== $firmware->model) {
                return response()->json([
                    'message' => 'Firmware model does not match device model',
                    'device_id' => $device->id,
                    'device_model' => $device->model_name,
                    'firmware_model' => $firmware->model
                ], 422);
            }
        }
        
        $deployments = [];
        $firstDeploymentId = null;
        
        // Crea deployment per ogni dispositivo selezionato
        // Create deployment for each selected device
        foreach ($validated['device_ids'] as $deviceId) {
            $deployment = FirmwareDeployment::create([
                'firmware_version_id' => $firmware->id,
                'cpe_device_id' => $deviceId,
                'status' => 'scheduled',
                'scheduled_at' => $validated['scheduled_at'] ?? now()
            ]);
            
            // Dispatcha job asincrono per processing deployment
            // Dispatch async job for deployment processing
            \App\Jobs\ProcessFirmwareDeployment::dispatch($deployment);
            
            $deployments[] = $deployment;
            
            if ($firstDeploymentId === null) {
                $firstDeploymentId = $deployment->id;
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'deployment_id' => $firstDeploymentId,
                'devices_count' => count($deployments),
                'status' => 'scheduled',
                'deployments' => $deployments
            ]
        ]);
    }
}
