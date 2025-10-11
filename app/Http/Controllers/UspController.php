<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Services\UspMessageService;
use App\Models\CpeDevice;
use App\Models\DeviceParameter;
use Illuminate\Support\Facades\Log;

/**
 * USP Controller - TR-369 Protocol Handler
 * 
 * Gestisce i messaggi USP in arrivo da dispositivi TR-369
 * Handles incoming USP messages from TR-369 devices
 */
class UspController extends Controller
{
    protected UspMessageService $uspService;

    public function __construct(UspMessageService $uspService)
    {
        $this->uspService = $uspService;
    }

    /**
     * Endpoint principale USP - Riceve USP Records
     * Main USP endpoint - Receives USP Records
     * 
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function handleUspMessage(Request $request): Response|JsonResponse
    {
        try {
            // Get binary payload from request body
            $binaryPayload = $request->getContent();

            if (empty($binaryPayload)) {
                return $this->errorResponse('Empty payload received', 400);
            }

            Log::info('USP Record received', [
                'size' => strlen($binaryPayload),
                'content_type' => $request->header('Content-Type')
            ]);

            // Deserialize USP Record
            $record = $this->uspService->deserializeRecord($binaryPayload);

            // Extract message from record
            $msg = $this->uspService->extractMessageFromRecord($record);

            if (!$msg) {
                return $this->errorResponse('Failed to extract USP message from Record', 400);
            }

            // Get message metadata
            $fromId = $record->getFromId();
            $toId = $record->getToId();
            $msgType = $this->uspService->getMessageType($msg);
            $msgId = $msg->getHeader()->getMsgId();

            Log::info('USP Message extracted', [
                'from' => $fromId,
                'to' => $toId,
                'type' => $msgType,
                'msg_id' => $msgId
            ]);

            // Find or create device
            $device = $this->findOrCreateDevice($fromId, $request);

            // Process message based on type
            $responseMsg = match($msgType) {
                'GET' => $this->handleGet($msg, $device),
                'SET' => $this->handleSet($msg, $device),
                'ADD' => $this->handleAdd($msg, $device),
                'DELETE' => $this->handleDelete($msg, $device),
                'OPERATE' => $this->handleOperate($msg, $device),
                'NOTIFY' => $this->handleNotify($msg, $device),
                'GET_RESP', 'SET_RESP', 'ADD_RESP', 'DELETE_RESP', 'OPERATE_RESP' => 
                    $this->handleResponse($msg, $device),
                default => $this->createErrorMessage($msgId, 9000, "Unsupported message type: {$msgType}")
            };

            // Wrap response in Record
            $responseRecord = $this->uspService->wrapInRecord(
                $responseMsg,
                $fromId,  // Send back to sender
                $toId,    // From our controller endpoint
                $record->getVersion()
            );

            // Serialize and return
            $responseBinary = $this->uspService->serializeRecord($responseRecord);

            Log::info('USP Response sent', [
                'to' => $fromId,
                'type' => $this->uspService->getMessageType($responseMsg),
                'size' => strlen($responseBinary)
            ]);

            return response($responseBinary, 200)
                ->header('Content-Type', 'application/octet-stream');

        } catch (\Exception $e) {
            Log::error('USP processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Trova o crea un dispositivo USP
     * Find or create USP device
     */
    protected function findOrCreateDevice(string $endpointId, Request $request): CpeDevice
    {
        $device = CpeDevice::where('usp_endpoint_id', $endpointId)->first();

        if (!$device) {
            // Auto-register new USP device
            // USP devices don't have OUI like TR-069, use default value
            $device = CpeDevice::create([
                'serial_number' => 'USP-' . substr(md5($endpointId), 0, 10),
                'oui' => '000000', // Default OUI for USP devices
                'product_class' => 'USP Device',
                'protocol_type' => 'tr369',
                'usp_endpoint_id' => $endpointId,
                'ip_address' => $request->ip(),
                'status' => 'online',
                'last_inform' => now(),
                'last_contact' => now(),
            ]);

            Log::info('New USP device auto-registered', [
                'endpoint_id' => $endpointId,
                'device_id' => $device->id
            ]);
        } else {
            // Update last contact
            $device->update([
                'last_contact' => now(),
                'status' => 'online',
                'ip_address' => $request->ip()
            ]);
        }

        return $device;
    }

    /**
     * Gestisce richiesta GET
     * Handle GET request
     */
    protected function handleGet($msg, CpeDevice $device)
    {
        $msgId = $msg->getHeader()->getMsgId();
        $get = $msg->getBody()->getRequest()->getGet();
        $paramPaths = $get->getParamPaths();

        Log::info('Processing GET request', [
            'device_id' => $device->id,
            'paths' => iterator_to_array($paramPaths)
        ]);

        // Query device parameters
        $results = [];
        foreach ($paramPaths as $path) {
            // Query from device_parameters table
            $params = DeviceParameter::where('cpe_device_id', $device->id)
                ->where('parameter_path', 'LIKE', $path . '%')
                ->get();

            foreach ($params as $param) {
                $results[$param->parameter_path] = $param->parameter_value;
            }

            // If no results, return mock data for demo
            if (empty($results)) {
                $results[$path . 'Manufacturer'] = $device->manufacturer ?? 'Unknown';
                $results[$path . 'ModelName'] = $device->model_name ?? 'TR-369 Device';
                $results[$path . 'SoftwareVersion'] = $device->software_version ?? '1.0';
            }
        }

        return $this->uspService->createGetResponseMessage($msgId, $results);
    }

