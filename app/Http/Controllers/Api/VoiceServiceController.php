<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Models\VoiceService;
use App\Models\SipProfile;
use App\Models\VoipLine;
use App\Models\CpeDevice;
use App\Jobs\ProvisionVoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VoiceServiceController extends Controller
    use ApiResponse;
{
    public function index(Request $request): JsonResponse
    {
        $query = VoiceService::with(['cpeDevice', 'sipProfiles.voipLines']);

        if ($request->has('cpe_device_id')) {
            $query->where('cpe_device_id', $request->cpe_device_id);
        }

        if ($request->has('protocol')) {
            $query->where('protocol', $request->protocol);
        }

        if ($request->has('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        $services = $query->paginate($request->input('per_page', 50));

        return response()->json($services);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cpe_device_id' => 'required|exists:cpe_devices,id',
            'service_instance' => 'required|integer',
            'enabled' => 'boolean',
            'protocol' => ['required', Rule::in(['SIP', 'MGCP', 'H.323'])],
            'max_profiles' => 'integer|min:1',
            'max_lines' => 'integer|min:1',
            'max_sessions' => 'integer|min:1',
            'capabilities' => 'array',
            'codecs' => 'array',
            'rtp_dscp' => 'integer|between:0,63',
            'rtp_port_min' => 'integer|between:1024,65535',
            'rtp_port_max' => 'integer|between:1024,65535',
            'stun_enabled' => 'boolean',
            'stun_server' => 'nullable|string',
            'stun_port' => 'nullable|integer|between:1,65535',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service = VoiceService::create($validator->validated());

        return response()->json([
            'message' => 'Voice service created successfully',
            'service' => $service->load('cpeDevice')
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $service = VoiceService::with(['cpeDevice', 'sipProfiles.voipLines'])->find($id);

        if (!$service) {
            return response()->json(['error' => 'Voice service not found'], 404);
        }

        return response()->json($service);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $service = VoiceService::find($id);

        if (!$service) {
            return response()->json(['error' => 'Voice service not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'enabled' => 'boolean',
            'protocol' => [Rule::in(['SIP', 'MGCP', 'H.323'])],
            'max_profiles' => 'integer|min:1',
            'max_lines' => 'integer|min:1',
            'max_sessions' => 'integer|min:1',
            'capabilities' => 'array',
            'codecs' => 'array',
            'rtp_dscp' => 'integer|between:0,63',
            'rtp_port_min' => 'integer|between:1024,65535',
            'rtp_port_max' => 'integer|between:1024,65535',
            'stun_enabled' => 'boolean',
            'stun_server' => 'nullable|string',
            'stun_port' => 'nullable|integer|between:1,65535',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service->update($validator->validated());

        return response()->json([
            'message' => 'Voice service updated successfully',
            'service' => $service->fresh(['cpeDevice'])
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $service = VoiceService::find($id);

        if (!$service) {
            return response()->json(['error' => 'Voice service not found'], 404);
        }

        $service->delete();

        return response()->json(['message' => 'Voice service deleted successfully']);
    }

    public function createSipProfile(Request $request, string $serviceId): JsonResponse
    {
        $service = VoiceService::find($serviceId);

        if (!$service) {
            return response()->json(['error' => 'Voice service not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'profile_instance' => 'required|integer',
            'enabled' => 'boolean',
            'profile_name' => 'required|string|max:255',
            'proxy_server' => 'required|string',
            'proxy_port' => 'required|integer|between:1,65535',
            'registrar_server' => 'required|string',
            'registrar_port' => 'required|integer|between:1,65535',
            'auth_username' => 'required|string',
            'auth_password' => 'required|string',
            'domain' => 'required|string',
            'transport_protocol' => ['required', Rule::in(['UDP', 'TCP', 'TLS'])],
            'register_expires' => 'integer|min:60',
            'codec_list' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['voice_service_id'] = $serviceId;

        $profile = SipProfile::create($data);

        return response()->json([
            'message' => 'SIP profile created successfully',
            'profile' => $profile
        ], 201);
    }

    public function createVoipLine(Request $request, string $profileId): JsonResponse
    {
        $profile = SipProfile::find($profileId);

        if (!$profile) {
            return response()->json(['error' => 'SIP profile not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'line_instance' => 'required|integer',
            'enabled' => 'boolean',
            'directory_number' => 'required|string',
            'display_name' => 'string',
            'sip_uri' => 'required|string',
            'auth_username' => 'required|string',
            'auth_password' => 'required|string',
            'call_waiting_enabled' => 'boolean',
            'call_forward_enabled' => 'boolean',
            'call_forward_number' => 'nullable|string',
            'dnd_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['sip_profile_id'] = $profileId;
        $data['status'] = 'Idle';

        $line = VoipLine::create($data);

        return response()->json([
            'message' => 'VoIP line created successfully',
            'line' => $line
        ], 201);
    }

    public function getStatistics(Request $request): JsonResponse
    {
        $stats = [
            'total_services' => VoiceService::count(),
            'enabled_services' => VoiceService::where('enabled', true)->count(),
            'total_profiles' => SipProfile::count(),
            'total_lines' => VoipLine::count(),
            'active_lines' => VoipLine::where('status', 'Registered')->count(),
            'protocols' => VoiceService::select('protocol')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('protocol')
                ->get(),
        ];

        return response()->json($stats);
    }

    public function provisionService(Request $request, string $id): JsonResponse
    {
        $service = VoiceService::with(['cpeDevice', 'sipProfiles.voipLines'])->find($id);

        if (!$service) {
            return response()->json(['error' => 'Voice service not found'], 404);
        }

        $device = $service->cpeDevice;

        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        if ($device->protocol_type !== 'tr069') {
            return response()->json([
                'error' => 'VoIP provisioning only works with TR-069 devices',
                'device_protocol' => $device->protocol_type
            ], 422);
        }

        ProvisionVoiceService::dispatch($service);

        return response()->json([
            'message' => 'VoIP provisioning task queued successfully',
            'voice_service_id' => $service->id,
            'device_id' => $device->id,
            'service_instance' => $service->service_instance,
            'status' => 'queued'
        ]);
    }
}
