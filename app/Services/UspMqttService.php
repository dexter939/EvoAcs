<?php

namespace App\Services;

use App\Models\CpeDevice;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;
use PhpMqtt\Client\ConnectionSettings;

class UspMqttService
{
    protected UspMessageService $uspMessageService;

    public function __construct(UspMessageService $uspMessageService)
    {
        $this->uspMessageService = $uspMessageService;
    }

    /**
     * Publish USP Record to device via MQTT
     */
    public function publishToDevice(CpeDevice $device, string $uspRecordBinary): bool
    {
        if (!$device->mqtt_client_id || $device->mtp_type !== 'mqtt') {
            Log::warning('Device not configured for MQTT', [
                'device_id' => $device->id,
                'mqtt_client_id' => $device->mqtt_client_id,
                'mtp_type' => $device->mtp_type
            ]);
            return false;
        }

        try {
            $topic = $this->getDeviceSubscribeTopic($device->mqtt_client_id);
            
            $mqtt = MQTT::connection();
            $mqtt->publish(
                $topic,
                $uspRecordBinary,
                0, // QoS 0 for now
                false // Not retained
            );

            Log::info('USP Record published via MQTT', [
                'device_id' => $device->id,
                'topic' => $topic,
                'size' => strlen($uspRecordBinary)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to publish USP Record via MQTT', [
                'device_id' => $device->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Subscribe to device topics and handle incoming USP Records
     */
    public function subscribe(callable $messageHandler): void
    {
        try {
            $mqtt = MQTT::connection();
            
            // Subscribe to controller topic (where devices publish)
            $controllerTopic = $this->getControllerTopic();
            
            Log::info('MQTT Subscriber starting', [
                'topic' => $controllerTopic
            ]);

            $mqtt->subscribe($controllerTopic, function ($topic, $message) use ($messageHandler) {
                Log::info('MQTT Message received', [
                    'topic' => $topic,
                    'size' => strlen($message)
                ]);

                try {
                    // Extract client ID from topic
                    $clientId = $this->extractClientIdFromTopic($topic);
                    
                    // Call the message handler with the USP Record binary and client ID
                    $messageHandler($message, $clientId);

                } catch (\Exception $e) {
                    Log::error('Error processing MQTT message', [
                        'topic' => $topic,
                        'error' => $e->getMessage()
                    ]);
                }
            }, 0); // QoS 0

            // Keep the connection alive
            $mqtt->loop(true);

        } catch (\Exception $e) {
            Log::error('MQTT Subscriber error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get the topic where controller receives messages from devices
     * Format: usp/controller/{agent-id}/response
     */
    protected function getControllerTopic(): string
    {
        $controllerEndpointId = config('usp.controller_endpoint_id', 'proto::acs-controller');
        return "usp/controller/{$controllerEndpointId}/+";
    }

    /**
     * Get the topic where device receives messages from controller
     * Format: usp/agent/{agent-id}/request
     */
    protected function getDeviceSubscribeTopic(string $mqttClientId): string
    {
        return "usp/agent/{$mqttClientId}/request";
    }

    /**
     * Extract client ID from topic
     */
    protected function extractClientIdFromTopic(string $topic): ?string
    {
        // Topic format: usp/controller/{controller-id}/{agent-id}
        $parts = explode('/', $topic);
        return $parts[3] ?? null;
    }

    /**
     * Send USP GET request to device via MQTT
     */
    public function sendGetRequest(CpeDevice $device, array $paths): bool
    {
        $getRequest = $this->uspMessageService->createGetMessage($paths);
        $record = $this->uspMessageService->wrapInRecord(
            $getRequest,
            config('usp.controller_endpoint_id', 'proto::acs-controller'),
            $device->usp_endpoint_id
        );
        
        $binary = $this->uspMessageService->serializeRecord($record);
        return $this->publishToDevice($device, $binary);
    }

    /**
     * Send USP SET request to device via MQTT
     */
    public function sendSetRequest(CpeDevice $device, array $parameters): bool
    {
        $setRequest = $this->uspMessageService->createSetMessage($parameters);
        $record = $this->uspMessageService->wrapInRecord(
            $setRequest,
            config('usp.controller_endpoint_id', 'proto::acs-controller'),
            $device->usp_endpoint_id
        );
        
        $binary = $this->uspMessageService->serializeRecord($record);
        return $this->publishToDevice($device, $binary);
    }

    /**
     * Send USP OPERATE request to device via MQTT
     */
    public function sendOperateRequest(CpeDevice $device, string $command, array $args = []): bool
    {
        $operateRequest = $this->uspMessageService->createOperateMessage($command, $args);
        $record = $this->uspMessageService->wrapInRecord(
            $operateRequest,
            config('usp.controller_endpoint_id', 'proto::acs-controller'),
            $device->usp_endpoint_id
        );
        
        $binary = $this->uspMessageService->serializeRecord($record);
        return $this->publishToDevice($device, $binary);
    }

    /**
     * Send USP SUBSCRIPTION request to device via MQTT
     * 
     * Creates a subscription on Device.LocalAgent.Subscription.{i}. path
     * 
     * @param CpeDevice $device Target device
     * @param string $subscriptionPath Path (es: Device.LocalAgent.Subscription.1.)
     * @param array $subscriptionParams Parameters (Enable, Recipient, NotifType, ReferenceList, etc.)
     * @return bool
     */
    public function sendSubscriptionRequest(CpeDevice $device, string $subscriptionPath, array $subscriptionParams): bool
    {
        $subscriptionRequest = $this->uspMessageService->createSubscriptionMessage($subscriptionPath, $subscriptionParams);
        $record = $this->uspMessageService->wrapInRecord(
            $subscriptionRequest,
            config('usp.controller_endpoint_id', 'proto::acs-controller'),
            $device->usp_endpoint_id
        );
        
        $binary = $this->uspMessageService->serializeRecord($record);
        return $this->publishToDevice($device, $binary);
    }
}
