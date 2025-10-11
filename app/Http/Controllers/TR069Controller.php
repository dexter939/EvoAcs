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
        
        if ($deviceId) {
            $serialNumber = (string)($deviceId->SerialNumber ?? '');
            $oui = (string)($deviceId->OUI ?? '');
            $productClass = (string)($deviceId->ProductClass ?? '');
            $manufacturer = (string)($deviceId->Manufacturer ?? '');
            
            $device = CpeDevice::updateOrCreate(
                ['serial_number' => $serialNumber],
                [
                    'oui' => $oui,
                    'product_class' => $productClass,
                    'manufacturer' => $manufacturer,
                    'ip_address' => $request->ip(),
                    'last_inform' => Carbon::now(),
                    'last_contact' => Carbon::now(),
                    'status' => 'online'
                ]
            );
            
            \Log::info('Device registered/updated', ['serial' => $serialNumber, 'id' => $device->id]);
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
