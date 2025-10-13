<?php
/**
 * Test Script per TR-069 Inform Message
 * Simula un dispositivo CPE che invia un Inform al server ACS
 */

$acsUrl = 'http://127.0.0.1:5000/tr069';

// Messaggio SOAP Inform secondo TR-069
$soapInform = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" 
               xmlns:cwmp="urn:dslforum-org:cwmp-1-0"
               xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <soap:Header>
    <cwmp:ID soap:mustUnderstand="1">1234567890</cwmp:ID>
  </soap:Header>
  <soap:Body>
    <cwmp:Inform>
      <DeviceId>
        <Manufacturer>TestVendor</Manufacturer>
        <OUI>001122</OUI>
        <ProductClass>CPE-Model-X</ProductClass>
        <SerialNumber>TEST-SN-99999</SerialNumber>
        <SoftwareVersion>1.2.3</SoftwareVersion>
        <HardwareVersion>HW-V1.0</HardwareVersion>
      </DeviceId>
      <Event xsi:type="cwmp:EventStruct">
        <EventCode>0 BOOTSTRAP</EventCode>
        <CommandKey></CommandKey>
      </Event>
      <Event xsi:type="cwmp:EventStruct">
        <EventCode>1 BOOT</EventCode>
        <CommandKey></CommandKey>
      </Event>
      <MaxEnvelopes>1</MaxEnvelopes>
      <CurrentTime>2025-10-13T22:30:00Z</CurrentTime>
      <RetryCount>0</RetryCount>
      <ParameterList>
        <ParameterValueStruct>
          <Name>Device.DeviceInfo.SoftwareVersion</Name>
          <Value xsi:type="xsd:string">1.2.3</Value>
        </ParameterValueStruct>
        <ParameterValueStruct>
          <Name>Device.DeviceInfo.HardwareVersion</Name>
          <Value xsi:type="xsd:string">HW-V1.0</Value>
        </ParameterValueStruct>
        <ParameterValueStruct>
          <Name>Device.ManagementServer.ConnectionRequestURL</Name>
          <Value xsi:type="xsd:string">http://192.168.1.100:7547/tr069</Value>
        </ParameterValueStruct>
        <ParameterValueStruct>
          <Name>Device.ManagementServer.ConnectionRequestUsername</Name>
          <Value xsi:type="xsd:string">cpeadmin</Value>
        </ParameterValueStruct>
        <ParameterValueStruct>
          <Name>Device.ManagementServer.ConnectionRequestPassword</Name>
          <Value xsi:type="xsd:string">cpepassword123</Value>
        </ParameterValueStruct>
        <ParameterValueStruct>
          <Name>Device.DeviceInfo.Manufacturer</Name>
          <Value xsi:type="xsd:string">TestVendor</Value>
        </ParameterValueStruct>
        <ParameterValueStruct>
          <Name>Device.DeviceInfo.ModelName</Name>
          <Value xsi:type="xsd:string">CPE-Model-X</Value>
        </ParameterValueStruct>
        <ParameterValueStruct>
          <Name>Device.LAN.IPAddress</Name>
          <Value xsi:type="xsd:string">192.168.1.100</Value>
        </ParameterValueStruct>
        <ParameterValueStruct>
          <Name>Device.WiFi.SSID.1.SSID</Name>
          <Value xsi:type="xsd:string">TestNetwork5G</Value>
        </ParameterValueStruct>
        <ParameterValueStruct>
          <Name>Device.WiFi.Radio.1.Channel</Name>
          <Value xsi:type="xsd:int">36</Value>
        </ParameterValueStruct>
      </ParameterList>
    </cwmp:Inform>
  </soap:Body>
</soap:Envelope>
XML;

echo "📡 Test TR-069 Inform\n";
echo "====================\n\n";

echo "🎯 Target ACS URL: $acsUrl\n";
echo "📦 Serial Number: TEST-SN-99999\n";
echo "🏭 Manufacturer: TestVendor\n";
echo "📱 Model: CPE-Model-X\n\n";

// Inizializza cURL
$ch = curl_init($acsUrl);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $soapInform,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: text/xml; charset=utf-8',
        'SOAPAction: ""'
    ],
    CURLOPT_HEADER => true,
    CURLOPT_VERBOSE => false
]);

echo "🚀 Invio Inform al server ACS...\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

if ($response === false) {
    echo "❌ Errore cURL: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

curl_close($ch);

// Separa headers e body
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "✅ Risposta ricevuta dal server\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📊 HTTP Status Code: $httpCode\n\n";

if ($httpCode === 200) {
    echo "✅ SUCCESSO! Il server ha accettato l'Inform\n\n";
    
    // Cerca cookie di sessione
    if (preg_match('/Set-Cookie: TR069SessionID=([^;]+)/', $headers, $matches)) {
        echo "🍪 Session Cookie: " . $matches[1] . "\n\n";
    }
    
    echo "📄 Risposta SOAP:\n";
    echo "━━━━━━━━━━━━━━━━━\n";
    
    // Formatta XML per output leggibile
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    
    if (@$dom->loadXML($body)) {
        echo $dom->saveXML();
    } else {
        echo $body;
    }
    
    echo "\n━━━━━━━━━━━━━━━━━\n\n";
    
    echo "🔍 Verifica registrazione dispositivo:\n";
    echo "   → Vai su http://127.0.0.1:5000/acs/devices\n";
    echo "   → Cerca il dispositivo TEST-SN-99999\n";
    echo "   → Dovrebbe essere registrato con 10 parametri TR-181\n\n";
    
} else {
    echo "❌ ERRORE! HTTP $httpCode\n\n";
    echo "Headers:\n$headers\n\n";
    echo "Body:\n$body\n";
}

echo "✨ Test completato!\n";
