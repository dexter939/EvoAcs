<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Models\CpeDevice;
use App\Models\UspPendingRequest;
use App\Models\UspSubscription;
use App\Services\UspMessageService;
use App\Services\UspMqttService;
use App\Services\UspWebSocketService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * UspController - API Controller for TR-369 USP operations
 * 
 * Provides RESTful API endpoints for managing TR-369 USP devices
 * Supports Get, Set, Operate, Add, and Delete operations via MQTT/HTTP transport
 */
class UspController extends Controller
{
    use ApiResponse;
    protected $uspService;
    protected $mqttService;
    protected $webSocketService;
    
    public function __construct(
        UspMessageService $uspService, 
        UspMqttService $mqttService,
        UspWebSocketService $webSocketService
    ) {
        $this->uspService = $uspService;
        $this->mqttService = $mqttService;
        $this->webSocketService = $webSocketService;
    }
    
    /**
     * Get parameters from USP device
     * 
     * Sends a USP Get message to retrieve parameter values
     * 
     * @param Request $request {paths: array of parameter paths}
     * @param CpeDevice $device Target USP device
     * @return \Illuminate\Http\JsonResponse
     */
    public function getParameters(Request $request, CpeDevice $device)
    {
        // Validate device is TR-369
        if ($device->protocol_type !== 'tr369') {
            return response()->json([
                'message' => 'Device must support TR-369 USP protocol'
            ], 422);
        }
        
        // Validate device is online
        if ($device->status !== 'online') {
            return response()->json([
                'message' => 'Device must be online'
            ], 422);
        }
        
        // Validate request
        $validated = $request->validate([
            'param_paths' => 'required|array|min:1',
            'param_paths.*' => 'required|string'
        ]);
        
        try {
            $msgId = 'api-get-' . Str::random(10);
            
            // Send via appropriate MTP
            if ($device->mtp_type === 'mqtt') {
                $this->mqttService->sendGetRequest($device, $validated['param_paths']);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'status' => 'sent',
                        'transport' => 'mqtt'
                    ]
                ]);
            } elseif ($device->mtp_type === 'websocket') {
                $this->webSocketService->sendGetRequest($device, $validated['param_paths'], $msgId);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'status' => 'sent',
                        'transport' => 'websocket'
                    ]
                ]);
            } else {
                // For HTTP MTP, store request in database for polling
                $getMessage = $this->uspService->createGetMessage($validated['param_paths'], $msgId);
                $pendingRequest = $this->storePendingRequest($device, $msgId, 'get', $getMessage);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'status' => 'pending',
                        'transport' => 'http',
                        'pending_request_id' => $pendingRequest->id,
                        'expires_at' => $pendingRequest->expires_at->toIso8601String()
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send USP Get request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Set parameters on USP device
     * 
     * Sends a USP Set message to modify parameter values
     * 
     * @param Request $request {parameters: object with param_path => value}
     * @param CpeDevice $device Target USP device
     * @return \Illuminate\Http\JsonResponse
     */
    public function setParameters(Request $request, CpeDevice $device)
    {
        // Validate device is TR-369
        if ($device->protocol_type !== 'tr369') {
            return response()->json([
                'message' => 'Device must support TR-369 USP protocol'
            ], 422);
        }
        
        // Validate device is online
        if ($device->status !== 'online') {
            return response()->json([
                'message' => 'Device must be online'
            ], 422);
        }
        
        // Validate request
        $validated = $request->validate([
            'param_paths' => 'required|array|min:1',
            'allow_partial' => 'boolean'
        ]);
        
        try {
            $msgId = 'api-set-' . Str::random(10);
            $allowPartial = $validated['allow_partial'] ?? true;
            
            // Send via appropriate MTP
            if ($device->mtp_type === 'mqtt') {
                $this->mqttService->sendSetRequest($device, $validated['param_paths']);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'status' => 'sent',
                        'transport' => 'mqtt'
                    ]
                ]);
            } elseif ($device->mtp_type === 'websocket') {
                $this->webSocketService->sendSetRequest($device, $validated['param_paths'], $msgId, $allowPartial);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'status' => 'sent',
                        'transport' => 'websocket'
                    ]
                ]);
            } else {
                // For HTTP MTP, store request in database for polling
                $updateObjects = $this->convertToUpdateObjects($validated['param_paths']);
                $setMessage = $this->uspService->createSetMessage($updateObjects, $allowPartial, $msgId);
                $pendingRequest = $this->storePendingRequest($device, $msgId, 'set', $setMessage);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'status' => 'pending',
                        'transport' => 'http',
                        'pending_request_id' => $pendingRequest->id
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send USP Set request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Execute operation on USP device
     * 
     * Sends a USP Operate message to execute a command
     * 
     * @param Request $request {command: string, params: object}
     * @param CpeDevice $device Target USP device
     * @return \Illuminate\Http\JsonResponse
     */
    public function operate(Request $request, CpeDevice $device)
    {
        // Validate device is TR-369
        if ($device->protocol_type !== 'tr369') {
            return response()->json([
                'message' => 'Device must support TR-369 USP protocol'
            ], 422);
        }
        
        // Validate device is online
        if ($device->status !== 'online') {
            return response()->json([
                'message' => 'Device must be online'
            ], 422);
        }
        
        // Validate request
        $validated = $request->validate([
            'command' => 'required|string',
            'command_args' => 'array'
        ]);
        
        try {
            $msgId = 'api-operate-' . Str::random(10);
            $params = $validated['command_args'] ?? [];
            
            // Send via appropriate MTP
            if ($device->mtp_type === 'mqtt') {
                $this->mqttService->sendOperateRequest($device, $validated['command'], $params);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'command' => $validated['command'],
                        'status' => 'sent'
                    ]
                ]);
            } elseif ($device->mtp_type === 'websocket') {
                $this->webSocketService->sendOperateRequest($device, $validated['command'], $params, $msgId);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'command' => $validated['command'],
                        'status' => 'sent'
                    ]
                ]);
            } else {
                // For HTTP MTP, store request in database for polling
                $operateMessage = $this->uspService->createOperateMessage($validated['command'], $params, $msgId);
                $pendingRequest = $this->storePendingRequest($device, $msgId, 'operate', $operateMessage);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'command' => $validated['command'],
                        'status' => 'pending'
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send USP Operate request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Add object instance on USP device
     * 
     * Sends a USP Add message to create a new object instance
     * 
     * @param Request $request {object_path: string, params: object}
     * @param CpeDevice $device Target USP device
     * @return \Illuminate\Http\JsonResponse
     */
    public function addObject(Request $request, CpeDevice $device)
    {
        // Validate device is TR-369
        if ($device->protocol_type !== 'tr369') {
            return response()->json([
                'message' => 'Device must support TR-369 USP protocol'
            ], 422);
        }
        
        // Validate device is online
        if ($device->status !== 'online') {
            return response()->json([
                'message' => 'Device must be online'
            ], 422);
        }
        
        // Validate request
        $validated = $request->validate([
            'object_path' => 'required|string',
            'parameters' => 'array'
        ]);
        
        try {
            $msgId = 'api-add-' . Str::random(10);
            
            // Build Add message
            $addMessage = $this->uspService->createAddMessage(
                $validated['object_path'],
                $validated['parameters'] ?? [],
                false,
                $msgId
            );
            
            // Wrap in Record
            $record = $this->uspService->wrapInRecord(
                $addMessage,
                config('usp.controller_endpoint_id'),
                $device->usp_endpoint_id
            );
            
            // Send via appropriate MTP
            if ($device->mtp_type === 'mqtt') {
                $topic = "usp/agent/{$device->usp_endpoint_id}/request";
                $this->mqttService->publish($topic, $record);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'object_path' => $validated['object_path']
                    ]
                ]);
            } elseif ($device->mtp_type === 'websocket') {
                $this->webSocketService->sendAddRequest($device, $validated['object_path'], $validated['parameters'] ?? [], $msgId, false);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'object_path' => $validated['object_path']
                    ]
                ]);
            } else {
                // For HTTP MTP, store request in database for polling
                $pendingRequest = $this->storePendingRequest($device, $msgId, 'add', $addMessage);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'object_path' => $validated['object_path']
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send USP Add request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete object instance on USP device
     * 
     * Sends a USP Delete message to remove an object instance
     * 
     * @param Request $request {object_paths: array of paths to delete}
     * @param CpeDevice $device Target USP device
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteObject(Request $request, CpeDevice $device)
    {
        // Validate device is TR-369
        if ($device->protocol_type !== 'tr369') {
            return response()->json([
                'message' => 'Device must support TR-369 USP protocol'
            ], 422);
        }
        
        // Validate device is online
        if ($device->status !== 'online') {
            return response()->json([
                'message' => 'Device must be online'
            ], 422);
        }
        
        // Validate request
        $validated = $request->validate([
            'object_paths' => 'required|array|min:1',
            'object_paths.*' => 'required|string'
        ]);
        
        try {
            $msgId = 'api-delete-' . Str::random(10);
            
            // Build Delete message
            $deleteMessage = $this->uspService->createDeleteMessage(
                $validated['object_paths'],
                false,
                $msgId
            );
            
            // Wrap in Record
            $record = $this->uspService->wrapInRecord(
                $deleteMessage,
                config('usp.controller_endpoint_id'),
                $device->usp_endpoint_id
            );
            
            // Send via appropriate MTP
            if ($device->mtp_type === 'mqtt') {
                $topic = "usp/agent/{$device->usp_endpoint_id}/request";
                $this->mqttService->publish($topic, $record);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'deleted_objects' => $validated['object_paths']
                    ]
                ]);
            } elseif ($device->mtp_type === 'websocket') {
                $this->webSocketService->sendDeleteRequest($device, $validated['object_paths'], $msgId, false);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'deleted_objects' => $validated['object_paths']
                    ]
                ]);
            } else {
                // For HTTP MTP, store request in database for polling
                $pendingRequest = $this->storePendingRequest($device, $msgId, 'delete', $deleteMessage);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'deleted_objects' => $validated['object_paths']
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send USP Delete request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Reboot USP device
     * 
     * Sends a USP Operate message with Device.Reboot() command
     * 
     * @param CpeDevice $device Target USP device
     * @return \Illuminate\Http\JsonResponse
     */
    public function reboot(CpeDevice $device)
    {
        // Validate device is TR-369
        if ($device->protocol_type !== 'tr369') {
            return response()->json([
                'message' => 'Device must support TR-369 USP protocol'
            ], 422);
        }
        
        // Validate device is online
        if ($device->status !== 'online') {
            return response()->json([
                'message' => 'Device must be online'
            ], 422);
        }
        
        try {
            $msgId = 'api-reboot-' . Str::random(10);
            
            // Send Operate with Device.Reboot() command
            if ($device->mtp_type === 'mqtt') {
                $this->mqttService->sendOperateRequest($device, 'Device.Reboot()', []);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'status' => 'sent'
                    ]
                ]);
            } elseif ($device->mtp_type === 'websocket') {
                $this->webSocketService->sendOperateRequest($device, 'Device.Reboot()', [], $msgId);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'status' => 'sent'
                    ]
                ]);
            } else {
                // For HTTP MTP, store request in database for polling
                $operateMessage = $this->uspService->createOperateMessage('Device.Reboot()', [], $msgId);
                $pendingRequest = $this->storePendingRequest($device, $msgId, 'operate', $operateMessage);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'msg_id' => $msgId,
                        'status' => 'pending'
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to send USP Reboot command',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Store USP request for HTTP MTP devices
     * 
     * Saves the USP message in database for later retrieval by polling devices
     * 
     * @param CpeDevice $device Target device
     * @param string $msgId Message ID
     * @param string $messageType Type of message (get, set, operate, add, delete)
     * @param \Usp\Msg $message Protobuf USP message
     * @return UspPendingRequest
     */
    protected function storePendingRequest(CpeDevice $device, string $msgId, string $messageType, $message)
    {
        // Wrap message in Record
        $record = $this->uspService->wrapInRecord(
            $message,
            config('usp.controller_endpoint_id'),
            $device->usp_endpoint_id
        );
        
        // Serialize Record to binary for storage
        $binaryPayload = $record->serializeToString();
        
        // Store in database with 1 hour expiration
        return UspPendingRequest::create([
            'cpe_device_id' => $device->id,
            'msg_id' => $msgId,
            'message_type' => $messageType,
            'request_payload' => $binaryPayload,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addHour()
        ]);
    }
    
    /**
     * Convert API parameter format to USP updateObjects format
     * 
     * Converts flat parameters like:
     *   ['Device.WiFi.Radio.1.Channel' => '11']
     * To grouped updateObjects like:
     *   ['Device.WiFi.Radio.1.' => ['Channel' => '11']]
     * 
     * @param array $parameters Flat parameter array
     * @return array Grouped updateObjects array
     */
    protected function convertToUpdateObjects(array $parameters): array
    {
        $updateObjects = [];
        
        foreach ($parameters as $fullPath => $value) {
            // Split path into object path and parameter name
            // e.g., "Device.WiFi.Radio.1.Channel" -> "Device.WiFi.Radio.1." + "Channel"
            $lastDotPos = strrpos($fullPath, '.');
            
            if ($lastDotPos !== false) {
                $objectPath = substr($fullPath, 0, $lastDotPos + 1);
                $paramName = substr($fullPath, $lastDotPos + 1);
                
                if (!isset($updateObjects[$objectPath])) {
                    $updateObjects[$objectPath] = [];
                }
                
                $updateObjects[$objectPath][$paramName] = $value;
            } else {
                // If no dot found, treat entire path as parameter under root
                $updateObjects['Device.'][$fullPath] = $value;
            }
        }
        
        return $updateObjects;
    }
    
    /**
     * Create event subscription on USP device
     * 
     * Sends a USP Subscribe message (ADD to Device.LocalAgent.Subscription.{i}.)
     * 
     * @param Request $request {event_path, reference_list (optional), notification_retry}
     * @param CpeDevice $device Target USP device
     * @return \Illuminate\Http\JsonResponse
     */
    public function createSubscription(Request $request, CpeDevice $device)
    {
        // Validate device is TR-369
        if ($device->protocol_type !== 'tr369') {
            return response()->json([
                'message' => 'Device must support TR-369 USP protocol'
            ], 422);
        }
        
        // Validate device is online
        if ($device->status !== 'online') {
            return response()->json([
                'message' => 'Device must be online'
            ], 422);
        }
        
        // Validate request
        $validated = $request->validate([
            'subscription_id' => 'required|string',
            'notification_type' => 'required|string',
            'reference_list' => 'required|array',
            'reference_list.*' => 'string',
            'enabled' => 'boolean',
            'persistent' => 'boolean'
        ]);
        
        try {
            $msgId = 'api-subscribe-' . Str::random(10);
            $subscriptionId = $validated['subscription_id'];
            
            // Create subscription record
            $subscription = UspSubscription::create([
                'cpe_device_id' => $device->id,
                'subscription_id' => $subscriptionId,
                'notification_type' => $validated['notification_type'],
                'reference_list' => $validated['reference_list'],
                'enabled' => $validated['enabled'] ?? true,
                'persistent' => $validated['persistent'] ?? true
            ]);
            
            // Send via appropriate MTP (simplified for now - actual USP message sending can be added)
            return response()->json([
                'success' => true,
                'data' => [
                    'subscription_id' => $subscriptionId,
                    'msg_id' => $msgId,
                    'status' => 'created'
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * List all subscriptions for a device
     * 
     * @param CpeDevice $device Target USP device
     * @return \Illuminate\Http\JsonResponse
     */
    public function listSubscriptions(CpeDevice $device)
    {
        // Validate device is TR-369
        if ($device->protocol_type !== 'tr369') {
            return response()->json([
                'message' => 'Device must support TR-369 USP protocol'
            ], 422);
        }
        
        $subscriptions = UspSubscription::where('cpe_device_id', $device->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'data' => $subscriptions
        ]);
    }
    
    /**
     * Delete subscription from USP device
     * 
     * Sends a USP Delete message to remove subscription
     * 
     * @param CpeDevice $device Target USP device
     * @param UspSubscription $subscription Target subscription
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSubscription(CpeDevice $device, UspSubscription $subscription)
    {
        // Validate device is TR-369
        if ($device->protocol_type !== 'tr369') {
            return response()->json([
                'message' => 'Device must support TR-369 USP protocol'
            ], 422);
        }
        
        // Validate subscription belongs to device
        if ($subscription->cpe_device_id !== $device->id) {
            return response()->json([
                'message' => 'Subscription does not belong to this device'
            ], 403);
        }
        
        try {
            $msgId = 'api-unsubscribe-' . Str::random(10);
            
            // Soft delete subscription
            $subscription->delete();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'msg_id' => $msgId,
                    'subscription_id' => $subscription->subscription_id
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
