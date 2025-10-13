<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Models\CpeDevice;
use App\Models\DiagnosticTest;
use App\Models\ProvisioningTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DiagnosticsController - Controller API per test diagnostici TR-143
 * DiagnosticsController - API Controller for TR-143 diagnostic tests
 * 
 * Gestisce richieste diagnostiche: ping, traceroute, download/upload speed test
 * Handles diagnostic requests: ping, traceroute, download/upload speed test
 */
class DiagnosticsController extends Controller
    use ApiResponse;
{
    /**
     * Avvia test Ping su dispositivo CPE
     * Start Ping test on CPE device
     * 
     * Standard TR-143: IPPing diagnostics
     * 
     * @param Request $request Parametri: host, packets, timeout, size
     * @param CpeDevice $device Dispositivo target / Target device
     * @return \Illuminate\Http\JsonResponse Test diagnostico creato / Diagnostic test created
     */
    public function ping(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'packets' => 'integer|min:1|max:100',
            'timeout' => 'integer|min:100|max:10000',
            'size' => 'integer|min:32|max:1500'
        ]);

        try {
            // Transazione atomica per consistenza dati
            // Atomic transaction for data consistency
            [$diagnostic, $task] = DB::transaction(function () use ($device, $validated) {
                // Crea record test diagnostico
                // Create diagnostic test record
                $diagnostic = DiagnosticTest::create([
                    'cpe_device_id' => $device->id,
                    'diagnostic_type' => 'ping',
                    'status' => 'pending',
                    'parameters' => [
                        'host' => $validated['host'],
                        'packets' => $validated['packets'] ?? 4,
                        'timeout' => $validated['timeout'] ?? 1000,
                        'size' => $validated['size'] ?? 64
                    ],
                    'command_key' => 'IPPing_' . time()
                ]);

                // Crea task provisioning per inviare comando TR-069
                // Create provisioning task to send TR-069 command
                $task = ProvisioningTask::create([
                    'cpe_device_id' => $device->id,
                    'task_type' => 'diagnostic_ping',
                    'status' => 'pending',
                    'task_data' => [
                        'diagnostic_id' => $diagnostic->id,
                        'host' => $validated['host'],
                        'packets' => $validated['packets'] ?? 4,
                        'timeout' => $validated['timeout'] ?? 1000,
                        'size' => $validated['size'] ?? 64
                    ]
                ]);

                return [$diagnostic, $task];
            });

            // Dispatcha job asincrono dopo commit transazione
            // Dispatch async job after transaction commit
            \App\Jobs\ProcessProvisioningTask::dispatch($task);

            return response()->json([
                'success' => true,
                'message' => 'Ping diagnostic test started',
                'diagnostic' => $diagnostic,
                'task' => $task
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start diagnostic test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Avvia test Traceroute su dispositivo CPE
     * Start Traceroute test on CPE device
     * 
     * Standard TR-143: TraceRoute diagnostics
     * 
     * @param Request $request Parametri: host, tries, timeout, max_hops
     * @param CpeDevice $device Dispositivo target / Target device
     * @return \Illuminate\Http\JsonResponse Test diagnostico creato / Diagnostic test created
     */
    public function traceroute(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'tries' => 'integer|min:1|max:10',
            'timeout' => 'integer|min:100|max:30000',
            'max_hops' => 'integer|min:1|max:64'
        ]);

        try {
            [$diagnostic, $task] = DB::transaction(function () use ($device, $validated) {
                $diagnostic = DiagnosticTest::create([
                    'cpe_device_id' => $device->id,
                    'diagnostic_type' => 'traceroute',
                    'status' => 'pending',
                    'parameters' => [
                        'host' => $validated['host'],
                        'tries' => $validated['tries'] ?? 3,
                        'timeout' => $validated['timeout'] ?? 5000,
                        'max_hops' => $validated['max_hops'] ?? 30
                    ],
                    'command_key' => 'TraceRoute_' . time()
                ]);

                $task = ProvisioningTask::create([
                    'cpe_device_id' => $device->id,
                    'task_type' => 'diagnostic_traceroute',
                    'status' => 'pending',
                    'task_data' => [
                        'diagnostic_id' => $diagnostic->id,
                        'host' => $validated['host'],
                        'tries' => $validated['tries'] ?? 3,
                        'timeout' => $validated['timeout'] ?? 5000,
                        'max_hops' => $validated['max_hops'] ?? 30
                    ]
                ]);

                return [$diagnostic, $task];
            });

            \App\Jobs\ProcessProvisioningTask::dispatch($task);

            return response()->json([
                'success' => true,
                'message' => 'Traceroute diagnostic test started',
                'diagnostic' => $diagnostic,
                'task' => $task
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start diagnostic test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Avvia test Download Speed su dispositivo CPE
     * Start Download Speed test on CPE device
     * 
     * Standard TR-143: DownloadDiagnostics with multi-threaded support
     * 
     * @param Request $request Parametri: url, file_size, connections (NumberOfConnections for multi-threading)
     * @param CpeDevice $device Dispositivo target / Target device
     * @return \Illuminate\Http\JsonResponse Test diagnostico creato / Diagnostic test created
     */
    public function download(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'file_size' => 'integer|min:0',
            'connections' => 'integer|min:1|max:8' // TR-143 NumberOfConnections (1-8 threads)
        ]);

        try {
            [$diagnostic, $task] = DB::transaction(function () use ($device, $validated) {
                $diagnostic = DiagnosticTest::create([
                    'cpe_device_id' => $device->id,
                    'diagnostic_type' => 'download',
                    'status' => 'pending',
                    'parameters' => [
                        'url' => $validated['url'],
                        'file_size' => $validated['file_size'] ?? 0,
                        'connections' => $validated['connections'] ?? 1
                    ],
                    'command_key' => 'DownloadDiag_' . time()
                ]);

                $task = ProvisioningTask::create([
                    'cpe_device_id' => $device->id,
                    'task_type' => 'diagnostic_download',
                    'status' => 'pending',
                    'task_data' => [
                        'diagnostic_id' => $diagnostic->id,
                        'url' => $validated['url'],
                        'file_size' => $validated['file_size'] ?? 0,
                        'connections' => $validated['connections'] ?? 1
                    ]
                ]);

                return [$diagnostic, $task];
            });

            \App\Jobs\ProcessProvisioningTask::dispatch($task);

            return response()->json([
                'success' => true,
                'message' => 'Download speed test started',
                'diagnostic' => $diagnostic,
                'task' => $task
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start diagnostic test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Avvia test Upload Speed su dispositivo CPE
     * Start Upload Speed test on CPE device
     * 
     * Standard TR-143: UploadDiagnostics with multi-threaded support
     * 
     * @param Request $request Parametri: url, file_size, connections (NumberOfConnections for multi-threading)
     * @param CpeDevice $device Dispositivo target / Target device
     * @return \Illuminate\Http\JsonResponse Test diagnostico creato / Diagnostic test created
     */
    public function upload(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'file_size' => 'integer|min:0|max:104857600', // Max 100MB
            'connections' => 'integer|min:1|max:8' // TR-143 NumberOfConnections (1-8 threads)
        ]);

        try {
            [$diagnostic, $task] = DB::transaction(function () use ($device, $validated) {
                $diagnostic = DiagnosticTest::create([
                    'cpe_device_id' => $device->id,
                    'diagnostic_type' => 'upload',
                    'status' => 'pending',
                    'parameters' => [
                        'url' => $validated['url'],
                        'file_size' => $validated['file_size'] ?? 1048576, // 1MB default
                        'connections' => $validated['connections'] ?? 1
                    ],
                    'command_key' => 'UploadDiag_' . time()
                ]);

                $task = ProvisioningTask::create([
                    'cpe_device_id' => $device->id,
                    'task_type' => 'diagnostic_upload',
                    'status' => 'pending',
                    'task_data' => [
                        'diagnostic_id' => $diagnostic->id,
                        'url' => $validated['url'],
                        'file_size' => $validated['file_size'] ?? 1048576,
                        'connections' => $validated['connections'] ?? 1
                    ]
                ]);

                return [$diagnostic, $task];
            });

            \App\Jobs\ProcessProvisioningTask::dispatch($task);

            return response()->json([
                'success' => true,
                'message' => 'Upload speed test started',
                'diagnostic' => $diagnostic,
                'task' => $task
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start diagnostic test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Avvia test UDPEcho su dispositivo CPE
     * Start UDPEcho test on CPE device
     * 
     * Standard TR-143: UDPEcho diagnostics
     * 
     * @param Request $request Parametri: host, port, packets, timeout, size, interval
     * @param CpeDevice $device Dispositivo target / Target device
     * @return \Illuminate\Http\JsonResponse Test diagnostico creato / Diagnostic test created
     */
    public function udpEcho(Request $request, CpeDevice $device)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'packets' => 'integer|min:1|max:100',
            'timeout' => 'integer|min:100|max:10000',
            'size' => 'integer|min:32|max:1500',
            'interval' => 'integer|min:10|max:10000'
        ]);

        try {
            [$diagnostic, $task] = DB::transaction(function () use ($device, $validated) {
                $diagnostic = DiagnosticTest::create([
                    'cpe_device_id' => $device->id,
                    'diagnostic_type' => 'udpecho',
                    'status' => 'pending',
                    'parameters' => [
                        'host' => $validated['host'],
                        'port' => $validated['port'],
                        'packets' => $validated['packets'] ?? 10,
                        'timeout' => $validated['timeout'] ?? 1000,
                        'size' => $validated['size'] ?? 64,
                        'interval' => $validated['interval'] ?? 100
                    ],
                    'command_key' => 'UDPEcho_' . time()
                ]);

                $task = ProvisioningTask::create([
                    'cpe_device_id' => $device->id,
                    'task_type' => 'diagnostic_udpecho',
                    'status' => 'pending',
                    'task_data' => [
                        'diagnostic_id' => $diagnostic->id,
                        'host' => $validated['host'],
                        'port' => $validated['port'],
                        'packets' => $validated['packets'] ?? 10,
                        'timeout' => $validated['timeout'] ?? 1000,
                        'size' => $validated['size'] ?? 64,
                        'interval' => $validated['interval'] ?? 100
                    ]
                ]);

                return [$diagnostic, $task];
            });

            \App\Jobs\ProcessProvisioningTask::dispatch($task);

            return response()->json([
                'success' => true,
                'message' => 'UDPEcho diagnostic test started',
                'diagnostic' => $diagnostic,
                'task' => $task
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start diagnostic test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ottiene risultati test diagnostico
     * Get diagnostic test results
     * 
     * @param DiagnosticTest $diagnostic Test diagnostico / Diagnostic test
     * @return \Illuminate\Http\JsonResponse Risultati formattati / Formatted results
     */
    public function getResults(DiagnosticTest $diagnostic)
    {
        return response()->json([
            'diagnostic' => $diagnostic->load('cpeDevice'),
            'summary' => $diagnostic->getResultsSummary(),
            'duration_seconds' => $diagnostic->duration
        ]);
    }

    /**
     * Lista test diagnostici di un dispositivo
     * List diagnostic tests for a device
     * 
     * @param CpeDevice $device Dispositivo / Device
     * @return \Illuminate\Http\JsonResponse Lista test / Test list
     */
    public function listDeviceDiagnostics(CpeDevice $device)
    {
        $diagnostics = $device->diagnosticTests()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($diagnostics);
    }

    /**
     * Lista tutti i test diagnostici con filtri
     * List all diagnostic tests with filters
     * 
     * @param Request $request Filtri: type, status, device_id
     * @return \Illuminate\Http\JsonResponse Lista paginata / Paginated list
     */
    public function index(Request $request)
    {
        $query = DiagnosticTest::with('cpeDevice');

        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        if ($request->has('status')) {
            $query->withStatus($request->status);
        }

        if ($request->has('device_id')) {
            $query->where('cpe_device_id', $request->device_id);
        }

        $diagnostics = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json($diagnostics);
    }
}
