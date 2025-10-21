<?php

namespace App\Http\Controllers;

use App\Services\SystemUpdateService;
use App\Models\SystemVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SystemUpdateController extends Controller
{
    private SystemUpdateService $updateService;

    public function __construct(SystemUpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    public function dashboard()
    {
        $status = $this->updateService->getSystemStatus();
        $history = SystemVersion::latest('deployed_at')
            ->limit(20)
            ->get();

        $stats = [
            'total_deployments' => SystemVersion::count(),
            'successful_deployments' => SystemVersion::successful()->count(),
            'failed_deployments' => SystemVersion::failed()->count(),
            'last_24h_deployments' => SystemVersion::where('deployed_at', '>=', now()->subDay())->count(),
        ];

        return view('acs.system-updates', compact('status', 'history', 'stats'));
    }

    public function status(Request $request)
    {
        $environment = $request->query('environment', config('app.env'));
        return response()->json($this->updateService->getSystemStatus($environment));
    }

    public function history(Request $request)
    {
        $limit = $request->query('limit', 10);
        $history = $this->updateService->getUpdateHistory($limit);

        return response()->json([
            'status' => 'success',
            'history' => $history,
        ]);
    }

    public function runUpdate(Request $request)
    {
        $request->validate([
            'force' => 'sometimes|boolean',
            'environment' => 'sometimes|in:development,staging,production',
        ]);

        $result = $this->updateService->performAutoUpdate(
            $request->input('environment', config('app.env'))
        );

        return response()->json($result);
    }

    public function healthCheck(Request $request)
    {
        $environment = $request->query('environment', config('app.env'));
        $current = SystemVersion::getCurrentVersion($environment);

        if (!$current) {
            return response()->json([
                'status' => 'warning',
                'message' => 'No deployment records found',
                'healthy' => false,
            ], 200);
        }

        $healthChecks = $current->health_check_results ?? [];

        return response()->json([
            'status' => $current->is_healthy ? 'healthy' : 'degraded',
            'version' => $current->version,
            'deployed_at' => $current->deployed_at?->toIso8601String(),
            'health_checks' => $healthChecks,
            'healthy' => $current->is_healthy,
        ]);
    }

    public function versionInfo(Request $request)
    {
        $environment = $request->query('environment', config('app.env'));
        $current = SystemVersion::getCurrentVersion($environment);

        return response()->json([
            'current_version' => $current?->version ?? 'unknown',
            'deployment_status' => $current?->deployment_status ?? 'unknown',
            'deployed_at' => $current?->deployed_at?->toIso8601String(),
            'environment' => config('app.env'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ]);
    }
}
