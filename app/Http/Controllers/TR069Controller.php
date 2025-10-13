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
        
        \Log::info('TR-069 SOAP request received');
        
        // Parsa il messaggio XML SOAP usando DOMDocument (carrier-grade namespace support)
        // Parse SOAP XML message using DOMDocument (carrier-grade namespace support)
        $dom = new \DOMDocument();
        if (!$dom->loadXML($rawBody)) {
            return response('Invalid XML', 400);
        }
        
        // Crea XPath con namespace SOAP e CWMP registrati
        // Create XPath with SOAP and CWMP namespaces registered
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xpath->registerNamespace('cwmp', 'urn:dslforum-org:cwmp-1-0');
        
        // Determina tipo di messaggio (Inform o Response)
        // Determine message type (Inform or Response)
        $messageType = $this->detectMessageType($xpath);
        
        \Log::info('TR-069 message type detected', ['type' => $messageType]);
        
        // Se è una risposta, gestisce separatamente
        // If it's a response, handle separately
        if ($messageType !== 'Inform') {
            return $this->handleResponse($request, $xpath);
        }
        
        // È un Inform, processa normalmente
        // It's an Inform, process normally
        $deviceIdNodes = $xpath->query('//cwmp:DeviceId');
        $deviceId = $deviceIdNodes->length > 0 ? $deviceIdNodes->item(0) : null;
        // ParameterValueStruct può essere con o senza namespace
        $paramList = $xpath->query('//cwmp:ParameterValueStruct | //ParameterValueStruct');
        
        // Variabili per ConnectionRequest (comunicazione bidirezionale ACS->CPE)
        // Variables for ConnectionRequest (bidirectional communication ACS->CPE)
        $connectionRequestUrl = null;
        $connectionRequestUsername = null;
        $connectionRequestPassword = null;
        
        // Cerca ConnectionRequestURL nei parametri dell'Inform
        // Search for ConnectionRequestURL in Inform parameters
        for ($i = 0; $i < $paramList->length; $i++) {
            $param = $paramList->item($i);
            $nameNodes = $param->getElementsByTagName('Name');
            $valueNodes = $param->getElementsByTagName('Value');
            
            $name = $nameNodes->length > 0 ? $nameNodes->item(0)->textContent : '';
            $value = $valueNodes->length > 0 ? $valueNodes->item(0)->textContent : '';
            
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
            // Estrae identificatori dispositivo usando DOMDocument
            // Extract device identifiers using DOMDocument
            $serialNodes = $deviceId->getElementsByTagName('SerialNumber');
            $ouiNodes = $deviceId->getElementsByTagName('OUI');
            $productClassNodes = $deviceId->getElementsByTagName('ProductClass');
            $manufacturerNodes = $deviceId->getElementsByTagName('Manufacturer');
            
            $serialNumber = $serialNodes->length > 0 ? $serialNodes->item(0)->textContent : '';
            $oui = $ouiNodes->length > 0 ? $ouiNodes->item(0)->textContent : '';
            $productClass = $productClassNodes->length > 0 ? $productClassNodes->item(0)->textContent : '';
            $manufacturer = $manufacturerNodes->length > 0 ? $manufacturerNodes->item(0)->textContent : '';
            
            // Estrae SoftwareVersion e HardwareVersion se presenti
            $softwareVersionNodes = $deviceId->getElementsByTagName('SoftwareVersion');
            $hardwareVersionNodes = $deviceId->getElementsByTagName('HardwareVersion');
            $softwareVersion = $softwareVersionNodes->length > 0 ? $softwareVersionNodes->item(0)->textContent : null;
            $hardwareVersion = $hardwareVersionNodes->length > 0 ? $hardwareVersionNodes->item(0)->textContent : null;
            
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
            
            if ($softwareVersion) {
                $deviceData['software_version'] = $softwareVersion;
            }
            if ($hardwareVersion) {
                $deviceData['hardware_version'] = $hardwareVersion;
            }
            
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
            
            // Salva parametri dell'Inform in device_parameters
            // Save Inform parameters to device_parameters
            if ($paramList->length > 0) {
                for ($i = 0; $i < $paramList->length; $i++) {
                    $param = $paramList->item($i);
                    $nameNodes = $param->getElementsByTagName('Name');
                    $valueNodes = $param->getElementsByTagName('Value');
                    
                    if ($nameNodes->length > 0 && $valueNodes->length > 0) {
                        $paramName = $nameNodes->item(0)->textContent;
                        $paramValue = $valueNodes->item(0)->textContent;
                        
                        // Salva o aggiorna parametro
                        \DB::table('device_parameters')->updateOrInsert(
                            [
                                'cpe_device_id' => $device->id,
                                'parameter_path' => $paramName
                            ],
                            [
                                'parameter_value' => $paramValue,
                                'last_updated' => Carbon::now()
                            ]
                        );
                    }
                }
                
                \Log::info('Parameters saved', ['device_id' => $device->id, 'count' => $paramList->length]);
            }
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
                // Usa task_id come CommandKey per correlazione deterministica TransferComplete
                // Use task_id as CommandKey for deterministic TransferComplete correlation
                $params['command_key'] = 'task_' . $task->id;
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
                        return $tr069Service->generateDownloadRequest(
                            $command['params']['url'] ?? '',
                            $command['params']['file_type'] ?? '1 Firmware Upgrade Image',
                            $command['params']['file_size'] ?? 0,
                            $messageId,
                            $command['params']['command_key'] ?? ''
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
    
    /**
     * Gestisce risposte SOAP dai dispositivi CPE
     * Handles SOAP responses from CPE devices
     * 
     * Gestisce risposte a comandi TR-069:
     * - GetParameterValuesResponse
     * - SetParameterValuesResponse  
     * - RebootResponse
     * - TransferComplete
     * 
     * Handles TR-069 command responses:
     * - GetParameterValuesResponse
     * - SetParameterValuesResponse
     * - RebootResponse
     * - TransferComplete
     * 
     * @param Request $request Richiesta HTTP
     * @param \DOMXPath $xpath XPath object with registered namespaces
     * @return \Illuminate\Http\Response Risposta SOAP
     */
    private function handleResponse(Request $request, $xpath)
    {
        \Log::info('TR-069 Response processing');
        
        // Recupera sessione tramite cookie
        $cookieValue = $request->cookie('TR069SessionID');
        $sessionManager = new TR069SessionManager();
        $session = $cookieValue ? $sessionManager->getSessionByCookie($cookieValue) : null;
        
        // Se non c'è sessione tramite cookie (es. TransferComplete in nuova connessione),
        // cerca dispositivo tramite DeviceId per correlazione alternativa
        // If no session via cookie (e.g., TransferComplete in new connection),
        // find device via DeviceId for alternative correlation
        $device = null;
        if (!$session) {
            $deviceIdNodes = $xpath->query('//cwmp:DeviceId');
            $deviceId = $deviceIdNodes->length > 0 ? $deviceIdNodes->item(0) : null;
            
            if ($deviceId) {
                $serialNodes = $deviceId->getElementsByTagName('SerialNumber');
                $serialNumber = $serialNodes->length > 0 ? $serialNodes->item(0)->textContent : '';
                $device = CpeDevice::where('serial_number', $serialNumber)->first();
                
                if ($device) {
                    \Log::info('TR-069 Response correlated via DeviceId', ['serial_number' => $serialNumber]);
                    
                    // Crea nuova sessione temporanea per processare la risposta
                    // Create temporary session to process response
                    $session = $sessionManager->createSession($device, $request->ip());
                }
            }
        }
        
        if (!$session) {
            \Log::warning('TR-069 Response without valid session or device correlation');
            return response('No active session', 400);
        }
        
        // Determina tipo di risposta
        $responseType = $this->detectResponseType($xpath);
        
        \Log::info('TR-069 response type detected', ['type' => $responseType]);
        
        // Gestisce risposta in base al tipo (TODO: migrate these handlers to DOMXPath)
        switch ($responseType) {
            case 'GetParameterValuesResponse':
                $this->handleGetParameterValuesResponse($xpath, $session);
                break;
                
            case 'SetParameterValuesResponse':
                $this->handleSetParameterValuesResponse($xpath, $session);
                break;
                
            case 'RebootResponse':
                $this->handleRebootResponse($xpath, $session);
                break;
                
            case 'TransferComplete':
                $this->handleTransferCompleteResponse($xpath, $session);
                break;
                
            default:
                \Log::warning('TR-069 unknown response type');
                break;
        }
        
        // Genera risposta successiva
        $response = $this->generateSessionResponse($session);
        
        return response($response, 200)
            ->header('Content-Type', 'text/xml; charset=utf-8')
            ->header('SOAPAction', '')
            ->cookie('TR069SessionID', $session->cookie, 5);
    }
    
    /**
     * Rileva tipo di messaggio SOAP (Inform o Response)
     * Detect SOAP message type (Inform or Response)
     * 
     * @param \DOMXPath $xpath XPath object with registered namespaces
     * @return string Tipo messaggio
     */
    private function detectMessageType($xpath): string
    {
        // Prima verifica se è un Inform
        if ($xpath->query('//cwmp:Inform')->length > 0) {
            return 'Inform';
        }
        
        // Altrimenti verifica i vari tipi di risposta
        return $this->detectResponseType($xpath);
    }
    
    /**
     * Rileva tipo di risposta SOAP
     * Detect SOAP response type
     * 
     * @param \DOMXPath $xpath XPath object with registered namespaces
     * @return string Tipo risposta
     */
    private function detectResponseType($xpath): string
    {
        if ($xpath->query('//cwmp:GetParameterValuesResponse')->length > 0) {
            return 'GetParameterValuesResponse';
        } elseif ($xpath->query('//cwmp:SetParameterValuesResponse')->length > 0) {
            return 'SetParameterValuesResponse';
        } elseif ($xpath->query('//cwmp:RebootResponse')->length > 0) {
            return 'RebootResponse';
        } elseif ($xpath->query('//cwmp:TransferComplete')->length > 0) {
            return 'TransferComplete';
        } elseif ($xpath->query('//cwmp:DownloadResponse')->length > 0) {
            return 'DownloadResponse';
        }
        
        return 'Unknown';
    }
    
    /**
     * Gestisce GetParameterValuesResponse
     * Handle GetParameterValuesResponse
     * 
     * @param \SimpleXMLElement $xml Documento XML
     * @param \App\Models\Tr069Session $session Sessione corrente
     * @return void
     */
    private function handleGetParameterValuesResponse($xml, $session): void
    {
        // Estrae parametri dalla risposta
        $paramList = $xml->xpath('//cwmp:ParameterValueStruct') ?? [];
        $parameters = [];
        
        foreach ($paramList as $param) {
            $name = (string)($param->Name ?? '');
            $value = (string)($param->Value ?? '');
            $parameters[$name] = $value;
        }
        
        \Log::info('TR-069 GetParameterValues response parsed', ['parameters' => $parameters]);
        
        // Aggiorna task associata se presente
        if ($session->last_command_sent && isset($session->last_command_sent['task_id'])) {
            $task = ProvisioningTask::find($session->last_command_sent['task_id']);
            
            if ($task) {
                $task->update([
                    'status' => 'completed',
                    'result' => [
                        'success' => true,
                        'parameters' => $parameters,
                        'completed_at' => now()->toIso8601String()
                    ]
                ]);
                
                \Log::info('TR-069 Task completed', ['task_id' => $task->id]);
            }
        }
    }
    
    /**
     * Gestisce SetParameterValuesResponse
     * Handle SetParameterValuesResponse
     * 
     * @param \SimpleXMLElement $xml Documento XML
     * @param \App\Models\Tr069Session $session Sessione corrente
     * @return void
     */
    private function handleSetParameterValuesResponse($xml, $session): void
    {
        // Estrae status dalla risposta
        $status = $xml->xpath('//cwmp:Status')[0] ?? null;
        $statusCode = $status ? (int)((string)$status) : 0;
        
        $success = $statusCode === 0; // 0 = successo in TR-069
        
        \Log::info('TR-069 SetParameterValues response', ['status' => $statusCode, 'success' => $success]);
        
        // Aggiorna task associata
        if ($session->last_command_sent && isset($session->last_command_sent['task_id'])) {
            $task = ProvisioningTask::find($session->last_command_sent['task_id']);
            
            if ($task) {
                $task->update([
                    'status' => $success ? 'completed' : 'failed',
                    'result' => [
                        'success' => $success,
                        'status_code' => $statusCode,
                        'completed_at' => now()->toIso8601String()
                    ]
                ]);
                
                \Log::info('TR-069 Task updated', ['task_id' => $task->id, 'status' => $success ? 'completed' : 'failed']);
            }
        }
    }
    
    /**
     * Gestisce RebootResponse
     * Handle RebootResponse
     * 
     * @param \SimpleXMLElement $xml Documento XML
     * @param \App\Models\Tr069Session $session Sessione corrente
     * @return void
     */
    private function handleRebootResponse($xml, $session): void
    {
        \Log::info('TR-069 Reboot response received');
        
        // Aggiorna task associata
        if ($session->last_command_sent && isset($session->last_command_sent['task_id'])) {
            $task = ProvisioningTask::find($session->last_command_sent['task_id']);
            
            if ($task) {
                $task->update([
                    'status' => 'completed',
                    'result' => [
                        'success' => true,
                        'message' => 'Device reboot initiated',
                        'completed_at' => now()->toIso8601String()
                    ]
                ]);
                
                \Log::info('TR-069 Reboot task completed', ['task_id' => $task->id]);
            }
        }
    }
    
    /**
     * Gestisce TransferComplete (callback firmware download)
     * Handle TransferComplete (firmware download callback)
     * 
     * Questo è il callback critico per firmware deployment.
     * Il dispositivo lo invia dopo aver completato il download del firmware.
     * 
     * This is the critical callback for firmware deployment.
     * Device sends it after completing firmware download.
     * 
     * @param \SimpleXMLElement $xml Documento XML
     * @param \App\Models\Tr069Session $session Sessione corrente
     * @return void
     */
    private function handleTransferCompleteResponse($xml, $session): void
    {
        // Estrae informazioni da TransferComplete
        $commandKey = $xml->xpath('//cwmp:CommandKey')[0] ?? null;
        $faultStruct = $xml->xpath('//cwmp:FaultStruct')[0] ?? null;
        $startTime = $xml->xpath('//cwmp:StartTime')[0] ?? null;
        $completeTime = $xml->xpath('//cwmp:CompleteTime')[0] ?? null;
        
        $success = !$faultStruct; // Se non c'è FaultStruct, è successo
        
        $faultCode = null;
        $faultString = null;
        
        if ($faultStruct) {
            $faultCode = (string)($faultStruct->FaultCode ?? '');
            $faultString = (string)($faultStruct->FaultString ?? '');
        }
        
        \Log::info('TR-069 TransferComplete received', [
            'success' => $success,
            'command_key' => (string)$commandKey,
            'fault_code' => $faultCode,
            'fault_string' => $faultString
        ]);
        
        // Trova task da aggiornare tramite CommandKey (metodo deterministico)
        // Find task to update via CommandKey (deterministic method)
        $task = null;
        $commandKeyStr = (string)$commandKey;
        
        // Metodo 1: Via CommandKey (formato: task_<id>)
        // Method 1: Via CommandKey (format: task_<id>)
        if (str_starts_with($commandKeyStr, 'task_')) {
            $taskId = (int)str_replace('task_', '', $commandKeyStr);
            $task = ProvisioningTask::find($taskId);
            
            if ($task) {
                \Log::info('TR-069 TransferComplete task found via CommandKey', [
                    'task_id' => $task->id,
                    'command_key' => $commandKeyStr
                ]);
            }
        }
        
        // Metodo 2: Tramite sessione last_command_sent (fallback)
        // Method 2: Via session last_command_sent (fallback)
        if (!$task && $session->last_command_sent && isset($session->last_command_sent['task_id'])) {
            $task = ProvisioningTask::find($session->last_command_sent['task_id']);
            
            if ($task) {
                \Log::info('TR-069 TransferComplete task found via session', ['task_id' => $task->id]);
            }
        }
        
        // Metodo 3: Cerca task download per dispositivo SOLO se CommandKey è generico (ultimo resort)
        // Method 3: Find download task by device ONLY if CommandKey is generic (last resort)
        if (!$task && $session->cpe_device_id && !str_starts_with($commandKeyStr, 'task_')) {
            $task = ProvisioningTask::where('cpe_device_id', $session->cpe_device_id)
                ->where('task_type', 'download')
                ->whereIn('status', ['processing', 'pending'])
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($task) {
                \Log::warning('TR-069 TransferComplete task found via device fallback (non-deterministic)', [
                    'task_id' => $task->id,
                    'command_key' => $commandKeyStr
                ]);
            }
        }
        
        if ($task) {
            $task->update([
                'status' => $success ? 'completed' : 'failed',
                'result' => [
                    'success' => $success,
                    'command_key' => (string)$commandKey,
                    'start_time' => (string)$startTime,
                    'complete_time' => (string)$completeTime,
                    'fault_code' => $faultCode,
                    'fault_string' => $faultString,
                    'completed_at' => now()->toIso8601String()
                ]
            ]);
            
            // Aggiorna anche FirmwareDeployment se task è di tipo download
            if ($task->task_type === 'download' && isset($task->task_params['deployment_id'])) {
                $deployment = \App\Models\FirmwareDeployment::find($task->task_params['deployment_id']);
                
                if ($deployment) {
                    $deployment->update([
                        'status' => $success ? 'completed' : 'failed',
                        'deployed_at' => now()
                    ]);
                    
                    \Log::info('TR-069 Firmware deployment updated', [
                        'deployment_id' => $deployment->id,
                        'status' => $success ? 'completed' : 'failed'
                    ]);
                }
            }
            
            \Log::info('TR-069 Transfer task completed', [
                'task_id' => $task->id,
                'success' => $success
            ]);
        } else {
            \Log::warning('TR-069 TransferComplete without matching task', [
                'device_id' => $session->cpe_device_id,
                'command_key' => (string)$commandKey
            ]);
        }
    }
}
