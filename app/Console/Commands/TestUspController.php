<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UspMessageService;
use Illuminate\Support\Facades\Http;

class TestUspController extends Command
{
    protected $signature = 'usp:test-controller';
    protected $description = 'Test USP Controller by sending messages';

    public function handle(UspMessageService $uspService)
    {
        $this->info('🧪 Testing USP Controller...');
        $this->newLine();

        $baseUrl = env('APP_URL', 'http://localhost:5000');
        $uspEndpoint = "{$baseUrl}/usp";

        $this->info("USP Endpoint: {$uspEndpoint}");
        $this->newLine();

        // Test 1: Send GET request
        $this->info('1️⃣  Sending USP GET Request...');
        
        $getMsg = $uspService->createGetMessage([
            'Device.DeviceInfo.',
            'Device.WiFi.Radio.1.Status'
        ]);

        $getRecord = $uspService->wrapInRecord(
            $getMsg,
            'proto::usp-controller',      // to (controller)
            'proto::usp-test-agent-001',  // from (agent)
            '1.3'
        );

        $getBinary = $uspService->serializeRecord($getRecord);

        try {
            $response = Http::withBody($getBinary, 'application/octet-stream')
                ->post($uspEndpoint);

            if ($response->successful()) {
                $this->line('   ✅ Response received: ' . $response->status());
                $this->line('   📦 Response size: ' . strlen($response->body()) . ' bytes');

                // Deserialize response
                $responseRecord = $uspService->deserializeRecord($response->body());
                $responseMsg = $uspService->extractMessageFromRecord($responseRecord);
                
                if ($responseMsg) {
                    $this->line('   📨 Response type: ' . $uspService->getMessageType($responseMsg));
                    $this->line('   🆔 From: ' . $responseRecord->getFromId());
                    $this->line('   🎯 To: ' . $responseRecord->getToId());
                }
            } else {
                $this->error('   ❌ Request failed: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error: ' . $e->getMessage());
        }

        $this->newLine();

        // Test 2: Send SET request
        $this->info('2️⃣  Sending USP SET Request...');
        
        $setMsg = $uspService->createSetMessage([
            'Device.WiFi.Radio.1.' => [
                'Enable' => 'true',
                'Channel' => '6',
                'OperatingFrequencyBand' => '2.4GHz'
            ]
        ]);

        $setRecord = $uspService->wrapInRecord(
            $setMsg,
            'proto::usp-controller',
            'proto::usp-test-agent-001',
            '1.3'
        );

        $setBinary = $uspService->serializeRecord($setRecord);

        try {
            $response = Http::withBody($setBinary, 'application/octet-stream')
                ->post($uspEndpoint);

            if ($response->successful()) {
                $this->line('   ✅ Response received: ' . $response->status());
                $this->line('   📦 Response size: ' . strlen($response->body()) . ' bytes');
            } else {
                $this->error('   ❌ Request failed: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error: ' . $e->getMessage());
        }

        $this->newLine();

        // Test 3: Send OPERATE (Reboot) request
        $this->info('3️⃣  Sending USP OPERATE Request (Reboot)...');
        
        $operateMsg = $uspService->createOperateMessage('Device.Reboot()');

        $operateRecord = $uspService->wrapInRecord(
            $operateMsg,
            'proto::usp-controller',
            'proto::usp-test-agent-001',
            '1.3'
        );

        $operateBinary = $uspService->serializeRecord($operateRecord);

        try {
            $response = Http::withBody($operateBinary, 'application/octet-stream')
                ->post($uspEndpoint);

            if ($response->successful()) {
                $this->line('   ✅ Response received: ' . $response->status());
            } else {
                $this->error('   ❌ Request failed: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error: ' . $e->getMessage());
        }

        $this->newLine();

        // Check database
        $this->info('4️⃣  Checking database for auto-registered device...');
        
        $device = \App\Models\CpeDevice::where('usp_endpoint_id', 'proto::usp-test-agent-001')->first();
        
        if ($device) {
            $this->line('   ✅ Device found in database:');
            $this->line('      ID: ' . $device->id);
            $this->line('      Serial: ' . $device->serial_number);
            $this->line('      Protocol: ' . $device->protocol_type);
            $this->line('      Endpoint ID: ' . $device->usp_endpoint_id);
            $this->line('      Status: ' . $device->status);
            $this->line('      Last contact: ' . $device->last_contact);

            // Check parameters
            $params = \App\Models\DeviceParameter::where('cpe_device_id', $device->id)->get();
            if ($params->count() > 0) {
                $this->newLine();
                $this->line('   📊 Device Parameters (' . $params->count() . '):');
                foreach ($params->take(5) as $param) {
                    $this->line('      ' . $param->parameter_path . ' = ' . $param->parameter_value);
                }
            }
        } else {
            $this->warn('   ⚠️  Device not found in database');
        }

        $this->newLine();
        $this->info('✅ USP Controller test completed!');
        
        return Command::SUCCESS;
    }
}
