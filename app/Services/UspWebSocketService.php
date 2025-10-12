<?php

namespace App\Services;

use App\Models\CpeDevice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UspWebSocketService
{
    protected UspMessageService $uspMessageService;

    public function __construct(UspMessageService $uspMessageService)
    {
        $this->uspMessageService = $uspMessageService;
    }

    /**
     * Send USP Record to device via WebSocket
     * 
     * Note: This requires the UspWebSocketServer daemon to be running
     */
    public function sendToDevice(CpeDevice $device, string $uspRecordBinary): bool
    {
        if (!$device->websocket_client_id || $device->mtp_type !== 'websocket') {
            Log::warning('Device not configured for WebSocket', [
                'device_id' => $device->id,
                'websocket_client_id' => $device->websocket_client_id,
                'mtp_type' => $device->mtp_type
            ]);
            return false;
        }

        // Check if device is connected
        $clientId = Redis::hget('usp:websocket:connections', $device->id);
        if (!$clientId) {
            Log::warning('Device not connected to WebSocket server', [
                'device_id' => $device->id,
                'websocket_client_id' => $device->websocket_client_id
            ]);
            
            // Mark as disconnected
            $device->update([
                'websocket_connected_at' => null,
                'last_websocket_ping' => null
            ]);
            
            return false;
        }

        try {
            // Queue message for WebSocket server to send
            // The message will be sent by the running WebSocket server daemon
            Redis::lpush("usp:websocket:outbound:{$clientId}", $uspRecordBinary);
            
            Log::info('USP Record queued for WebSocket delivery', [
                'device_id' => $device->id,
                'client_id' => $clientId,
                'size' => strlen($uspRecordBinary)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to queue USP Record for WebSocket', [
                'device_id' => $device->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send USP GET request
     */
    public function sendGetRequest(CpeDevice $device, array $paths, ?string $msgId = null): bool
    {
        $getRequest = $this->uspMessageService->createGetMessage($paths, $msgId);
        $record = $this->uspMessageService->wrapInRecord(
            $getRequest,
            config('usp.controller_endpoint_id', 'proto::acs-controller'),
            $device->usp_endpoint_id
        );
        
        $binary = $this->uspMessageService->serializeRecord($record);
        return $this->sendToDevice($device, $binary);
    }

    /**
     * Send USP SET request
     */
    public function sendSetRequest(CpeDevice $device, array $parameters, ?string $msgId = null, bool $allowPartial = true): bool
    {
        $setRequest = $this->uspMessageService->createSetMessage($parameters, $allowPartial, $msgId);
        $record = $this->uspMessageService->wrapInRecord(
            $setRequest,
            config('usp.controller_endpoint_id', 'proto::acs-controller'),
            $device->usp_endpoint_id
        );
        
        $binary = $this->uspMessageService->serializeRecord($record);
        return $this->sendToDevice($device, $binary);
    }

    /**
     * Send USP OPERATE request
     */
    public function sendOperateRequest(CpeDevice $device, string $command, array $commandArgs = [], ?string $msgId = null): bool
    {
        $operateRequest = $this->uspMessageService->createOperateMessage($command, $commandArgs, $msgId);
        $record = $this->uspMessageService->wrapInRecord(
            $operateRequest,
            config('usp.controller_endpoint_id', 'proto::acs-controller'),
            $device->usp_endpoint_id
        );
        
        $binary = $this->uspMessageService->serializeRecord($record);
        return $this->sendToDevice($device, $binary);
    }

    /**
     * Send USP ADD request
     */
    public function sendAddRequest(CpeDevice $device, string $objectPath, array $parameters = [], ?string $msgId = null, bool $allowPartial = false): bool
    {
        $addRequest = $this->uspMessageService->createAddMessage($objectPath, $parameters, $allowPartial, $msgId);
        $record = $this->uspMessageService->wrapInRecord(
            $addRequest,
            config('usp.controller_endpoint_id', 'proto::acs-controller'),
            $device->usp_endpoint_id
        );
        
        $binary = $this->uspMessageService->serializeRecord($record);
        return $this->sendToDevice($device, $binary);
    }

    /**
     * Send USP DELETE request
     */
    public function sendDeleteRequest(CpeDevice $device, array $objectPaths, ?string $msgId = null, bool $allowPartial = false): bool
    {
        $deleteRequest = $this->uspMessageService->createDeleteMessage($objectPaths, $allowPartial, $msgId);
        $record = $this->uspMessageService->wrapInRecord(
            $deleteRequest,
            config('usp.controller_endpoint_id', 'proto::acs-controller'),
            $device->usp_endpoint_id
        );
        
        $binary = $this->uspMessageService->serializeRecord($record);
        return $this->sendToDevice($device, $binary);
    }

    /**
     * Send Subscribe request for event notifications
     */
    public function sendSubscriptionRequest(CpeDevice $device, string $subscriptionPath, array $subscriptionParams, ?string $msgId = null): bool
    {
        $subscribeRequest = $this->uspMessageService->createSubscriptionMessage($subscriptionPath, $subscriptionParams, $msgId);
        $record = $this->uspMessageService->wrapInRecord(
            $subscribeRequest,
            config('usp.controller_endpoint_id', 'proto::acs-controller'),
            $device->usp_endpoint_id
        );
        
        $binary = $this->uspMessageService->serializeRecord($record);
        return $this->sendToDevice($device, $binary);
    }

    /**
     * Get all connected WebSocket devices
     */
    public function getConnectedDevices(): array
    {
        $connections = Redis::hgetall('usp:websocket:connections');
        $devices = [];
        
        foreach ($connections as $deviceId => $clientId) {
            $device = CpeDevice::find($deviceId);
            if ($device) {
                $devices[] = $device;
            }
        }
        
        return $devices;
    }

    /**
     * Check if device is currently connected
     */
    public function isDeviceConnected(CpeDevice $device): bool
    {
        return Redis::hexists('usp:websocket:connections', $device->id);
    }
}
