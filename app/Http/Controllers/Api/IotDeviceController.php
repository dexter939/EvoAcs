<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CpeDevice;
use App\Models\SmartHomeDevice;
use App\Models\IotService;
use App\Services\IotProvisioningService;
use Illuminate\Http\Request;

class IotDeviceController extends Controller
{
    protected $iotService;

    public function __construct(IotProvisioningService $iotService)
    {
        $this->iotService = $iotService;
    }

    public function listDevices(CpeDevice $device)
    {
        return response()->json($device->smartHomeDevices()->with('cpeDevice')->get());
    }

    public function provisionDevice(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'device_class' => 'required|string',
            'device_name' => 'required|string',
            'protocol' => 'required|string',
            'ieee_address' => 'nullable|string',
            'capabilities' => 'nullable|array'
        ]);
        $smartDevice = $this->iotService->provisionSmartDevice($device, $validated);
        return response()->json(['success' => true, 'device' => $smartDevice], 201);
    }

    public function updateState(Request $request, SmartHomeDevice $smartDevice)
    {
        $validated = $request->validate(['state' => 'required|array']);
        $updated = $this->iotService->updateDeviceState($smartDevice, $validated['state']);
        return response()->json(['success' => true, 'device' => $updated]);
    }

    public function listServices(CpeDevice $device)
    {
        return response()->json($device->iotServices()->get());
    }

    public function createService(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'service_type' => 'required|string',
            'service_name' => 'required|string',
            'automation_rules' => 'nullable|array'
        ]);
        $service = $this->iotService->createIotService($device, $validated);
        return response()->json(['success' => true, 'service' => $service], 201);
    }
}
