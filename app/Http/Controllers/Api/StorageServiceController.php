<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Models\CpeDevice;
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

    public function store(Request $request, ?CpeDevice $device = null): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cpe_device_id' => $device ? 'nullable' : 'required|exists:cpe_devices,id',
            'service_instance' => 'nullable|integer',
            'service_name' => 'nullable|string|max:255',
            'storage_type' => 'nullable|string|max:50',
            'enabled' => 'boolean',
            'total_capacity' => 'nullable|integer|min:0',
            'total_capacity_mb' => 'nullable|integer|min:0',
            'used_capacity' => 'nullable|integer|min:0',
            'used_capacity_mb' => 'nullable|integer|min:0',
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

        $data = $validator->validated();
        
        // Use device ID from route parameter if available
        if ($device) {
            $data['cpe_device_id'] = $device->id;
        }
        
        // Convert total_capacity_mb to total_capacity (bytes) if provided
        if (isset($data['total_capacity_mb'])) {
            $data['total_capacity'] = $data['total_capacity_mb'] * 1024 * 1024;
            unset($data['total_capacity_mb']);
        }
        
        // Convert used_capacity_mb to used_capacity (bytes) if provided
        if (isset($data['used_capacity_mb'])) {
            $data['used_capacity'] = $data['used_capacity_mb'] * 1024 * 1024;
            unset($data['used_capacity_mb']);
        }

        // Set defaults if not provided
        if (!isset($data['used_capacity']) && !isset($data['used_capacity_mb'])) {
            $data['used_capacity'] = 0;
        }

        $service = StorageService::create($data);

        return response()->json([
            'success' => true,
            'data' => $service->load('cpeDevice')
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $service = StorageService::with(['cpeDevice', 'logicalVolumes', 'fileServers'])->find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Storage service not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $service
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $service = StorageService::find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Storage service not found'
            ], 404);
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
            'success' => true,
            'data' => $service->fresh(['cpeDevice'])
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $service = StorageService::find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Storage service not found'
            ], 404);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Storage service deleted successfully'
        ]);
    }

    public function createVolume(Request $request, string $serviceId): JsonResponse
    {
        $service = StorageService::find($serviceId);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Storage service not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'volume_instance' => 'nullable|integer',
            'enabled' => 'boolean',
            'volume_name' => 'required|string|max:255',
            'filesystem' => ['nullable', Rule::in(['ext4', 'ext3', 'xfs', 'btrfs', 'ntfs', 'fat32'])],
            'filesystem_type' => ['nullable', Rule::in(['ext4', 'ext3', 'xfs', 'btrfs', 'ntfs', 'fat32'])],
            'capacity' => 'nullable|integer|min:0',
            'capacity_mb' => 'nullable|integer|min:0',
            'raid_level' => ['nullable', Rule::in(['RAID0', 'RAID1', 'RAID5', 'RAID6', 'RAID10'])],
            'mount_point' => 'nullable|string',
            'auto_mount' => 'boolean',
            'read_only' => 'boolean',
            'quota_enabled' => 'boolean',
            'quota_size' => 'nullable|integer',
            'encrypted' => 'boolean',
            'encryption_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        
        // Map filesystem_type to filesystem
        if (isset($data['filesystem_type']) && !isset($data['filesystem'])) {
            $data['filesystem'] = $data['filesystem_type'];
        }
        unset($data['filesystem_type']);
        
        // Map capacity_mb to capacity (bytes)
        if (isset($data['capacity_mb'])) {
            $data['capacity'] = $data['capacity_mb'] * 1024 * 1024;
            unset($data['capacity_mb']);
        }
        
        // Map encryption_enabled to encrypted
        if (isset($data['encryption_enabled'])) {
            $data['encrypted'] = $data['encryption_enabled'];
            unset($data['encryption_enabled']);
        }
        
        $data['storage_service_id'] = $serviceId;
        $data['free_space'] = $data['capacity'] ?? 0;
        $data['used_space'] = 0;
        $data['usage_percent'] = 0;
        $data['status'] = 'Online';

        $volume = LogicalVolume::create($data);

        return response()->json([
            'success' => true,
            'data' => $volume
        ], 201);
    }

    public function createFileServer(Request $request, string $serviceId): JsonResponse
    {
        $service = StorageService::find($serviceId);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Storage service not found'
            ], 404);
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
            'data' => $fileServerArray
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

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }

    public function provisionService(Request $request, string $id): JsonResponse
    {
        $service = StorageService::with(['cpeDevice', 'logicalVolumes', 'fileServers'])->find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Storage service not found'
            ], 404);
        }

        $device = $service->cpeDevice;

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }

        if ($device->protocol_type !== 'tr069') {
            return response()->json([
                'success' => false,
                'message' => 'Storage provisioning only works with TR-069 devices',
                'device_protocol' => $device->protocol_type
            ], 422);
        }

        ProvisionStorageService::dispatch($service);

        return response()->json([
            'success' => true,
            'data' => [
                'storage_service_id' => $service->id,
                'device_id' => $device->id,
                'service_instance' => $service->service_instance,
                'status' => 'queued'
            ]
        ]);
    }
}
