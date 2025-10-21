<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Models\CpeDevice;
use App\Services\TR181Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * TR-181 Device:2 Data Model API Controller
 * 
 * Provides RESTful API endpoints for TR-181 parameter management
 * Compliant with BBF TR-181 Issue 2 specification
 */
class TR181Controller extends Controller
{
    use ApiResponse;

    protected TR181Service $tr181;

    public function __construct(TR181Service $tr181Service)
    {
        $this->tr181 = $tr181Service;
    }

    /**
     * Get all TR-181 parameters for a device
     * 
     * GET /api/v1/devices/{device}/tr181/parameters
     * 
     * @param CpeDevice $device
     * @return JsonResponse
     */
    public function getAllParameters(CpeDevice $device): JsonResponse
    {
        try {
            $parameters = $this->tr181->getAllParameters($device);

            return $this->successResponse($parameters, 'TR-181 parameters retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve TR-181 parameters: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get specific TR-181 parameter namespace
     * 
     * GET /api/v1/devices/{device}/tr181/device-info
     * GET /api/v1/devices/{device}/tr181/management-server
     * GET /api/v1/devices/{device}/tr181/wifi
     * etc.
     * 
     * @param CpeDevice $device
     * @param string $namespace
     * @return JsonResponse
     */
    public function getNamespace(CpeDevice $device, string $namespace): JsonResponse
    {
        try {
            $parameters = match ($namespace) {
                'device-info' => $this->tr181->getDeviceInfo($device),
                'management-server' => $this->tr181->getManagementServer($device),
                'time' => $this->tr181->getTimeConfiguration($device),
                'wifi' => $this->tr181->getWiFiConfiguration($device),
                'lan' => $this->tr181->getLANConfiguration($device),
                'dhcp' => $this->tr181->getDHCPv4Configuration($device),
                'ip' => $this->tr181->getIPConfiguration($device),
                'hosts' => $this->tr181->getConnectedHosts($device),
                default => throw new \InvalidArgumentException("Invalid namespace: {$namespace}")
            };

            return $this->successResponse($parameters, "TR-181 {$namespace} parameters retrieved successfully");
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve parameters: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Set TR-181 parameters (SetParameterValues)
     * 
     * POST /api/v1/devices/{device}/tr181/parameters
     * 
     * Body:
     * {
     *   "parameters": {
     *     "Device.ManagementServer.PeriodicInformInterval": 600,
     *     "Device.WiFi.Radio.1.Channel": 6,
     *     "Device.WiFi.SSID.1.SSID": "MyNetwork"
     *   }
     * }
     * 
     * @param Request $request
     * @param CpeDevice $device
     * @return JsonResponse
     */
    public function setParameters(Request $request, CpeDevice $device): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'parameters' => 'required|array',
            'parameters.*' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $parameters = $request->input('parameters');

        // Validate parameter names
        foreach (array_keys($parameters) as $paramName) {
            if (!$this->tr181->isValidParameter($paramName)) {
                return $this->errorResponse("Invalid TR-181 parameter: {$paramName}", 400);
            }
        }

        try {
            $results = $this->tr181->setParameters($device, $parameters);

            $successCount = collect($results)->where('status', 'success')->count();
            $failureCount = collect($results)->where('status', 'error')->count();

            return $this->successResponse([
                'results' => $results,
                'summary' => [
                    'total' => count($results),
                    'success' => $successCount,
                    'failed' => $failureCount,
                ]
            ], "Set {$successCount} parameters successfully");
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to set parameters: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single TR-181 parameter value
     * 
     * GET /api/v1/devices/{device}/tr181/parameter?name=Device.DeviceInfo.SoftwareVersion
     * 
     * @param Request $request
     * @param CpeDevice $device
     * @return JsonResponse
     */
    public function getParameter(Request $request, CpeDevice $device): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $paramName = $request->input('name');

        if (!$this->tr181->isValidParameter($paramName)) {
            return $this->errorResponse("Invalid TR-181 parameter: {$paramName}", 400);
        }

        try {
            // Get all parameters and filter
            $allParams = $this->tr181->getAllParameters($device);

            if (!isset($allParams[$paramName])) {
                return $this->errorResponse("Parameter not found: {$paramName}", 404);
            }

            return $this->successResponse([
                'parameter' => $paramName,
                'value' => $allParams[$paramName],
            ], 'Parameter retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve parameter: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Device.DeviceInfo (device identification)
     * 
     * GET /api/v1/devices/{device}/tr181/device-info
     * 
     * @param CpeDevice $device
     * @return JsonResponse
     */
    public function getDeviceInfo(CpeDevice $device): JsonResponse
    {
        return $this->getNamespace($device, 'device-info');
    }

    /**
     * Get Device.ManagementServer (ACS connection settings)
     * 
     * GET /api/v1/devices/{device}/tr181/management-server
     * 
     * @param CpeDevice $device
     * @return JsonResponse
     */
    public function getManagementServer(CpeDevice $device): JsonResponse
    {
        return $this->getNamespace($device, 'management-server');
    }

    /**
     * Update Management Server settings
     * 
     * PUT /api/v1/devices/{device}/tr181/management-server
     * 
     * @param Request $request
     * @param CpeDevice $device
     * @return JsonResponse
     */
    public function updateManagementServer(Request $request, CpeDevice $device): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'PeriodicInformInterval' => 'sometimes|integer|min:60',
            'ConnectionRequestUsername' => 'sometimes|string|max:255',
            'ConnectionRequestPassword' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $this->tr181->setManagementServer($device, $request->all());

            return $this->successResponse(
                $this->tr181->getManagementServer($device),
                'Management server settings updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update management server: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Device.WiFi (WiFi radios and SSIDs)
     * 
     * GET /api/v1/devices/{device}/tr181/wifi
     * 
     * @param CpeDevice $device
     * @return JsonResponse
     */
    public function getWiFi(CpeDevice $device): JsonResponse
    {
        return $this->getNamespace($device, 'wifi');
    }

    /**
     * Get Device.LAN (LAN interfaces)
     * 
     * GET /api/v1/devices/{device}/tr181/lan
     * 
     * @param CpeDevice $device
     * @return JsonResponse
     */
    public function getLAN(CpeDevice $device): JsonResponse
    {
        return $this->getNamespace($device, 'lan');
    }

    /**
     * Get Device.Hosts (connected devices)
     * 
     * GET /api/v1/devices/{device}/tr181/hosts
     * 
     * @param CpeDevice $device
     * @return JsonResponse
     */
    public function getHosts(CpeDevice $device): JsonResponse
    {
        return $this->getNamespace($device, 'hosts');
    }

    /**
     * Get Device.DHCPv4 (DHCP configuration)
     * 
     * GET /api/v1/devices/{device}/tr181/dhcp
     * 
     * @param CpeDevice $device
     * @return JsonResponse
     */
    public function getDHCP(CpeDevice $device): JsonResponse
    {
        return $this->getNamespace($device, 'dhcp');
    }

    /**
     * Validate TR-181 parameter name
     * 
     * GET /api/v1/tr181/validate?parameter=Device.WiFi.Radio.1.Channel
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validateParameter(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'parameter' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $paramName = $request->input('parameter');
        $isValid = $this->tr181->isValidParameter($paramName);

        return $this->successResponse([
            'parameter' => $paramName,
            'valid' => $isValid,
        ], $isValid ? 'Parameter is valid' : 'Parameter is invalid');
    }
}
