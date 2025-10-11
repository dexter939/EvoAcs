<?php

namespace App\Http\Controllers;

use App\Models\CpeDevice;
use App\Models\ProvisioningTask;
use App\Models\FirmwareDeployment;
use Illuminate\Http\Request;

/**
 * DashboardController - Controller per dashboard amministrativa
 * DashboardController - Controller for administrative dashboard
 * 
 * Fornisce statistiche real-time sul sistema ACS
 * Provides real-time statistics about the ACS system
 */
class DashboardController extends Controller
{
    /**
     * Dashboard con statistiche aggregate e dispositivi recenti
     * Dashboard with aggregated statistics and recent devices
     * 
     * Restituisce:
     * - Conteggio dispositivi per stato (online, offline, provisioning, error)
     * - Statistiche task di provisioning (pending, processing, completed, failed)
     * - Statistiche deployment firmware (scheduled, downloading, installing, completed, failed)
     * - Ultimi 10 dispositivi che hanno fatto Inform
     * - Ultimi 10 task di provisioning creati
     * 
     * Returns:
     * - Device count by status (online, offline, provisioning, error)
     * - Provisioning task statistics (pending, processing, completed, failed)
     * - Firmware deployment statistics (scheduled, downloading, installing, completed, failed)
     * - Last 10 devices that sent Inform
     * - Last 10 created provisioning tasks
     * 
     * @return \Illuminate\Http\JsonResponse Statistiche dashboard / Dashboard statistics
     */
    public function index()
    {
        $stats = [
            // Statistiche dispositivi CPE
            // CPE device statistics
            'devices' => [
                'total' => CpeDevice::count(),
                'online' => CpeDevice::where('status', 'online')->count(),
                'offline' => CpeDevice::where('status', 'offline')->count(),
                'provisioning' => CpeDevice::where('status', 'provisioning')->count(),
                'error' => CpeDevice::where('status', 'error')->count(),
            ],
            
            // Statistiche task di provisioning
            // Provisioning task statistics
            'tasks' => [
                'total' => ProvisioningTask::count(),
                'pending' => ProvisioningTask::where('status', 'pending')->count(),
                'processing' => ProvisioningTask::where('status', 'processing')->count(),
                'completed' => ProvisioningTask::where('status', 'completed')->count(),
                'failed' => ProvisioningTask::where('status', 'failed')->count(),
            ],
            
            // Statistiche deployment firmware
            // Firmware deployment statistics
            'firmware' => [
                'total_deployments' => FirmwareDeployment::count(),
                'scheduled' => FirmwareDeployment::where('status', 'scheduled')->count(),
                'downloading' => FirmwareDeployment::where('status', 'downloading')->count(),
                'installing' => FirmwareDeployment::where('status', 'installing')->count(),
                'completed' => FirmwareDeployment::where('status', 'completed')->count(),
                'failed' => FirmwareDeployment::where('status', 'failed')->count(),
            ],
            
            // Ultimi 10 dispositivi attivi (ordinati per last_inform)
            // Last 10 active devices (ordered by last_inform)
            'recent_devices' => CpeDevice::orderBy('last_inform', 'desc')->limit(10)->get(),
            
            // Ultimi 10 task di provisioning (con relazione dispositivo)
            // Last 10 provisioning tasks (with device relationship)
            'recent_tasks' => ProvisioningTask::with('cpeDevice')->orderBy('created_at', 'desc')->limit(10)->get(),
        ];
        
        return response()->json($stats);
    }
}
