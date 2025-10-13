<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Models\CpeDevice;
use Illuminate\Http\Request;

/**
 * DeviceController - Controller per gestione dispositivi CPE via API
 * DeviceController - Controller for CPE device management via API
 * 
 * Fornisce endpoints RESTful per operazioni CRUD sui dispositivi
 * Provides RESTful endpoints for CRUD operations on devices
 */
class DeviceController extends Controller
{
    use ApiResponse;
    /**
     * Lista dispositivi con filtri e paginazione
     * List devices with filters and pagination
     * 
     * Filtri disponibili / Available filters:
     * - status: Filtra per stato dispositivo / Filter by device status
     * - protocol_type: Filtra per tipo protocollo (tr069, tr369) / Filter by protocol type
     * - manufacturer: Filtra per produttore / Filter by manufacturer
     * - search: Ricerca in serial_number, model_name, ip_address / Search in serial_number, model_name, ip_address
     * - per_page: Numero risultati per pagina (default 10) / Results per page (default 10)
     * 
     * @param Request $request Richiesta HTTP con parametri di filtro / HTTP request with filter parameters
     * @return \Illuminate\Http\JsonResponse Lista dispositivi paginata / Paginated device list
     */
    public function index(Request $request)
    {
        // Query con eager loading del profilo configurazione
        // Query with eager loading of configuration profile
        $query = CpeDevice::with('configurationProfile');
        
        // Filtro per stato (online, offline, provisioning, error)
        // Filter by status (online, offline, provisioning, error)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filtro per tipo protocollo (tr069, tr369)
        // Filter by protocol type (tr069, tr369)
        if ($request->has('protocol_type')) {
            $query->where('protocol_type', $request->protocol_type);
        }
        
        // Filtro per produttore
        // Filter by manufacturer
        if ($request->has('manufacturer')) {
            $query->where('manufacturer', $request->manufacturer);
        }
        
        // Ricerca full-text su serial_number, model_name, ip_address
        // Full-text search on serial_number, model_name, ip_address
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%")
                  ->orWhere('model_name', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }
        
        // Paginazione risultati (default 10 per pagina, configurabile via per_page)
        // Paginate results (default 10 per page, configurable via per_page)
        $perPage = $request->get('per_page', 10);
        $devices = $query->paginate($perPage);
        
        return $this->paginatedResponse($devices);
    }
    
    /**
     * Dettagli singolo dispositivo con relazioni
     * Single device details with relationships
     * 
     * @param CpeDevice $device Dispositivo CPE / CPE device
     * @return \Illuminate\Http\JsonResponse Dettagli dispositivo completi / Complete device details
     */
    public function show(CpeDevice $device)
    {
        // Carica tutte le relazioni del dispositivo
        // Load all device relationships
        $device->load(['configurationProfile', 'parameters', 'provisioningTasks', 'firmwareDeployments']);
        return $this->dataResponse($device);
    }
    
    /**
     * Crea nuovo dispositivo manualmente
     * Create new device manually
     * 
     * Permette registrazione manuale dispositivi non ancora connessi via TR-069
     * Allows manual registration of devices not yet connected via TR-069
     * 
     * @param Request $request Dati dispositivo / Device data
     * @return \Illuminate\Http\JsonResponse Dispositivo creato / Created device
     */
    public function store(Request $request)
    {
        // Validazione input richiesti
        // Validate required input
        $validated = $request->validate([
            'serial_number' => 'required|unique:cpe_devices',
            'oui' => 'required',
            'product_class' => 'nullable|string',
            'manufacturer' => 'nullable|string',
            'model_name' => 'nullable|string',
            'protocol_type' => 'nullable|in:tr069,tr369',
            'status' => 'nullable|in:online,offline,provisioning,error',
            'configuration_profile_id' => 'nullable|exists:configuration_profiles,id'
        ]);
        
        $device = CpeDevice::create($validated);
        return $this->dataResponse($device, 201);
    }
    
    /**
     * Aggiorna configurazione dispositivo
     * Update device configuration
     * 
     * @param Request $request Dati da aggiornare / Data to update
     * @param CpeDevice $device Dispositivo target / Target device
     * @return \Illuminate\Http\JsonResponse Dispositivo aggiornato / Updated device
     */
    public function update(Request $request, CpeDevice $device)
    {
        // Validazione campi aggiornabili
        // Validate updatable fields
        $validated = $request->validate([
            'configuration_profile_id' => 'nullable|exists:configuration_profiles,id',
            'manufacturer' => 'nullable|string',
            'model_name' => 'nullable|string',
            'status' => 'nullable|in:online,offline,provisioning,error',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);
        
        $device->update($validated);
        return $this->dataResponse($device);
    }
    
    /**
     * Elimina dispositivo (soft delete)
     * Delete device (soft delete)
     * 
     * @param CpeDevice $device Dispositivo da eliminare / Device to delete
     * @return \Illuminate\Http\JsonResponse Messaggio conferma / Confirmation message
     */
    public function destroy(CpeDevice $device)
    {
        $device->delete();
        return response()->json(null, 204);
    }
}