    /**
     * Gestisce richiesta SET
     * Handle SET request
     */
    protected function handleSet($msg, CpeDevice $device)
    {
        $msgId = $msg->getHeader()->getMsgId();
        $set = $msg->getBody()->getRequest()->getSet();
        $updateObjs = $set->getUpdateObjs();

        Log::info('Processing SET request', [
            'device_id' => $device->id,
            'obj_count' => count($updateObjs)
        ]);

        $updatedParams = [];
        
        // Process each update object
        foreach ($updateObjs as $updateObj) {
            $objPath = $updateObj->getObjPath();
            $paramSettings = $updateObj->getParamSettings();

            foreach ($paramSettings as $setting) {
                $paramName = $setting->getParam();
                $paramValue = $setting->getValue();

                // Update or create parameter
                DeviceParameter::updateOrCreate(
                    [
                        'cpe_device_id' => $device->id,
                        'parameter_path' => $objPath . $paramName
                    ],
                    [
                        'parameter_value' => $paramValue,
                        'parameter_type' => 'string',
                        'is_writable' => true,
                        'last_update' => now()
                    ]
                );

                Log::info('Parameter updated', [
                    'device_id' => $device->id,
                    'path' => $objPath . $paramName,
                    'value' => $paramValue
                ]);
                
                $updatedParams[$objPath . $paramName] = $paramValue;
            }
        }

        // Return proper SET_RESP message
        return $this->uspService->createSetResponseMessage($msgId, $updatedParams ?? []);
    }

    /**
     * Gestisce richiesta ADD
     * Handle ADD request
     */
    protected function handleAdd($msg, CpeDevice $device)
    {
        $msgId = $msg->getHeader()->getMsgId();
        
        Log::info('Processing ADD request', [
            'device_id' => $device->id
        ]);

        // Return proper ADD_RESP message
        return $this->uspService->createAddResponseMessage($msgId, []);
    }

    /**
     * Gestisce richiesta DELETE
     * Handle DELETE request
     */
    protected function handleDelete($msg, CpeDevice $device)
    {
        $msgId = $msg->getHeader()->getMsgId();
        
        Log::info('Processing DELETE request', [
            'device_id' => $device->id
        ]);

        // Return proper DELETE_RESP message
        return $this->uspService->createDeleteResponseMessage($msgId, []);
    }

    /**
     * Gestisce richiesta OPERATE
     * Handle OPERATE request
     */
    protected function handleOperate($msg, CpeDevice $device)
    {
        $msgId = $msg->getHeader()->getMsgId();
        $operate = $msg->getBody()->getRequest()->getOperate();
        $command = $operate->getCommand();

        Log::info('Processing OPERATE request', [
            'device_id' => $device->id,
            'command' => $command
        ]);

        // Return proper OPERATE_RESP message
        return $this->uspService->createOperateResponseMessage($msgId, $command, []);
    }

    /**
     * Gestisce notifica NOTIFY
     * Handle NOTIFY notification
     */
    protected function handleNotify($msg, CpeDevice $device)
    {
        $msgId = $msg->getHeader()->getMsgId();
        
        Log::info('Processing NOTIFY', [
            'device_id' => $device->id
        ]);

        // NOTIFY messages don't require a response in most cases
        // Return GET_RESP as acknowledgment for now
        return $this->uspService->createGetResponseMessage($msgId, ['Status' => 'Received']);
    }

    /**
     * Gestisce messaggi di risposta
     * Handle response messages
     */
    protected function handleResponse($msg, CpeDevice $device)
    {
        $msgId = $msg->getHeader()->getMsgId();
        $msgType = $this->uspService->getMessageType($msg);

        Log::info('Received response message', [
            'device_id' => $device->id,
            'type' => $msgType,
            'msg_id' => $msgId
        ]);

        // Response messages don't need a reply
        // Just log and return simple acknowledgment
        return $this->uspService->createGetResponseMessage($msgId, ['Ack' => 'Received']);
    }

    /**
     * Crea messaggio di errore USP
     * Create USP error message
     */
    protected function createErrorMessage(string $msgId, int $errorCode, string $errorMsg)
    {
        return $this->uspService->createErrorMessage($msgId, $errorCode, $errorMsg);
    }

    /**
     * Risposta HTTP di errore
     * HTTP error response
     */
    protected function errorResponse(string $message, int $code): JsonResponse
    {
        return response()->json([
            'error' => $message
        ], $code);
    }
}
