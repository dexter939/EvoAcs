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
                'error' => 'Device is not a TR-369 USP device',
                'device_protocol' => $device->protocol_type
            ], 400);
        }
        
        // Validate request
        $validated = $request->validate([
            'paths' => 'required|array|min:1',
            'paths.*' => 'required|string'
        ]);
        
        try {
            $msgId = 'api-get-' . Str::random(10);
            
            // Send via appropriate MTP
            if ($device->mtp_type === 'mqtt') {
                $this->mqttService->sendGetRequest($device, $validated['paths'], $msgId);
                
                return response()->json([
                    'message' => 'USP Get request sent via MQTT',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'paths' => $validated['paths'],
                    'mtp' => 'mqtt'
                ]);
            } elseif ($device->mtp_type === 'websocket') {
                $this->webSocketService->sendGetRequest($device, $validated['paths'], $msgId);
                
                return response()->json([
                    'message' => 'USP Get request sent via WebSocket',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'paths' => $validated['paths'],
                    'mtp' => 'websocket'
                ]);
            } else {
                // For HTTP MTP, store request in database for polling
                $getMessage = $this->uspService->createGetMessage($validated['paths'], $msgId);
                $pendingRequest = $this->storePendingRequest($device, $msgId, 'get', $getMessage);
                
                return response()->json([
                    'message' => 'USP Get request stored for HTTP polling',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'paths' => $validated['paths'],
                    'mtp' => 'http',
                    'pending_request_id' => $pendingRequest->id,
                    'expires_at' => $pendingRequest->expires_at->toIso8601String()
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to send USP Get request',
                'message' => $e->getMessage()
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
                'error' => 'Device is not a TR-369 USP device',
                'device_protocol' => $device->protocol_type
            ], 400);
        }
        
        // Validate request
        $validated = $request->validate([
            'parameters' => 'required|array|min:1',
            'allow_partial' => 'boolean'
        ]);
        
        try {
            $msgId = 'api-set-' . Str::random(10);
            $allowPartial = $validated['allow_partial'] ?? true;
            
            // Send via appropriate MTP
            if ($device->mtp_type === 'mqtt') {
                $this->mqttService->sendSetRequest($device, $validated['parameters'], $msgId, $allowPartial);
                
                return response()->json([
                    'message' => 'USP Set request sent via MQTT',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'parameters' => $validated['parameters'],
                    'allow_partial' => $allowPartial,
                    'mtp' => 'mqtt'
                ]);
            } elseif ($device->mtp_type === 'websocket') {
                $this->webSocketService->sendSetRequest($device, $validated['parameters'], $msgId, $allowPartial);
                
                return response()->json([
                    'message' => 'USP Set request sent via WebSocket',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'parameters' => $validated['parameters'],
                    'allow_partial' => $allowPartial,
                    'mtp' => 'websocket'
                ]);
            } else {
                // For HTTP MTP, store request in database for polling
                $updateObjects = $this->convertToUpdateObjects($validated['parameters']);
                $setMessage = $this->uspService->createSetMessage($updateObjects, $allowPartial, $msgId);
                $pendingRequest = $this->storePendingRequest($device, $msgId, 'set', $setMessage);
                
                return response()->json([
                    'message' => 'USP Set request stored for HTTP polling',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'parameters' => $validated['parameters'],
                    'mtp' => 'http',
                    'pending_request_id' => $pendingRequest->id,
                    'expires_at' => $pendingRequest->expires_at->toIso8601String()
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to send USP Set request',
                'message' => $e->getMessage()
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
                'error' => 'Device is not a TR-369 USP device',
                'device_protocol' => $device->protocol_type
            ], 400);
        }
        
        // Validate request
        $validated = $request->validate([
            'command' => 'required|string',
            'params' => 'array'
        ]);
        
        try {
            $msgId = 'api-operate-' . Str::random(10);
            $params = $validated['params'] ?? [];
            
            // Send via appropriate MTP
            if ($device->mtp_type === 'mqtt') {
                $this->mqttService->sendOperateRequest($device, $validated['command'], $params, $msgId);
                
                return response()->json([
                    'message' => 'USP Operate request sent via MQTT',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'command' => $validated['command'],
                    'params' => $params,
                    'mtp' => 'mqtt'
                ]);
            } elseif ($device->mtp_type === 'websocket') {
                $this->webSocketService->sendOperateRequest($device, $validated['command'], $params, $msgId);
                
                return response()->json([
                    'message' => 'USP Operate request sent via WebSocket',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'command' => $validated['command'],
                    'params' => $params,
                    'mtp' => 'websocket'
                ]);
            } else {
                // For HTTP MTP, store request in database for polling
                $operateMessage = $this->uspService->createOperateMessage($validated['command'], $params, $msgId);
                $pendingRequest = $this->storePendingRequest($device, $msgId, 'operate', $operateMessage);
                
                return response()->json([
                    'message' => 'USP Operate request stored for HTTP polling',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'command' => $validated['command'],
                    'mtp' => 'http',
                    'pending_request_id' => $pendingRequest->id,
                    'expires_at' => $pendingRequest->expires_at->toIso8601String()
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to send USP Operate request',
                'message' => $e->getMessage()
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
                'error' => 'Device is not a TR-369 USP device',
                'device_protocol' => $device->protocol_type
            ], 400);
        }
        
        // Validate request
        $validated = $request->validate([
            'object_path' => 'required|string',
            'params' => 'array'
        ]);
        
        try {
            $msgId = 'api-add-' . Str::random(10);
            
            // Build Add message
            $addMessage = $this->uspService->createAddMessage(
                $validated['object_path'],
                $validated['params'] ?? [],
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
                    'message' => 'USP Add request sent via MQTT',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'object_path' => $validated['object_path'],
                    'params' => $validated['params'] ?? [],
                    'mtp' => 'mqtt'
                ]);
            } elseif ($device->mtp_type === 'websocket') {
                $this->webSocketService->sendAddRequest($device, $validated['object_path'], $validated['params'] ?? [], $msgId, false);
                
                return response()->json([
                    'message' => 'USP Add request sent via WebSocket',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'object_path' => $validated['object_path'],
                    'params' => $validated['params'] ?? [],
                    'mtp' => 'websocket'
                ]);
            } else {
                // For HTTP MTP, store request in database for polling
                $pendingRequest = $this->storePendingRequest($device, $msgId, 'add', $addMessage);
                
                return response()->json([
                    'message' => 'USP Add request stored for HTTP polling',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'object_path' => $validated['object_path'],
                    'mtp' => 'http',
                    'pending_request_id' => $pendingRequest->id,
                    'expires_at' => $pendingRequest->expires_at->toIso8601String()
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to send USP Add request',
                'message' => $e->getMessage()
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
                'error' => 'Device is not a TR-369 USP device',
                'device_protocol' => $device->protocol_type
            ], 400);
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
                    'message' => 'USP Delete request sent via MQTT',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'object_paths' => $validated['object_paths'],
                    'mtp' => 'mqtt'
                ]);
            } elseif ($device->mtp_type === 'websocket') {
                $this->webSocketService->sendDeleteRequest($device, $validated['object_paths'], $msgId, false);
                
                return response()->json([
                    'message' => 'USP Delete request sent via WebSocket',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'object_paths' => $validated['object_paths'],
                    'mtp' => 'websocket'
                ]);
            } else {
                // For HTTP MTP, store request in database for polling
                $pendingRequest = $this->storePendingRequest($device, $msgId, 'delete', $deleteMessage);
                
                return response()->json([
                    'message' => 'USP Delete request stored for HTTP polling',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'object_paths' => $validated['object_paths'],
                    'mtp' => 'http',
                    'pending_request_id' => $pendingRequest->id,
                    'expires_at' => $pendingRequest->expires_at->toIso8601String()
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to send USP Delete request',
                'message' => $e->getMessage()
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
                'error' => 'Device is not a TR-369 USP device',
                'device_protocol' => $device->protocol_type
            ], 400);
        }
        
        try {
            $msgId = 'api-reboot-' . Str::random(10);
            
            // Send Operate with Device.Reboot() command
            if ($device->mtp_type === 'mqtt') {
                $this->mqttService->sendOperateRequest($device, 'Device.Reboot()', [], $msgId);
                
                return response()->json([
                    'message' => 'USP Reboot command sent via MQTT',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'mtp' => 'mqtt'
                ]);
            } elseif ($device->mtp_type === 'websocket') {
                $this->webSocketService->sendOperateRequest($device, 'Device.Reboot()', [], $msgId);
                
                return response()->json([
                    'message' => 'USP Reboot command sent via WebSocket',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'mtp' => 'websocket'
                ]);
            } else {
                // For HTTP MTP, store request in database for polling
                $operateMessage = $this->uspService->createOperateMessage('Device.Reboot()', [], $msgId);
                $pendingRequest = $this->storePendingRequest($device, $msgId, 'operate', $operateMessage);
                
                return response()->json([
                    'message' => 'USP Reboot command stored for HTTP polling',
                    'msg_id' => $msgId,
                    'device' => $device->serial_number,
                    'mtp' => 'http',
                    'pending_request_id' => $pendingRequest->id,
                    'expires_at' => $pendingRequest->expires_at->toIso8601String()
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
                'error' => 'Device is not a TR-369 USP device',
                'device_protocol' => $device->protocol_type
            ], 400);
        }
        
        // Validate request
        $validated = $request->validate([
            'event_path' => 'required|string',
            'reference_list' => 'nullable|array',
            'reference_list.*' => 'string',
            'notification_retry' => 'nullable|boolean'
        ]);
        
        try {
            $msgId = 'api-subscribe-' . Str::random(10);
            $subscriptionId = (string) Str::uuid();
            $uspService = $this->uspService;
            $mqttService = $this->mqttService;
            
            // Use transaction to ensure atomicity
            return DB::transaction(function () use ($device, $validated, $msgId, $subscriptionId, $uspService, $mqttService) {
                // Create subscription record
                $subscription = UspSubscription::create([
                    'cpe_device_id' => $device->id,
                    'subscription_id' => $subscriptionId,
                    'event_path' => $validated['event_path'],
                    'reference_list' => $validated['reference_list'] ?? [],
                    'notification_retry' => $validated['notification_retry'] ?? true,
                    'is_active' => true
                ]);
                
                // Send via appropriate MTP
                if ($device->mtp_type === 'mqtt') {
                    // Build subscription params for USP Subscribe message
                    $subscriptionParams = [
                        'ID' => $subscriptionId,
                        'Enable' => 'true',
                        'NotifType' => 'ValueChange',  // Default to ValueChange
                        'ReferenceList' => $validated['reference_list'] ?? [],
                        'NotifRetry' => $validated['notification_retry'] ?? true
                    ];
                    
                    $mqttService->sendSubscriptionRequest(
                        $device,
                        $validated['event_path'],
                        $subscriptionParams
                    );
                    
                    return response()->json([
                        'message' => 'USP Subscribe request sent via MQTT',
                        'msg_id' => $msgId,
                        'subscription_id' => $subscriptionId,
                        'device' => $device->serial_number,
                        'event_path' => $validated['event_path'],
                        'mtp' => 'mqtt',
                        'subscription' => $subscription
                    ], 201);
                } elseif ($device->mtp_type === 'websocket') {
                    $subscriptionParams = [
                        'ID' => $subscriptionId,
                        'Enable' => 'true',
                        'NotifType' => 'ValueChange',
                        'ReferenceList' => $validated['reference_list'] ?? [],
                        'NotifRetry' => $validated['notification_retry'] ?? true
                    ];
                    
                    $this->webSocketService->sendSubscriptionRequest($device, $validated['event_path'], $subscriptionParams, $msgId);
                    
                    return response()->json([
                        'message' => 'USP Subscribe request sent via WebSocket',
                        'msg_id' => $msgId,
                        'subscription_id' => $subscriptionId,
                        'device' => $device->serial_number,
                        'event_path' => $validated['event_path'],
                        'mtp' => 'websocket',
                        'subscription' => $subscription
                    ], 201);
                } else {
                    // For HTTP MTP, store request
                    $subscribeMessage = $uspService->createSubscribeMessage(
                        $validated['event_path'],
                        $subscriptionId,
                        $validated['reference_list'] ?? [],
                        $validated['notification_retry'] ?? true,
                        $msgId
                    );
                    $pendingRequest = $this->storePendingRequest($device, $msgId, 'add', $subscribeMessage);
                    
                    return response()->json([
                        'message' => 'USP Subscribe request stored for HTTP polling',
                        'msg_id' => $msgId,
                        'subscription_id' => $subscriptionId,
                        'device' => $device->serial_number,
                        'event_path' => $validated['event_path'],
                        'mtp' => 'http',
                        'expires_at' => $pendingRequest->expires_at,
                        'subscription' => $subscription
                    ], 201);
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create subscription',
                'message' => $e->getMessage()
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
                'error' => 'Device is not a TR-369 USP device',
                'device_protocol' => $device->protocol_type
            ], 400);
        }
        
        $subscriptions = UspSubscription::where('cpe_device_id', $device->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'device' => $device->serial_number,
            'total' => $subscriptions->count(),
            'subscriptions' => $subscriptions
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
                'error' => 'Device is not a TR-369 USP device',
                'device_protocol' => $device->protocol_type
            ], 400);
        }
        
        // Validate subscription belongs to device
        if ($subscription->cpe_device_id !== $device->id) {
            return response()->json([
                'error' => 'Subscription does not belong to this device'
            ], 403);
        }
        
        try {
            $msgId = 'api-unsubscribe-' . Str::random(10);
            $objectPath = "Device.LocalAgent.Subscription.{$subscription->subscription_id}.";
            $mqttService = $this->mqttService;
            $uspService = $this->uspService;
            $storePendingRequest = function($device, $msgId, $operationType, $message) {
                return $this->storePendingRequest($device, $msgId, $operationType, $message);
            };
            
            // Use transaction to ensure atomicity
            return DB::transaction(function () use ($device, $subscription, $msgId, $objectPath, $mqttService, $uspService, $storePendingRequest) {
                // Mark as inactive immediately
                $subscription->update(['is_active' => false]);
                
                // Send DELETE message via appropriate MTP
                if ($device->mtp_type === 'mqtt') {
                    $mqttService->sendDeleteRequest($device, [$objectPath], $msgId);
                    
                    return response()->json([
                        'message' => 'Subscription deleted successfully, USP Delete request sent via MQTT',
                        'msg_id' => $msgId,
                        'subscription_id' => $subscription->subscription_id,
                        'device' => $device->serial_number,
                        'mtp' => 'mqtt'
                    ]);
                } elseif ($device->mtp_type === 'websocket') {
                    $this->webSocketService->sendDeleteRequest($device, [$objectPath], $msgId, false);
                    
                    return response()->json([
                        'message' => 'Subscription deleted successfully, USP Delete request sent via WebSocket',
                        'msg_id' => $msgId,
                        'subscription_id' => $subscription->subscription_id,
                        'device' => $device->serial_number,
                        'mtp' => 'websocket'
                    ]);
                } else {
                    // For HTTP MTP, store request (fix: add allowPartial parameter)
                    $deleteMessage = $uspService->createDeleteMessage([$objectPath], false, $msgId);
                    $pendingRequest = $storePendingRequest($device, $msgId, 'delete', $deleteMessage);
                    
                    return response()->json([
                        'message' => 'Subscription deleted successfully, USP Delete request stored for HTTP polling',
                        'msg_id' => $msgId,
                        'subscription_id' => $subscription->subscription_id,
                        'device' => $device->serial_number,
                        'mtp' => 'http',
                        'expires_at' => $pendingRequest->expires_at
                    ]);
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete subscription',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
