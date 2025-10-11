<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpeDevice;
use App\Models\ProvisioningTask;
use App\Services\TR069SessionManager;
use App\Services\TR069Service;
use Carbon\Carbon;

/**
 * TR069Controller - Controller per gestione protocollo TR-069 (CWMP)
 * TR069Controller - Controller for TR-069 (CWMP) protocol management
 * 
 * Gestisce le richieste SOAP dai dispositivi CPE secondo lo standard TR-069
 * Handles SOAP requests from CPE devices according to TR-069 standard
 */
class TR069Controller extends Controller
{
    /**
     * Gestisce le richieste SOAP dai dispositivi CPE (Inform + Responses)
     * Handles SOAP requests from CPE devices (Inform + Responses)
     * 
     * Handler generico che gestisce:
     * - Inform: Registrazione dispositivo e inizio sessione
     * - GetParameterValuesResponse: Risposta a GetParameterValues
     * - SetParameterValuesResponse: Risposta a SetParameterValues
     * - RebootResponse: Conferma reboot
     * - TransferComplete: Conferma download firmware completato
     * 
     * Generic handler that processes:
     * - Inform: Device registration and session start
     * - GetParameterValuesResponse: Response to GetParameterValues
     * - SetParameterValuesResponse: Response to SetParameterValues
     * - RebootResponse: Reboot confirmation
     * - TransferComplete: Firmware download completion
     * 
     * @param Request $request Richiesta HTTP con body SOAP XML / HTTP request with SOAP XML body
     * @return \Illuminate\Http\Response Risposta SOAP / SOAP response
     */
    public function handleInform(Request $request)
    {
        // Ottiene il corpo grezzo della richiesta SOAP
        // Gets raw SOAP request body
        $rawBody = $request->getContent();
        
        \Log::info('TR-069 Inform received', ['body' => $rawBody]);
        
        // Parsa il messaggio XML SOAP
        // Parse SOAP XML message
        $xml = simplexml_load_string($rawBody);
        if (!$xml) {
            return response('Invalid XML', 400);
        }
        
        // Registra i namespace SOAP e CWMP per XPath
        // Register SOAP and CWMP namespaces for XPath
        $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('cwmp', 'urn:dslforum-org:cwmp-1-0');
        
        // Estrae DeviceId e lista parametri dall'Inform
        // Extract DeviceId and parameter list from Inform
        $deviceId = $xml->xpath('//cwmp:DeviceId')[0] ?? null;
        $paramList = $xml->xpath('//cwmp:ParameterValueStruct') ?? [];
        
        // Variabili per ConnectionRequest (comunicazione bidirezionale ACS->CPE)
        // Variables for ConnectionRequest (bidirectional communication ACS->CPE)
        $connectionRequestUrl = null;
        $connectionRequestUsername = null;
        $connectionRequestPassword = null;
        
        // Cerca ConnectionRequestURL nei parametri dell'Inform
        // Search for ConnectionRequestURL in Inform parameters
        foreach ($paramList as $param) {
            $name = (string)($param->Name ?? '');
            $value = (string)($param->Value ?? '');
            
            if (str_contains($name, 'ConnectionRequestURL')) {
                $connectionRequestUrl = $value;
            } elseif (str_contains($name, 'ConnectionRequestUsername')) {
                $connectionRequestUsername = $value;
            } elseif (str_contains($name, 'ConnectionRequestPassword')) {
                $connectionRequestPassword = $value;
            }
        }
        
        // Registra o aggiorna il dispositivo CPE nel database
        // Register or update CPE device in database
        $device = null;
        if ($deviceId) {
            // Estrae identificatori dispositivo
            // Extract device identifiers
            $serialNumber = (string)($deviceId->SerialNumber ?? '');
            $oui = (string)($deviceId->OUI ?? '');
            $productClass = (string)($deviceId->ProductClass ?? '');
            $manufacturer = (string)($deviceId->Manufacturer ?? '');
            
            // Prepara dati per update/create
            // Prepare data for update/create
            $deviceData = [
                'oui' => $oui,
                'product_class' => $productClass,
                'manufacturer' => $manufacturer,
                'ip_address' => $request->ip(),
                'last_inform' => Carbon::now(),
                'last_contact' => Carbon::now(),
                'status' => 'online'
            ];
            
            // Aggiunge ConnectionRequest info se disponibili
            // Add ConnectionRequest info if available
            if ($connectionRequestUrl) {
                $deviceData['connection_request_url'] = $connectionRequestUrl;
            }
            if ($connectionRequestUsername) {
                $deviceData['connection_request_username'] = $connectionRequestUsername;
            }
            if ($connectionRequestPassword) {
                $deviceData['connection_request_password'] = $connectionRequestPassword;
            }
            
            // Crea o aggiorna dispositivo (upsert per serial_number)
            // Create or update device (upsert by serial_number)
            $device = CpeDevice::updateOrCreate(
                ['serial_number' => $serialNumber],
                $deviceData
            );
            
            \Log::info('Device registered/updated', ['serial' => $serialNumber, 'id' => $device->id]);
        }
        
        // SESSION MANAGEMENT: Gestisce sessione TR-069
        // SESSION MANAGEMENT: Handle TR-069 session
        $sessionManager = new TR069SessionManager();
        $cookieValue = $request->cookie('TR069SessionID');
        $session = null;
        
        if ($device) {
            // Ottiene o crea sessione per il dispositivo
            // Get or create session for device
            $session = $sessionManager->getOrCreateSession($device, $cookieValue, $request->ip());
            
            // Cerca task di provisioning in coda per questo dispositivo
            // Search for pending provisioning tasks for this device
            $pendingTasks = ProvisioningTask::where('cpe_device_id', $device->id)
                ->where('status', 'pending')
                ->orderBy('scheduled_at', 'asc')
                ->get();
            
            // Accoda comandi SOAP per ogni task pending nella sessione
            // Queue SOAP commands for each pending task in session
            foreach ($pendingTasks as $task) {
                $this->queueTaskCommands($session, $task);
                $task->update(['status' => 'processing']);
            }
        }
        
        // Genera risposta SOAP
        // Generate SOAP response
        $response = $this->generateSessionResponse($session);
        
        // Restituisce risposta con cookie di sessione
        // Return response with session cookie
        $httpResponse = response($response, 200)
            ->header('Content-Type', 'text/xml; charset=utf-8')
            ->header('SOAPAction', '');
        
        if ($session) {
            $httpResponse->cookie('TR069SessionID', $session->cookie, 5); // 5 minuti
        }
        
        return $httpResponse;
    }
    
