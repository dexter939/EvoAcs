<?php

namespace App\Console\Commands;

use App\Models\CpeDevice;
use App\Services\UspMessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UspWebSocketServer extends Command
{
    protected $signature = 'usp:websocket-server {--port=8080}';
    protected $description = 'Start USP WebSocket server for TR-369 device connections';

    protected UspMessageService $uspMessageService;
    protected $socket;
    protected array $clients = [];
    protected array $deviceConnections = [];

    public function handle(): int
    {
        $this->uspMessageService = app(UspMessageService::class);
        $port = $this->option('port');

        $this->info("🚀 Starting USP WebSocket Server on port {$port}...");
        $this->info("   Devices can connect to: ws://0.0.0.0:{$port}/usp");
        $this->newLine();

        try {
            $this->socket = stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);
            
            if (!$this->socket) {
                throw new \Exception("Could not create socket: {$errstr} ({$errno})");
            }

            stream_set_blocking($this->socket, false);
            
            $this->info("✅ WebSocket Server started successfully");
            $this->info("   Listening for USP device connections...");
            $this->newLine();

            $this->runEventLoop();

        } catch (\Exception $e) {
            $this->error("❌ WebSocket Server error: " . $e->getMessage());
            Log::error('WebSocket Server failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function runEventLoop(): void
    {
        while (true) {
            $read = [$this->socket];
            foreach ($this->clients as $client) {
                $read[] = $client['socket'];
            }

            $write = null;
            $except = null;

            if (stream_select($read, $write, $except, 0, 200000) < 1) {
                // Process outbound queue when no socket activity
                $this->processOutboundQueue();
                continue;
            }

            // New connection
            if (in_array($this->socket, $read)) {
                $newSocket = stream_socket_accept($this->socket);
                if ($newSocket) {
                    $this->handleNewConnection($newSocket);
                }
                unset($read[array_search($this->socket, $read)]);
            }

            // Read from existing connections
            foreach ($read as $socket) {
                $clientId = (int)$socket;
                if (!isset($this->clients[$clientId])) {
                    continue;
                }

                $data = fread($socket, 8192);
                if (!$data || feof($socket)) {
                    $this->handleDisconnection($clientId);
                } else {
                    $this->handleData($clientId, $data);
                }
            }
            
            // Process outbound messages
            $this->processOutboundQueue();
        }
    }
    
    /**
     * Process outbound message queue from Redis
     */
    protected function processOutboundQueue(): void
    {
        foreach ($this->clients as $clientId => $client) {
            if (!$client['handshake']) {
                continue;
            }
            
            $queueKey = "usp:websocket:outbound:{$clientId}";
            $message = Redis::rpop($queueKey);
            
            if ($message) {
                try {
                    $encodedFrame = $this->encodeFrame($message);
                    fwrite($client['socket'], $encodedFrame);
                    
                    Log::info('Outbound USP Record sent via WebSocket', [
                        'client_id' => $clientId,
                        'size' => strlen($message)
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send outbound message', [
                        'client_id' => $clientId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    protected function handleNewConnection($socket): void
    {
        $clientId = (int)$socket;
        $this->clients[$clientId] = [
            'socket' => $socket,
            'handshake' => false,
            'buffer' => '',
            'fragment_opcode' => null,
            'fragment_buffer' => ''
        ];

        $this->info("🔌 New connection: Client #{$clientId}");
        
        Log::info('WebSocket connection opened', [
            'client_id' => $clientId
        ]);
    }

    protected function handleData(int $clientId, string $data): void
    {
        if (!isset($this->clients[$clientId])) {
            return;
        }

        $client = &$this->clients[$clientId];

        // WebSocket handshake
        if (!$client['handshake']) {
            $this->performHandshake($clientId, $data);
            return;
        }

        // Append to buffer
        $client['buffer'] .= $data;

        // Try to decode complete frames from buffer
        while ($this->clients[$clientId]['buffer'] !== '') {
            $decodedData = $this->decodeFrame($clientId);
            
            if ($decodedData === false) {
                // Incomplete frame, wait for more data
                break;
            }
            
            if ($decodedData !== null) {
                $this->processUspRecord($clientId, $decodedData);
            }
        }
    }

    protected function performHandshake(int $clientId, string $request): void
    {
        $headers = [];
        $lines = explode("\n", $request);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^Sec-WebSocket-Key: (.*)$/i', $line, $matches)) {
                $headers['Sec-WebSocket-Key'] = $matches[1];
            }
        }

        if (!isset($headers['Sec-WebSocket-Key'])) {
            return;
        }

        $key = $headers['Sec-WebSocket-Key'];
        $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                   "Upgrade: websocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n";

        fwrite($this->clients[$clientId]['socket'], $response);
        $this->clients[$clientId]['handshake'] = true;

        $this->info("✋ WebSocket handshake completed for Client #{$clientId}");
    }

    protected function decodeFrame(int $clientId): string|null|false
    {
        $buffer = &$this->clients[$clientId]['buffer'];
        
        if (strlen($buffer) < 2) {
            return false; // Need at least 2 bytes for header
        }

        $firstByte = ord($buffer[0]);
        $secondByte = ord($buffer[1]);
        
        $fin = ($firstByte >> 7) & 1;
        $opcode = $firstByte & 0x0F;
        $masked = ($secondByte >> 7) & 1;
        $payloadLen = $secondByte & 0x7F;
        
        $headerSize = 2;
        
        // Extended payload length
        if ($payloadLen == 126) {
            if (strlen($buffer) < 4) {
                return false; // Need 2 more bytes
            }
            $payloadLen = unpack('n', substr($buffer, 2, 2))[1];
            $headerSize = 4;
        } elseif ($payloadLen == 127) {
            if (strlen($buffer) < 10) {
                return false; // Need 8 more bytes
            }
            // Big-endian 64-bit unsigned
            $high = unpack('N', substr($buffer, 2, 4))[1];
            $low = unpack('N', substr($buffer, 6, 4))[1];
            $payloadLen = ($high << 32) | $low;
            $headerSize = 10;
        }
        
        // Masking key (client frames are always masked)
        if ($masked) {
            if (strlen($buffer) < $headerSize + 4) {
                return false; // Need masking key
            }
            $maskKey = substr($buffer, $headerSize, 4);
            $headerSize += 4;
        }
        
        // Check if we have complete payload
        if (strlen($buffer) < $headerSize + $payloadLen) {
            return false; // Incomplete frame
        }
        
        // Extract payload
        $payload = substr($buffer, $headerSize, $payloadLen);
        
        // Unmask if needed
        if ($masked) {
            $unmasked = '';
            for ($i = 0; $i < strlen($payload); $i++) {
                $unmasked .= $payload[$i] ^ $maskKey[$i % 4];
            }
            $payload = $unmasked;
        }
        
        // Remove processed frame from buffer
        $buffer = substr($buffer, $headerSize + $payloadLen);
        
        // Handle control frames (high bit set in opcode)
        if ($opcode == 0x8) {
            // Close frame
            $this->handleDisconnection($clientId);
            return null;
        }
        
        if ($opcode == 0x9) {
            // Ping frame - send pong
            $pongFrame = chr(0x8A) . chr(strlen($payload)) . $payload;
            fwrite($this->clients[$clientId]['socket'], $pongFrame);
            return null;
        }
        
        // Handle data frames with fragmentation support
        $client = &$this->clients[$clientId];
        
        if ($opcode == 0x1 || $opcode == 0x2) {
            // Initial frame (text or binary)
            if ($fin) {
                // Complete single-frame message
                return $payload;
            } else {
                // Start of fragmented message
                $client['fragment_opcode'] = $opcode;
                $client['fragment_buffer'] = $payload;
                return null;
            }
        }
        
        if ($opcode == 0x0) {
            // Continuation frame
            if ($client['fragment_opcode'] === null) {
                // Continuation without initial frame - protocol error
                Log::warning('WebSocket continuation frame without initial frame', [
                    'client_id' => $clientId
                ]);
                return null;
            }
            
            // Append to fragment buffer
            $client['fragment_buffer'] .= $payload;
            
            if ($fin) {
                // Final fragment - deliver complete message
                $completeMessage = $client['fragment_buffer'];
                $client['fragment_opcode'] = null;
                $client['fragment_buffer'] = '';
                return $completeMessage;
            }
            
            // More fragments to come
            return null;
        }
        
        return null;
    }

    protected function encodeFrame(string $message): string
    {
        $length = strlen($message);
        $header = chr(0x82); // FIN=1, opcode=2 (binary frame)

        if ($length <= 125) {
            $header .= chr($length);
        } elseif ($length <= 65535) {
            // 16-bit length in network byte order (big-endian)
            $header .= chr(126) . pack('n', $length);
        } else {
            // 64-bit length in network byte order (big-endian)
            $header .= chr(127);
            $high = ($length >> 32) & 0xFFFFFFFF;
            $low = $length & 0xFFFFFFFF;
            $header .= pack('N', $high) . pack('N', $low);
        }

        return $header . $message;
    }

    protected function processUspRecord(int $clientId, string $binaryMessage): void
    {
        try {
            $this->info("📨 Received USP Record from Client #{$clientId} (size: " . strlen($binaryMessage) . " bytes)");

            $record = $this->uspMessageService->deserializeRecord($binaryMessage);
            $message = $this->uspMessageService->extractMessage($record);
            
            $fromId = $record->getFromId();
            $toId = $record->getToId();
            
            $this->info("   From: {$fromId}");
            $this->info("   To: {$toId}");

            $device = $this->findOrCreateDevice($fromId, $clientId);

            $responseMessage = $this->processMessage($message, $device);

            $responseRecord = $this->uspMessageService->wrapInRecord(
                $responseMessage,
                $toId,
                $fromId
            );

            $responseBinary = $this->uspMessageService->serializeRecord($responseRecord);
            
            $encodedFrame = $this->encodeFrame($responseBinary);
            fwrite($this->clients[$clientId]['socket'], $encodedFrame);

            $this->info("   ✅ Response sent via WebSocket");
            $this->newLine();

        } catch (\Exception $e) {
            $this->error("   ❌ Error processing USP Record: " . $e->getMessage());
            Log::error('USP WebSocket Record processing error', [
                'client_id' => $clientId,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function handleDisconnection(int $clientId): void
    {
        if (!isset($this->clients[$clientId])) {
            return;
        }

        // Find device by client ID
        $device = null;
        foreach ($this->deviceConnections as $deviceId => $storedClientId) {
            if ($storedClientId === $clientId) {
                $device = CpeDevice::find($deviceId);
                break;
            }
        }

        if ($device) {
            $device->update([
                'websocket_connected_at' => null,
                'last_websocket_ping' => null
            ]);
            
            unset($this->deviceConnections[$device->id]);
            Redis::hdel('usp:websocket:connections', $device->id);
            
            $this->info("📴 Device disconnected: {$device->serial_number}");
        } else {
            $this->info("📴 Client #{$clientId} disconnected");
        }

        fclose($this->clients[$clientId]['socket']);
        unset($this->clients[$clientId]);
        
        Log::info('WebSocket connection closed', [
            'client_id' => $clientId,
            'device_id' => $device->id ?? null
        ]);
    }

    protected function findOrCreateDevice(string $endpointId, int $clientId): CpeDevice
    {
        $device = CpeDevice::where('usp_endpoint_id', $endpointId)->first();

        if (!$device) {
            $serialNumber = 'USP-WS-' . substr(md5($endpointId . time()), 0, 10);
            
            $device = CpeDevice::create([
                'serial_number' => $serialNumber,
                'oui' => config('usp.defaults.oui', '000000'),
                'product_class' => config('usp.defaults.product_class', 'USP Device'),
                'protocol_type' => 'tr369',
                'usp_endpoint_id' => $endpointId,
                'websocket_client_id' => (string)$clientId,
                'mtp_type' => 'websocket',
                'status' => 'online',
                'websocket_connected_at' => now(),
                'last_websocket_ping' => now(),
                'last_contact' => now(),
            ]);

            $this->info("   📝 Device auto-registered: {$serialNumber}");
            Log::info('USP device auto-registered via WebSocket', [
                'device_id' => $device->id,
                'endpoint_id' => $endpointId,
                'client_id' => $clientId
            ]);
        } else {
            $device->update([
                'status' => 'online',
                'websocket_client_id' => (string)$clientId,
                'websocket_connected_at' => now(),
                'last_websocket_ping' => now(),
                'last_contact' => now(),
            ]);
        }

        $this->deviceConnections[$device->id] = $clientId;
        Redis::hset('usp:websocket:connections', $device->id, $clientId);

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

        $device->update(['last_websocket_ping' => now()]);

        return match($msgType) {
            \Usp\Msg\Header\MsgType::GET => $this->handleGet($body->getRequest()->getGet(), $device, $msgId),
            \Usp\Msg\Header\MsgType::SET => $this->handleSet($body->getRequest()->getSet(), $device, $msgId),
            \Usp\Msg\Header\MsgType::OPERATE => $this->handleOperate($body->getRequest()->getOperate(), $device, $msgId),
            \Usp\Msg\Header\MsgType::ADD => $this->handleAdd($body->getRequest()->getAdd(), $device, $msgId),
            \Usp\Msg\Header\MsgType::DELETE => $this->handleDelete($body->getRequest()->getDelete(), $device, $msgId),
            \Usp\Msg\Header\MsgType::NOTIFY => $this->handleNotify($body->getRequest()->getNotify(), $device, $msgId),
            default => $this->uspMessageService->createErrorMessage($msgId, 9000, "Unsupported message type: {$msgType}"),
        };
    }

    protected function handleGet($getRequest, CpeDevice $device, string $msgId)
    {
        $paths = iterator_to_array($getRequest->getParamPaths());
        $this->info("   GET Parameters: " . implode(', ', $paths));

        $parameters = [];
        foreach ($paths as $path) {
            $param = $device->parameters()->where('parameter_path', $path)->first();
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
                    ['parameter_value' => $value]
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

        if ($command === 'Device.Reboot()') {
            Log::info('Reboot command received via WebSocket', ['device_id' => $device->id]);
            return $this->uspMessageService->createOperateResponseMessage($msgId, [
                'executed' => true,
                'result' => 'Reboot initiated'
            ]);
        }

        return $this->uspMessageService->createOperateResponseMessage($msgId, [
            'executed' => false,
            'error' => 'Command not implemented'
        ]);
    }

    protected function handleAdd($addRequest, CpeDevice $device, string $msgId)
    {
        $this->info("   ADD Object request");
        return $this->uspMessageService->createAddResponseMessage($msgId, []);
    }

    protected function handleDelete($deleteRequest, CpeDevice $device, string $msgId)
    {
        $this->info("   DELETE Object request");
        return $this->uspMessageService->createDeleteResponseMessage($msgId, []);
    }

    protected function handleNotify($notifyRequest, CpeDevice $device, string $msgId)
    {
        $this->info("   NOTIFY request");
        return $this->uspMessageService->createNotifyResponseMessage($msgId);
    }
}
