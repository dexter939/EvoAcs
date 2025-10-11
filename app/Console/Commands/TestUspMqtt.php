<?php

namespace App\Console\Commands;

use App\Services\UspMessageService;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class TestUspMqtt extends Command
{
    protected $signature = 'usp:test-mqtt 
                            {--host= : MQTT broker host}
                            {--port=1883 : MQTT broker port}';

    protected $description = 'Test USP MQTT communication by simulating a device';

    protected UspMessageService $uspMessageService;

    public function __construct(UspMessageService $uspMessageService)
    {
        parent::__construct();
        $this->uspMessageService = $uspMessageService;
    }

    public function handle()
    {
        $host = $this->option('host') ?? config('mqtt-client.connections.default.host');
        $port = $this->option('port') ?? config('mqtt-client.connections.default.port');

        if (!$host) {
            $this->error('MQTT host not configured. Set MQTT_HOST in .env or use --host option');
            return 1;
        }

        $this->info("🧪 Testing USP MQTT Communication");
        $this->info("🔌 MQTT Broker: {$host}:{$port}");
        $this->newLine();

        $agentId = 'proto::usp-mqtt-test-agent-' . substr(md5((string)time()), 0, 8);
        $controllerId = config('usp.controller_endpoint_id', 'proto::acs-controller');

        try {
            $mqtt = MQTT::connection();

            $this->info("1️⃣  Sending USP GET Request via MQTT...");
            $this->sendGetRequest($mqtt, $agentId, $controllerId);
            sleep(2);

            $this->info("2️⃣  Sending USP SET Request via MQTT...");
            $this->sendSetRequest($mqtt, $agentId, $controllerId);
            sleep(2);

            $this->info("3️⃣  Sending USP OPERATE Request via MQTT...");
            $this->sendOperateRequest($mqtt, $agentId, $controllerId);
            sleep(2);

            $this->newLine();
            $this->info("✅ USP MQTT test messages sent successfully!");
            $this->info("📡 To receive and process these messages, run:");
            $this->info("   php artisan usp:mqtt-subscribe");
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ MQTT test failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function sendGetRequest($mqtt, string $agentId, string $controllerId): void
    {
        $getRequest = $this->uspMessageService->createGetMessage([
            'Device.WiFi.Radio.1.Channel',
            'Device.WiFi.Radio.1.Enable',
            'Device.WiFi.Radio.1.OperatingFrequencyBand',
        ]);

        $record = $this->uspMessageService->wrapInRecord(
            $getRequest,
            $controllerId,
            $agentId
        );

        $binary = $this->uspMessageService->serializeRecord($record);

        $topic = "usp/controller/{$controllerId}/{$agentId}";
        
        $mqtt->publish($topic, $binary, 0, false);

        $this->info("   📨 GET Request published to: {$topic}");
        $this->info("   📦 Size: " . strlen($binary) . " bytes");
    }

    protected function sendSetRequest($mqtt, string $agentId, string $controllerId): void
    {
        $setRequest = $this->uspMessageService->createSetMessage([
            'Device.WiFi.Radio.1.' => [
                'Channel' => '11',
                'Enable' => 'true',
                'OperatingFrequencyBand' => '2.4GHz',
            ],
        ]);

        $record = $this->uspMessageService->wrapInRecord(
            $setRequest,
            $controllerId,
            $agentId
        );

        $binary = $this->uspMessageService->serializeRecord($record);

        $topic = "usp/controller/{$controllerId}/{$agentId}";
        
        $mqtt->publish($topic, $binary, 0, false);

        $this->info("   📨 SET Request published to: {$topic}");
        $this->info("   📦 Size: " . strlen($binary) . " bytes");
    }

    protected function sendOperateRequest($mqtt, string $agentId, string $controllerId): void
    {
        $operateRequest = $this->uspMessageService->createOperateMessage(
            'Device.Reboot()',
            []
        );

        $record = $this->uspMessageService->wrapInRecord(
            $operateRequest,
            $controllerId,
            $agentId
        );

        $binary = $this->uspMessageService->serializeRecord($record);

        $topic = "usp/controller/{$controllerId}/{$agentId}";
        
        $mqtt->publish($topic, $binary, 0, false);

        $this->info("   📨 OPERATE Request published to: {$topic}");
        $this->info("   📦 Size: " . strlen($binary) . " bytes");
    }
}
