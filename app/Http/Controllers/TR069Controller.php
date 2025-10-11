<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpeDevice;
use App\Models\ProvisioningTask;
use Carbon\Carbon;

class TR069Controller extends Controller
{
    public function handleInform(Request $request)
    {
        $rawBody = $request->getContent();
        
        \Log::info('TR-069 Inform received', ['body' => $rawBody]);
        
        $xml = simplexml_load_string($rawBody);
        if (!$xml) {
            return response('Invalid XML', 400);
        }
        
        $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('cwmp', 'urn:dslforum-org:cwmp-1-0');
        
        $deviceId = $xml->xpath('//cwmp:DeviceId')[0] ?? null;
        $paramList = $xml->xpath('//cwmp:ParameterValueStruct') ?? [];
        
        $connectionRequestUrl = null;
        $connectionRequestUsername = null;
        $connectionRequestPassword = null;
        
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
        
        if ($deviceId) {
            $serialNumber = (string)($deviceId->SerialNumber ?? '');
            $oui = (string)($deviceId->OUI ?? '');
            $productClass = (string)($deviceId->ProductClass ?? '');
            $manufacturer = (string)($deviceId->Manufacturer ?? '');
            
            $deviceData = [
                'oui' => $oui,
                'product_class' => $productClass,
                'manufacturer' => $manufacturer,
                'ip_address' => $request->ip(),
                'last_inform' => Carbon::now(),
                'last_contact' => Carbon::now(),
                'status' => 'online'
            ];
            
            if ($connectionRequestUrl) {
                $deviceData['connection_request_url'] = $connectionRequestUrl;
            }
            if ($connectionRequestUsername) {
                $deviceData['connection_request_username'] = $connectionRequestUsername;
            }
            if ($connectionRequestPassword) {
                $deviceData['connection_request_password'] = $connectionRequestPassword;
            }
            
            $device = CpeDevice::updateOrCreate(
                ['serial_number' => $serialNumber],
                $deviceData
            );
            
            \Log::info('Device registered/updated', ['serial' => $serialNumber, 'id' => $device->id]);
            
            $pendingTask = ProvisioningTask::where('cpe_device_id', $device->id)
                ->where('status', 'pending')
                ->orderBy('scheduled_at', 'asc')
                ->first();
            
            if ($pendingTask) {
                \App\Jobs\ProcessProvisioningTask::dispatch($pendingTask);
                \Log::info('Pending task dispatched for device', ['device_id' => $device->id, 'task_id' => $pendingTask->id]);
            }
        }
        
        $response = $this->generateInformResponse();
        
        return response($response, 200)
            ->header('Content-Type', 'text/xml; charset=utf-8')
            ->header('SOAPAction', '');
    }
    
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
    
    public function handleEmpty(Request $request)
    {
        \Log::info('TR-069 Empty request received');
        
        return response('<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"></soap:Envelope>', 200)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }
}
