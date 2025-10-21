<?php

namespace App\Http\Controllers;

use App\Models\SystemVersion;
use App\Services\GitHub\GitHubReleaseService;
use App\Services\GitHub\UpdateStagingService;
use App\Services\GitHub\UpdateApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitHubUpdateDashboardController extends Controller
{
    public function __construct(
        private GitHubReleaseService $githubService,
        private UpdateStagingService $stagingService,
        private UpdateApplicationService $applicationService
    ) {}

    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        
        $query = SystemVersion::whereNotNull('github_release_url')
            ->orderBy('created_at', 'desc');

        if ($status === 'pending') {
            $query->pendingApproval();
        } elseif ($status === 'approved') {
            $query->approved();
        } elseif ($status !== 'all') {
            $query->where('approval_status', $status);
        }

        $updates = $query->with(['approvedByUser'])->paginate(20);

        $stats = $this->getStats();

        return view('acs.github-updates.index', compact('updates', 'stats', 'status'));
    }

    public function show(int $id)
    {
        $update = SystemVersion::whereNotNull('github_release_url')
            ->with(['approvedByUser'])
            ->findOrFail($id);

        $validationResults = null;
        if ($update->download_path && file_exists(storage_path('app/' . $update->download_path))) {
            $validationResults = $this->stagingService->validateStagedUpdate($update->version);
        }

        $deploymentHistory = SystemVersion::where('version', $update->version)
            ->orWhere('github_release_tag', $update->github_release_tag)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('acs.github-updates.show', compact('update', 'validationResults', 'deploymentHistory'));
    }

    public function checkForUpdates(Request $request)
    {
        try {
            $autoStage = $request->input('auto_stage', false);
            
            $latestRelease = $this->githubService->getLatestRelease();
            
            if (!$latestRelease) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nessun nuovo aggiornamento disponibile su GitHub.'
                ]);
            }

            $existingUpdate = SystemVersion::where('github_release_tag', $latestRelease['tag_name'])->first();
            
            if ($existingUpdate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Questo aggiornamento è già stato registrato.',
                    'data' => $existingUpdate
                ]);
            }

            if ($autoStage) {
                $result = $this->stagingService->stageUpdate($latestRelease);
                
                if (!$result['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Errore durante lo staging: ' . $result['message']
                    ], 500);
                }

                $update = $result['version'];
            } else {
                $update = SystemVersion::create([
                    'version' => $latestRelease['tag_name'],
                    'github_release_url' => $latestRelease['html_url'],
                    'github_release_tag' => $latestRelease['tag_name'],
                    'changelog' => $latestRelease['body'] ?? '',
                    'release_notes' => $latestRelease['body'] ?? '',
                    'approval_status' => 'pending',
                    'environment' => config('app.env'),
                    'deployment_status' => 'pending',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Nuovo aggiornamento rilevato e registrato con successo.',
                'data' => $update->fresh(['approvedByUser'])
            ]);

        } catch (\Exception $e) {
            Log::error('GitHub update check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la verifica degli aggiornamenti: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approve(Request $request, int $id)
    {
        try {
            $update = SystemVersion::whereNotNull('github_release_url')->findOrFail($id);

            if ($update->approval_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Questo aggiornamento non è in attesa di approvazione.'
                ], 400);
            }

            $update->approve();

            Log::info('GitHub update approved', [
                'update_id' => $update->id,
                'version' => $update->version,
                'approved_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aggiornamento approvato con successo.',
                'data' => $update->fresh(['approvedByUser'])
            ]);

        } catch (\Exception $e) {
            Log::error('GitHub update approval failed', [
                'update_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'approvazione: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $update = SystemVersion::whereNotNull('github_release_url')->findOrFail($id);

            if ($update->approval_status === 'rejected') {
                return response()->json([
                    'success' => false,
                    'message' => 'Questo aggiornamento è già stato rigettato.'
                ], 400);
            }

            $update->reject();

            if ($update->download_path) {
                $this->stagingService->cleanupStagedUpdate($update->version);
            }

            Log::warning('GitHub update rejected', [
                'update_id' => $update->id,
                'version' => $update->version,
                'rejected_by' => auth()->id(),
                'reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aggiornamento rigettato. I file staging sono stati rimossi.',
                'data' => $update->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('GitHub update rejection failed', [
                'update_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante il rigetto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function schedule(Request $request, int $id)
    {
        $request->validate([
            'scheduled_at' => 'required|date|after:now'
        ]);

        try {
            $update = SystemVersion::whereNotNull('github_release_url')->findOrFail($id);

            if ($update->approval_status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo gli aggiornamenti approvati possono essere pianificati.'
                ], 400);
            }

            $update->scheduleDeployment(new \DateTime($request->scheduled_at));

            Log::info('GitHub update scheduled', [
                'update_id' => $update->id,
                'version' => $update->version,
                'scheduled_at' => $request->scheduled_at,
                'scheduled_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Deployment pianificato con successo per ' . $update->scheduled_at->format('d/m/Y H:i'),
                'data' => $update->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('GitHub update scheduling failed', [
                'update_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la pianificazione: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apply(Request $request, int $id)
    {
        try {
            $update = SystemVersion::whereNotNull('github_release_url')->findOrFail($id);

            if ($update->approval_status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo gli aggiornamenti approvati possono essere applicati.'
                ], 400);
            }

            if (!$update->download_path || !file_exists(storage_path('app/' . $update->download_path))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Package non trovato. Eseguire prima il download/staging.'
                ], 400);
            }

            $result = $this->applicationService->applyUpdate($update->version);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deployment fallito: ' . $result['message'],
                    'details' => $result
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Aggiornamento applicato con successo! Sistema aggiornato alla versione ' . $update->version,
                'data' => [
                    'version' => $result['version'],
                    'backup_path' => $result['backup_path'] ?? null,
                    'migrations_run' => $result['migrations_count'] ?? 0
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('GitHub update application failed', [
                'update_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore critico durante il deployment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function validate(int $id)
    {
        try {
            $update = SystemVersion::whereNotNull('github_release_url')->findOrFail($id);

            if (!$update->download_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nessun package in staging per questa versione.'
                ], 400);
            }

            $validationResults = $this->stagingService->validateStagedUpdate($update->version);

            return response()->json([
                'success' => $validationResults['valid'],
                'message' => $validationResults['valid'] 
                    ? 'Validazione completata con successo. Package pronto per il deployment.'
                    : 'Validazione fallita. Controllare i dettagli.',
                'data' => $validationResults
            ]);

        } catch (\Exception $e) {
            Log::error('GitHub update validation failed', [
                'update_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la validazione: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getStats(): array
    {
        return [
            'total_updates' => SystemVersion::whereNotNull('github_release_url')->count(),
            'pending_approval' => SystemVersion::pendingApproval()->count(),
            'approved' => SystemVersion::approved()->count(),
            'rejected' => SystemVersion::whereNotNull('github_release_url')
                ->where('approval_status', 'rejected')
                ->count(),
            'deployed' => SystemVersion::whereNotNull('github_release_url')
                ->where('is_current', true)
                ->count(),
            'scheduled' => SystemVersion::whereNotNull('github_release_url')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '>', now())
                ->count(),
        ];
    }
}
