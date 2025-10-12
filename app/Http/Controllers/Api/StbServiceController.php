<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CpeDevice;
use App\Models\StbService;
use App\Models\StreamingSession;
use App\Services\StbProvisioningService;
use Illuminate\Http\Request;

class StbServiceController extends Controller
{
    protected $stbService;

    public function __construct(StbProvisioningService $stbService)
    {
        $this->stbService = $stbService;
    }

    public function provisionService(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'service_type' => 'required|string',
            'frontend_type' => 'required|string',
            'streaming_protocol' => 'required|string'
        ]);
        $service = $this->stbService->provisionStbService($device, $validated);
        return response()->json(['success' => true, 'service' => $service], 201);
    }

    public function startSession(Request $request, StbService $service)
    {
        $validated = $request->validate(['channel_name' => 'nullable|string']);
        $session = $this->stbService->startStreamingSession($service, $validated);
        return response()->json(['success' => true, 'session' => $session], 201);
    }

    public function updateQos(Request $request, StreamingSession $session)
    {
        $validated = $request->validate(['bitrate' => 'nullable|integer']);
        $updated = $this->stbService->updateSessionQos($session, $validated);
        return response()->json(['success' => true, 'session' => $updated]);
    }
}
