<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CpeDevice;
use App\Models\ProvisioningTask;
use Illuminate\Http\Request;

/**
 * ProvisioningController - Controller per provisioning dispositivi CPE
 * ProvisioningController - Controller for CPE device provisioning
 * 
 * Gestisce operazioni di provisioning zero-touch e configurazione remota via TR-069
 * Manages zero-touch provisioning and remote configuration via TR-069
 */
class ProvisioningController extends Controller
{
    /**
     * Provisioning automatico dispositivo con profilo configurazione
     * Automatic device provisioning with configuration profile
     * 
     * Carica parametri dal profilo e crea task SetParameterValues TR-069
     * Loads parameters from profile and creates SetParameterValues TR-069 task
     * 
     * @param Request $request Richiesta con profile_id / Request with profile_id
     * @param CpeDevice $device Dispositivo target / Target device
     * @return \Illuminate\Http\JsonResponse Task provisioning creata / Provisioning task created
     */
    public function provisionDevice(Request $request, CpeDevice $device)
    {
        // Validazione profilo configurazione richiesto
        // Validate required configuration profile
        $validated = $request->validate([
            'profile_id' => 'required|exists:configuration_profiles,id'
        ]);
        
        // Carica profilo configurazione
        // Load configuration profile
        $profile = \App\Models\ConfigurationProfile::findOrFail($validated['profile_id']);
        
        // Assegna profilo al dispositivo
        // Assign profile to device
        $device->update(['configuration_profile_id' => $validated['profile_id']]);
        
        // Estrae parametri dal profilo (formato JSON con chiave-valore TR-181)
        // Extract parameters from profile (JSON format with TR-181 key-value)
        $parameters = $profile->parameters ?? [];
        
        // Crea task di provisioning con tipo "set_parameters"
        // Create provisioning task with type "set_parameters"
        $task = ProvisioningTask::create([
            'cpe_device_id' => $device->id,
            'task_type' => 'set_parameters',
            'status' => 'pending',
            'task_data' => ['parameters' => $parameters]
        ]);
        
        // Dispatcha job asincrono per elaborazione
        // Dispatch async job for processing
        \App\Jobs\ProcessProvisioningTask::dispatch($task);
        
        return response()->json(['message' => 'Provisioning task created and queued', 'task' => $task]);
    }
    
    /**
     * Richiesta lettura parametri TR-181 dal dispositivo
     * Request to read TR-181 parameters from device
     * 
     * Crea task GetParameterValues TR-069
     * Creates GetParameterValues TR-069 task
     * 
     * @param Request $request Lista parametri da leggere / List of parameters to read
     * @param CpeDevice $device Dispositivo target / Target device
     * @return \Illuminate\Http\JsonResponse Task creata / Created task
     */
    public function getParameters(Request $request, CpeDevice $device)
    {
        // Validazione array parametri richiesti
        // Validate required parameters array
        $validated = $request->validate([
            'parameters' => 'required|array'
        ]);
        
        // Crea task GetParameterValues
        // Create GetParameterValues task
        $task = ProvisioningTask::create([
            'cpe_device_id' => $device->id,
            'task_type' => 'get_parameters',
            'status' => 'pending',
            'task_data' => ['parameters' => $validated['parameters']]
        ]);
        
        // Dispatcha job per invio richiesta SOAP al dispositivo
        // Dispatch job to send SOAP request to device
        \App\Jobs\ProcessProvisioningTask::dispatch($task);
        
        return response()->json(['message' => 'Get parameters task created and queued', 'task' => $task]);
    }
    
    /**
     * Imposta parametri TR-181 sul dispositivo
     * Set TR-181 parameters on device
     * 
     * Crea task SetParameterValues TR-069
     * Creates SetParameterValues TR-069 task
     * 
     * @param Request $request Parametri chiave-valore / Key-value parameters
     * @param CpeDevice $device Dispositivo target / Target device
     * @return \Illuminate\Http\JsonResponse Task creata / Created task
     */
    public function setParameters(Request $request, CpeDevice $device)
    {
        // Validazione array parametri (formato: {"param_name": "value"})
        // Validate parameters array (format: {"param_name": "value"})
        $validated = $request->validate([
            'parameters' => 'required|array'
        ]);
        
        // Crea task SetParameterValues
        // Create SetParameterValues task
        $task = ProvisioningTask::create([
            'cpe_device_id' => $device->id,
            'task_type' => 'set_parameters',
            'status' => 'pending',
            'task_data' => ['parameters' => $validated['parameters']]
        ]);
        
        // Dispatcha job asincrono
        // Dispatch async job
        \App\Jobs\ProcessProvisioningTask::dispatch($task);
        
        return response()->json(['message' => 'Set parameters task created and queued', 'task' => $task]);
    }
    
    /**
     * Reboot remoto dispositivo CPE
     * Remote reboot CPE device
     * 
     * Crea task Reboot TR-069
     * Creates Reboot TR-069 task
     * 
     * @param CpeDevice $device Dispositivo da riavviare / Device to reboot
     * @return \Illuminate\Http\JsonResponse Task creata / Created task
     */
    public function rebootDevice(CpeDevice $device)
    {
        // Crea task di tipo "reboot"
        // Create task of type "reboot"
        $task = ProvisioningTask::create([
            'cpe_device_id' => $device->id,
            'task_type' => 'reboot',
            'status' => 'pending',
            'task_data' => []
        ]);
        
        // Dispatcha job per invio comando Reboot SOAP
        // Dispatch job to send Reboot SOAP command
        \App\Jobs\ProcessProvisioningTask::dispatch($task);
        
        return response()->json(['message' => 'Reboot task created and queued', 'task' => $task]);
    }
    
    /**
     * Lista task di provisioning con filtri
     * List provisioning tasks with filters
     * 
     * @param Request $request Parametri filtro (status, device_id) / Filter parameters (status, device_id)
     * @return \Illuminate\Http\JsonResponse Lista task paginata / Paginated task list
     */
    public function listTasks(Request $request)
    {
        // Query con eager loading dispositivo
        // Query with device eager loading
        $query = ProvisioningTask::with('cpeDevice');
        
        // Filtro per stato task
        // Filter by task status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filtro per dispositivo
        // Filter by device
        if ($request->has('device_id')) {
            $query->where('cpe_device_id', $request->device_id);
        }
        
        // Ordina per data creazione (più recenti prima)
        // Order by creation date (most recent first)
        $tasks = $query->orderBy('created_at', 'desc')->paginate(50);
        
        return response()->json($tasks);
    }
    
    /**
     * Dettagli singola task di provisioning
     * Single provisioning task details
     * 
     * @param ProvisioningTask $task Task da visualizzare / Task to display
     * @return \Illuminate\Http\JsonResponse Dettagli task / Task details
     */
    public function getTask(ProvisioningTask $task)
    {
        // Carica relazione con dispositivo
        // Load device relationship
        $task->load('cpeDevice');
        return response()->json($task);
    }
}
