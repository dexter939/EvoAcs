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
     * Lista versioni firmware attive
     * List active firmware versions
     * 
     * @return \Illuminate\Http\JsonResponse Lista firmware paginata / Paginated firmware list
     */
    public function index()
    {
        // Query solo firmware attivi, ordinati per data (più recenti prima)
        // Query only active firmware, ordered by date (most recent first)
        $firmware = FirmwareVersion::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        
        return $this->paginatedResponse($firmware);
    }
    
    /**
     * Carica nuova versione firmware
     * Upload new firmware version
     * 
     * Richiede file_hash e file_size per integrità e validazione
     * Requires file_hash and file_size for integrity and validation
     * 
     * @param Request $request Dati firmware (version, manufacturer, model, file_path, hash) / Firmware data
     * @return \Illuminate\Http\JsonResponse Firmware creato / Created firmware
     */
    public function store(Request $request)
    {
        // Validazione campi obbligatori firmware
        // Validate required firmware fields
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
        
        $deployments = [];
        
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
        }
        
        return $this->successResponse('Firmware deployment scheduled and queued', $deployments);
    }
}
