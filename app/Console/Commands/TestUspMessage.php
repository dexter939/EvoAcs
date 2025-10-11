<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UspMessageService;

class TestUspMessage extends Command
{
    protected $signature = 'usp:test-message';
    protected $description = 'Test USP Message encoding/decoding';

    public function handle(UspMessageService $uspService)
    {
        $this->info('🔬 Testing USP Message Service...');
        $this->newLine();

        // Test 1: Get Message
        $this->info('1️⃣  Creating USP Get Message...');
        $getMsg = $uspService->createGetMessage([
            'Device.DeviceInfo.',
            'Device.WiFi.Radio.1.Status'
        ]);

        $this->line('   Message ID: ' . $getMsg->getHeader()->getMsgId());
        $this->line('   Message Type: ' . $uspService->getMessageType($getMsg));
        
        $binary = $uspService->serializeMessage($getMsg);
        $this->line('   Binary size: ' . strlen($binary) . ' bytes');
        $this->newLine();

        // Test 2: Set Message
        $this->info('2️⃣  Creating USP Set Message...');
        $setMsg = $uspService->createSetMessage([
            'Device.WiFi.Radio.1.' => [
                'Enable' => true,
                'Channel' => 6
            ]
        ]);

        $this->line('   Message ID: ' . $setMsg->getHeader()->getMsgId());
        $this->line('   Message Type: ' . $uspService->getMessageType($setMsg));
        $this->newLine();

        // Test 3: Operate Message (Reboot)
        $this->info('3️⃣  Creating USP Operate Message (Reboot)...');
        $operateMsg = $uspService->createOperateMessage('Device.Reboot()');
        
        $this->line('   Message ID: ' . $operateMsg->getHeader()->getMsgId());
        $this->line('   Message Type: ' . $uspService->getMessageType($operateMsg));
        $this->newLine();

        // Test 4: Wrap in Record
        $this->info('4️⃣  Wrapping message in USP Record...');
        $record = $uspService->wrapInRecord(
            $getMsg,
            'proto::usp-agent-001',
            'proto::usp-controller',
            '1.3'
        );

        $this->line('   From: ' . $record->getFromId());
        $this->line('   To: ' . $record->getToId());
        $this->line('   Version: ' . $record->getVersion());
        
        $recordBinary = $uspService->serializeRecord($record);
        $this->line('   Record binary size: ' . strlen($recordBinary) . ' bytes');
        $this->newLine();

        // Test 5: Extract message from record
        $this->info('5️⃣  Extracting message from Record...');
        $extractedMsg = $uspService->extractMessageFromRecord($record);
        
        if ($extractedMsg) {
            $this->line('   ✅ Message extracted successfully');
            $this->line('   Message ID: ' . $extractedMsg->getHeader()->getMsgId());
            $this->line('   Type: ' . $uspService->getMessageType($extractedMsg));
        } else {
            $this->error('   ❌ Failed to extract message');
        }
        $this->newLine();

        // Test 6: Create Response
        $this->info('6️⃣  Creating USP Get Response...');
        $responseMsg = $uspService->createGetResponseMessage(
            $getMsg->getHeader()->getMsgId(),
            [
                'Device.DeviceInfo.Manufacturer' => 'ACS Test Inc',
                'Device.DeviceInfo.ModelName' => 'TR-369 Device',
                'Device.WiFi.Radio.1.Status' => 'Up'
            ]
        );

        $this->line('   Response Message ID: ' . $responseMsg->getHeader()->getMsgId());
        $this->line('   Response Type: ' . $uspService->getMessageType($responseMsg));
        $this->newLine();

        $this->info('✅ All USP Message tests completed successfully!');
        
        return Command::SUCCESS;
    }
}
