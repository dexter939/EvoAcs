<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Models\StorageService;
use App\Models\LogicalVolume;
use App\Models\FileServer;
use App\Jobs\ProvisionStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StorageServiceController extends Controller
{
    use ApiResponse;
    public function index(Request $request): JsonResponse
    {
        $query = StorageService::with(['cpeDevice', 'logicalVolumes', 'fileServers']);

        if ($request->has('cpe_device_id')) {
            $query->where('cpe_device_id', $request->cpe_device_id);
        }

        if ($request->has('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        if ($request->has('health_status')) {
            $query->where('health_status', $request->health_status);
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
            'total_capacity' => 'required|integer|min:0',
            'used_capacity' => 'integer|min:0',
            'raid_supported' => 'boolean',
            'supported_raid_types' => 'array',
            'ftp_supported' => 'boolean',
            'sftp_supported' => 'boolean',
            'http_supported' => 'boolean',
            'https_supported' => 'boolean',
            'samba_supported' => 'boolean',
            'nfs_supported' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service = StorageService::create($validator->validated());

        return response()->json([
            'success' => true,
            'storage_service' => $service->load('cpeDevice')
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $service = StorageService::with(['cpeDevice', 'logicalVolumes', 'fileServers'])->find($id);

        if (!$service) {
            return response()->json(['error' => 'Storage service not found'], 404);
        }

        return response()->json($service);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $service = StorageService::find($id);

        if (!$service) {
            return response()->json(['error' => 'Storage service not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'enabled' => 'boolean',
            'total_capacity' => 'integer|min:0',
            'used_capacity' => 'integer|min:0',
            'health_status' => 'string',
            'temperature' => 'integer',
            'smart_status' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service->update($validator->validated());

        return response()->json([
            'message' => 'Storage service updated successfully',
            'service' => $service->fresh(['cpeDevice'])
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $service = StorageService::find($id);

        if (!$service) {
            return response()->json(['error' => 'Storage service not found'], 404);
        }

        $service->delete();

        return response()->json(['message' => 'Storage service deleted successfully']);
    }

    public function createVolume(Request $request, string $serviceId): JsonResponse
    {
        $service = StorageService::find($serviceId);

        if (!$service) {
            return response()->json(['error' => 'Storage service not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'volume_instance' => 'required|integer',
            'enabled' => 'boolean',
            'volume_name' => 'required|string|max:255',
            'filesystem' => ['required', Rule::in(['ext4', 'ext3', 'xfs', 'btrfs', 'ntfs', 'fat32'])],
            'capacity' => 'required|integer|min:0',
            'raid_level' => ['nullable', Rule::in(['RAID0', 'RAID1', 'RAID5', 'RAID6', 'RAID10'])],
            'mount_point' => 'required|string',
            'auto_mount' => 'boolean',
            'read_only' => 'boolean',
            'quota_enabled' => 'boolean',
            'quota_size' => 'nullable|integer',
            'encrypted' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['storage_service_id'] = $serviceId;
        $data['free_space'] = $data['capacity'];
        $data['used_space'] = 0;
        $data['usage_percent'] = 0;
        $data['status'] = 'Online';

        $volume = LogicalVolume::create($data);

        return response()->json([
            'success' => true,
            'volume' => $volume
        ], 201);
    }

    public function createFileServer(Request $request, string $serviceId): JsonResponse
    {
        $service = StorageService::find($serviceId);

        if (!$service) {
            return response()->json(['error' => 'Storage service not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'server_name' => 'nullable|string|max:255',
            'server_type' => ['nullable', Rule::in(['FTP', 'SFTP', 'HTTP', 'HTTPS', 'SAMBA', 'SMB', 'NFS'])],
            'protocol' => ['nullable', Rule::in(['FTP', 'SFTP', 'HTTP', 'HTTPS', 'SAMBA', 'SMB', 'NFS'])],
            'enabled' => 'boolean',
            'port' => 'nullable|integer|between:1,65535',
            'bind_interface' => 'nullable|string',
            'max_connections' => 'nullable|integer|min:1',
            'anonymous_enabled' => 'boolean',
            'anonymous_directory' => 'nullable|string',
            'passive_mode' => 'boolean',
            'document_root' => 'nullable|string',
            'share_path' => 'nullable|string',
            'access_control' => 'nullable|array',
            'ssl_enabled' => 'boolean',
            'auth_required' => 'boolean',
            'allowed_users' => 'array',
            'ip_whitelist' => 'array',
        ], [
            'protocol.in' => 'The protocol field must be one of: FTP, SFTP, HTTP, HTTPS, SAMBA, SMB, NFS.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        
        // Preserve protocol for response
        $protocol = $data['protocol'] ?? $data['server_type'] ?? null;
        
        // Map 'protocol' to 'server_type' if provided
        if (isset($data['protocol']) && !isset($data['server_type'])) {
            $data['server_type'] = $data['protocol'];
        }
        unset($data['protocol']); // Remove protocol key as it's not a database column
        
        $data['storage_service_id'] = $serviceId;
        $data['status'] = 'Stopped';

        if (!isset($data['server_instance'])) {
            $maxInstance = FileServer::where('storage_service_id', $serviceId)
                ->max('server_instance');
            $data['server_instance'] = $maxInstance ? $maxInstance + 1 : 1;
        }

        $fileServer = FileServer::create($data);
        
        // Add protocol field to response
        $fileServerArray = $fileServer->toArray();
        $fileServerArray['protocol'] = $protocol;

        return response()->json([
            'success' => true,
            'file_server' => $fileServerArray
        ], 201);
    }

    public function getStatistics(Request $request): JsonResponse
    {
        $stats = [
            'total_services' => StorageService::count(),
            'enabled_services' => StorageService::where('enabled', true)->count(),
            'total_volumes' => LogicalVolume::count(),
            'total_file_servers' => FileServer::count(),
            'total_capacity' => StorageService::sum('total_capacity'),
            'used_capacity' => StorageService::sum('used_capacity'),
            'server_types' => FileServer::select('server_type')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('server_type')
                ->get(),
        ];

        $stats['free_capacity'] = $stats['total_capacity'] - $stats['used_capacity'];
        $stats['usage_percent'] = $stats['total_capacity'] > 0 
            ? round(($stats['used_capacity'] / $stats['total_capacity']) * 100, 2) 
            : 0;

        return response()->json($stats);
    }

    public function provisionService(Request $request, string $id): JsonResponse
    {
        $service = StorageService::with(['cpeDevice', 'logicalVolumes', 'fileServers'])->find($id);

        if (!$service) {
            return response()->json(['error' => 'Storage service not found'], 404);
        }

        $device = $service->cpeDevice;

        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        if ($device->protocol_type !== 'tr069') {
            return response()->json([
                'error' => 'Storage provisioning only works with TR-069 devices',
                'device_protocol' => $device->protocol_type
            ], 422);
        }

        ProvisionStorageService::dispatch($service);

        return response()->json([
            'message' => 'Storage provisioning task queued successfully',
            'storage_service_id' => $service->id,
            'device_id' => $device->id,
            'service_instance' => $service->service_instance,
            'status' => 'queued'
        ]);
    }
}