    /**
     * Genera la risposta SOAP InformResponse
     * Generates SOAP InformResponse
     * 
     * @return string Messaggio SOAP XML / SOAP XML message
     */
    private function generateInformResponse()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cwmp="urn:dslforum-org:cwmp-1-0">
    <soap:Header>
        <cwmp:ID soap:mustUnderstand="1">1</cwmp:ID>
    </soap:Header>
    <soap:Body>
        <cwmp:InformResponse>
            <MaxEnvelopes>1</MaxEnvelopes>
        </cwmp:InformResponse>
    </soap:Body>
</soap:Envelope>';
    }
    
    /**
     * Accoda comandi SOAP per una task di provisioning nella sessione
     * Queue SOAP commands for provisioning task in session
     * 
     * @param \App\Models\Tr069Session $session Sessione TR-069
     * @param \App\Models\ProvisioningTask $task Task di provisioning
     * @return void
     */
    private function queueTaskCommands($session, $task): void
    {
        $sessionManager = new TR069SessionManager();
        
        switch ($task->task_type) {
            case 'set_parameters':
                $params = $task->task_params['parameters'] ?? [];
                $sessionManager->queueCommand($session, 'SetParameterValues', $params, $task->id);
                break;
                
            case 'get_parameters':
                $params = $task->task_params['parameters'] ?? [];
                $sessionManager->queueCommand($session, 'GetParameterValues', ['parameters' => $params], $task->id);
                break;
                
            case 'reboot':
                $sessionManager->queueCommand($session, 'Reboot', [], $task->id);
                break;
                
            case 'download':
                $params = $task->task_params ?? [];
                $sessionManager->queueCommand($session, 'Download', $params, $task->id);
                break;
        }
    }
    
    /**
     * Genera risposta SOAP basata sulla sessione (comando o InformResponse)
     * Generate SOAP response based on session (command or InformResponse)
     * 
     * @param \App\Models\Tr069Session|null $session Sessione TR-069 (opzionale)
     * @return string Messaggio SOAP XML
     */
    private function generateSessionResponse($session): string
    {
        if (!$session) {
            return $this->generateInformResponse();
        }
        
        $sessionManager = new TR069SessionManager();
        
        // Se ci sono comandi pendenti, invia il prossimo comando
        // If there are pending commands, send next command
        if ($sessionManager->hasPendingCommands($session)) {
            $command = $sessionManager->getNextCommand($session);
            
            if ($command) {
                \Log::info('TR-069 sending command to device', [
                    'session_id' => $session->session_id,
                    'command_type' => $command['type'],
                    'task_id' => $command['task_id'] ?? null
                ]);
                
                $tr069Service = new TR069Service();
                $messageId = $session->getNextMessageId();
                
                switch ($command['type']) {
                    case 'GetParameterValues':
                        return $tr069Service->generateGetParameterValues(
                            $command['params']['parameters'] ?? [],
                            $messageId
                        );
                        
                    case 'SetParameterValues':
                        return $tr069Service->generateSetParameterValues(
                            $command['params'],
                            $messageId
                        );
                        
                    case 'Reboot':
                        return $tr069Service->generateReboot($messageId);
                        
                    case 'Download':
                        return $tr069Service->generateDownload(
                            $command['params']['url'] ?? '',
                            $command['params']['file_type'] ?? '1 Firmware Upgrade Image',
                            $command['params']['file_size'] ?? 0,
                            $messageId
                        );
                        
                    default:
                        \Log::warning('TR-069 unknown command type', ['type' => $command['type']]);
                        return $this->generateInformResponse();
                }
            }
        }
        
        // Nessun comando pendente, restituisce InformResponse
        // No pending commands, return InformResponse
        return $this->generateInformResponse();
    }
    
    /**
     * Gestisce richieste vuote dal dispositivo (empty POST)
     * Handles empty requests from device (empty POST)
     * 
     * Richiesta vuota indica la fine della sessione TR-069.
     * Empty request indicates end of TR-069 session.
     * 
     * @param Request $request Richiesta HTTP / HTTP request
     * @return \Illuminate\Http\Response Envelope SOAP vuoto / Empty SOAP envelope
     */
    public function handleEmpty(Request $request)
    {
        \Log::info('TR-069 Empty request received');
        
        // Chiude la sessione se esiste
        // Close session if exists
        $cookieValue = $request->cookie('TR069SessionID');
        if ($cookieValue) {
            $sessionManager = new TR069SessionManager();
            $session = $sessionManager->getSessionByCookie($cookieValue);
            
            if ($session) {
                $sessionManager->closeSession($session);
            }
        }
        
        return response('<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body></soap:Body></soap:Envelope>', 200)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }
}
