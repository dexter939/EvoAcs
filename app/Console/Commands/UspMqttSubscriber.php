<?php

namespace App\Console\Commands;

use App\Models\CpeDevice;
use App\Services\UspMessageService;
use App\Services\UspMqttService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UspMqttSubscriber extends Command
{
    protected $signature = 'usp:mqtt-subscribe 
                            {--host= : MQTT broker host}
                            {--port=1883 : MQTT broker port}';

    protected $description = 'Subscribe to MQTT topics and process incoming USP Records from TR-369 devices';

    protected UspMessageService $uspMessageService;
    protected UspMqttService $uspMqttService;

    public function __construct(
        UspMessageService $uspMessageService,
        UspMqttService $uspMqttService
    ) {
        parent::__construct();
        $this->uspMessageService = $uspMessageService;
        $this->uspMqttService = $uspMqttService;
    }

    public function handle()
    {
        $host = $this->option('host') ?? config('mqtt-client.connections.default.host');
        $port = $this->option('port') ?? config('mqtt-client.connections.default.port');

        if (!$host) {
            $this->error('MQTT host not configured. Set MQTT_HOST in .env or use --host option');
            return 1;
        }

        $this->info("🔌 Connecting to MQTT broker: {$host}:{$port}");
        $this->info("📡 Subscribing to USP topics...");
        $this->info("👂 Listening for USP Records from TR-369 devices...");
        $this->newLine();

        try {
            $this->uspMqttService->subscribe(function ($binaryMessage, $clientId) {
                $this->processUspRecord($binaryMessage, $clientId);
            });

        } catch (\Exception $e) {
            $this->error("❌ MQTT Subscriber error: " . $e->getMessage());
            Log::error('MQTT Subscriber failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function processUspRecord(string $binaryMessage, ?string $mqttClientId): void
    {
        try {
            $this->info("📨 Received USP Record (size: " . strlen($binaryMessage) . " bytes)");

            $record = $this->uspMessageService->deserializeRecord($binaryMessage);
            $message = $this->uspMessageService->extractMessage($record);
            
            $fromId = $record->getFromId();
            $toId = $record->getToId();
            
            $this->info("   From: {$fromId}");
            $this->info("   To: {$toId}");

            $device = $this->findOrCreateDevice($fromId, $mqttClientId);

            $responseMessage = $this->processMessage($message, $device);

            $responseRecord = $this->uspMessageService->wrapInRecord(
                $responseMessage,
                $toId,
                $fromId
            );

            $responseBinary = $this->uspMessageService->serializeRecord($responseRecord);

            $this->uspMqttService->publishToDevice($device, $responseBinary);

            $this->info("   ✅ Response sent via MQTT");
            $this->newLine();

        } catch (\Exception $e) {
            $this->error("   ❌ Error processing USP Record: " . $e->getMessage());
            Log::error('USP Record processing error', [
                'client_id' => $mqttClientId,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function findOrCreateDevice(string $endpointId, ?string $mqttClientId): CpeDevice
    {
        $device = CpeDevice::where('usp_endpoint_id', $endpointId)->first();

        if (!$device) {
            $serialNumber = 'USP-' . substr(md5($endpointId . time()), 0, 10);
            
            $device = CpeDevice::create([
                'serial_number' => $serialNumber,
                'oui' => config('usp.defaults.oui', '000000'),
                'product_class' => config('usp.defaults.product_class', 'USP Device'),
                'protocol_type' => 'tr369',
                'usp_endpoint_id' => $endpointId,
                'mqtt_client_id' => $mqttClientId,
                'mtp_type' => 'mqtt',
                'status' => 'online',
                'last_contact_at' => now(),
            ]);

            $this->info("   📝 Device auto-registered: {$serialNumber}");
            Log::info('USP device auto-registered via MQTT', [
                'device_id' => $device->id,
                'endpoint_id' => $endpointId,
                'mqtt_client_id' => $mqttClientId
            ]);
        } else {
            $device->update([
                'status' => 'online',
                'last_contact_at' => now(),
                'mqtt_client_id' => $mqttClientId ?? $device->mqtt_client_id,
            ]);
        }

        return $device;
    }

    protected function processMessage($message, CpeDevice $device)
    {
        $header = $message->getHeader();
        $body = $message->getBody();
        $msgType = $header->getMsgType();
        $msgId = $header->getMsgId();

        $this->info("   Message Type: " . $msgType);
        $this->info("   Message ID: " . $msgId);

        return match($msgType) {
            \Usp\Msg\Header\MsgType::GET => $this->handleGet($body->getRequest()->getGet(), $device, $msgId),
            \Usp\Msg\Header\MsgType::SET => $this->handleSet($body->getRequest()->getSet(), $device, $msgId),
            \Usp\Msg\Header\MsgType::OPERATE => $this->handleOperate($body->getRequest()->getOperate(), $device, $msgId),
            \Usp\Msg\Header\MsgType::ADD => $this->handleAdd($body->getRequest()->getAdd(), $device, $msgId),
            \Usp\Msg\Header\MsgType::DELETE => $this->handleDelete($body->getRequest()->getDelete(), $device, $msgId),
            \Usp\Msg\Header\MsgType::NOTIFY => $this->handleNotify($body->getRequest()->getNotify(), $device, $msgId),
            default => $this->uspMessageService->createErrorMessage(
                $msgId,
                9000,
                "Unsupported message type: {$msgType}"
            ),
        };
    }

    protected function handleGet($getRequest, CpeDevice $device, string $msgId)
    {
        $paths = iterator_to_array($getRequest->getParamPaths());
        $this->info("   GET Parameters: " . implode(', ', $paths));

        $parameters = [];
        foreach ($paths as $path) {
            $param = $device->parameters()
                ->where('parameter_path', $path)
                ->first();
            
            if ($param) {
                $parameters[$path] = $param->parameter_value;
            }
        }

        return $this->uspMessageService->createGetResponseMessage($msgId, $parameters);
    }

    protected function handleSet($setRequest, CpeDevice $device, string $msgId)
    {
        $updateObjs = iterator_to_array($setRequest->getUpdateObjs());
        $this->info("   SET Parameters count: " . count($updateObjs));

        $updatedParams = [];
        foreach ($updateObjs as $updateObj) {
            $objPath = $updateObj->getObjPath();
            $paramSettings = iterator_to_array($updateObj->getParamSettings());
            
            foreach ($paramSettings as $setting) {
                $param = $setting->getParam();
                $value = $setting->getValue();
                $fullPath = rtrim($objPath, '.') . '.' . $param;
                
                $device->parameters()->updateOrCreate(
                    ['parameter_path' => $fullPath],
                    [
                        'parameter_value' => $value,
                        'parameter_type' => 'string',
                        'is_writable' => true,
                        'last_updated' => now(),
                    ]
                );
                
                $updatedParams[$fullPath] = $value;
            }
        }

        return $this->uspMessageService->createSetResponseMessage($msgId, $updatedParams);
    }

    protected function handleOperate($operateRequest, CpeDevice $device, string $msgId)
    {
        $command = $operateRequest->getCommand();
        $this->info("   OPERATE Command: {$command}");

        return $this->uspMessageService->createOperateResponseMessage($msgId, $command, []);
    }

    protected function handleAdd($addRequest, CpeDevice $device, string $msgId)
    {
        $this->info("   ADD Request received");
        return $this->uspMessageService->createAddResponseMessage($msgId, []);
    }

    protected function handleDelete($deleteRequest, CpeDevice $device, string $msgId)
    {
        $this->info("   DELETE Request received");
        return $this->uspMessageService->createDeleteResponseMessage($msgId, []);
    }

    protected function handleNotify($notifyRequest, CpeDevice $device, string $msgId)
    {
        $this->info("   NOTIFY Request received");
        return $this->uspMessageService->createErrorMessage(
            $msgId,
            9000,
            "NOTIFY handling not yet implemented"
        );
    }
}
