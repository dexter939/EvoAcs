<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpeDevice;
use App\Models\ProvisioningTask;
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
     * Gestisce le richieste Inform dai dispositivi CPE
     * Handles Inform requests from CPE devices
     * 
     * Questo metodo:
     * - Riceve e parsa il messaggio SOAP Inform dal dispositivo
     * - Estrae le informazioni del dispositivo (DeviceId)
     * - Estrae ConnectionRequestURL per comunicazione bidirezionale
     * - Registra o aggiorna il dispositivo nel database
     * - Dispatcha eventuali task di provisioning in coda
     * - Restituisce InformResponse secondo protocollo TR-069
     * 
     * This method:
     * - Receives and parses SOAP Inform message from device
     * - Extracts device information (DeviceId)
     * - Extracts ConnectionRequestURL for bidirectional communication
     * - Registers or updates device in database
     * - Dispatches any pending provisioning tasks
     * - Returns InformResponse according to TR-069 protocol
     * 
     * @param Request $request Richiesta HTTP con body SOAP XML / HTTP request with SOAP XML body
     * @return \Illuminate\Http\Response Risposta SOAP InformResponse / SOAP InformResponse response
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
            
            // Cerca task di provisioning in coda per questo dispositivo
            // Search for pending provisioning tasks for this device
            $pendingTask = ProvisioningTask::where('cpe_device_id', $device->id)
                ->where('status', 'pending')
                ->orderBy('scheduled_at', 'asc')
                ->first();
            
            // Se esiste una task pending, la dispatcha per elaborazione asincrona
            // If pending task exists, dispatch it for async processing
            if ($pendingTask) {
                \App\Jobs\ProcessProvisioningTask::dispatch($pendingTask);
                \Log::info('Pending task dispatched for device', ['device_id' => $device->id, 'task_id' => $pendingTask->id]);
            }
        }
        
        // Genera e restituisce InformResponse secondo protocollo TR-069
        // Generate and return InformResponse according to TR-069 protocol
        $response = $this->generateInformResponse();
        
        return response($response, 200)
            ->header('Content-Type', 'text/xml; charset=utf-8')
            ->header('SOAPAction', '');
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
     * Gestisce richieste vuote dal dispositivo (empty POST)
     * Handles empty requests from device (empty POST)
     * 
     * @param Request $request Richiesta HTTP / HTTP request
     * @return \Illuminate\Http\Response Envelope SOAP vuoto / Empty SOAP envelope
     */
    public function handleEmpty(Request $request)
    {
        \Log::info('TR-069 Empty request received');
        
        return response('<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"></soap:Envelope>', 200)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }
}
