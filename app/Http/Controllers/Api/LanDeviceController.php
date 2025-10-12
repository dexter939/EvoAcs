<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CpeDevice;
use App\Models\LanDevice;
use App\Services\UpnpDiscoveryService;
use Illuminate\Http\Request;

class LanDeviceController extends Controller
{
    protected $upnpService;

    public function __construct(UpnpDiscoveryService $upnpService)
    {
        $this->upnpService = $upnpService;
    }

    public function index(Request $request, CpeDevice $device)
    {
        $query = $device->lanDevices();
        if ($request->has('status')) $query->where('status', $request->input('status'));
        return response()->json($query->orderBy('last_seen', 'desc')->paginate(50));
    }

    public function processSsdpAnnouncement(Request $request, CpeDevice $device)
    {
        $validated = $request->validate(['usn' => 'required|string', 'location' => 'nullable|url']);
        try {
            $lanDevice = $this->upnpService->processSsdpAnnouncement($device, $validated);
            return response()->json(['success' => true, 'lan_device' => $lanDevice], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function invokeSoapAction(Request $request, LanDevice $lanDevice)
    {
        $validated = $request->validate(['service_type' => 'required|string', 'action' => 'required|string', 'arguments' => 'nullable|array']);
        try {
            $result = $this->upnpService->invokeSoapAction($lanDevice, $validated['service_type'], $validated['action'], $validated['arguments'] ?? []);
            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
