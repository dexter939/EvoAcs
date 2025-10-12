<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DiagnosticTest Model
 * 
 * Modello per test diagnostici TR-143 su dispositivi CPE
 * Model for TR-143 diagnostic tests on CPE devices
 * 
 * Tipi supportati / Supported types:
 * - ping: IPPing diagnostics
 * - traceroute: TraceRoute diagnostics
 * - download: DownloadDiagnostics (speed test)
 * - upload: UploadDiagnostics (speed test)
 */
class DiagnosticTest extends Model
{
    protected $fillable = [
        'cpe_device_id',
        'diagnostic_type',
        'status',
        'parameters',
        'results',
        'command_key',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'parameters' => 'array',
        'results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relazione con dispositivo CPE
     * Relationship with CPE device
     */
    public function cpeDevice(): BelongsTo
    {
        return $this->belongsTo(CpeDevice::class);
    }

    /**
     * Scope per filtrare test per tipo diagnostica
     * Scope to filter tests by diagnostic type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('diagnostic_type', $type);
    }

    /**
     * Scope per filtrare test per stato
     * Scope to filter tests by status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope per test completati
     * Scope for completed tests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope per test in esecuzione
     * Scope for running tests
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope per test falliti
     * Scope for failed tests
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope per test pending
     * Scope for pending tests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Marca test come avviato
     * Mark test as started
     */
    public function markAsStarted()
    {
        $this->update([
            'status' => 'running',
            'started_at' => now()
        ]);
    }

    /**
     * Marca test come completato con risultati
     * Mark test as completed with results
     */
    public function markAsCompleted(array $results)
    {
        $this->update([
            'status' => 'completed',
            'results' => $results,
            'completed_at' => now()
        ]);
    }

    /**
     * Marca test come fallito con messaggio errore
     * Mark test as failed with error message
     */
    public function markAsFailed(string $errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now()
        ]);
    }

    /**
     * Ottiene durata test in secondi
     * Get test duration in seconds
     */
    public function getDurationAttribute()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Verifica se test è completato con successo
     * Check if test completed successfully
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && !empty($this->results);
    }

    /**
     * Ottiene summary risultati diagnostica (formattato per UI)
     * Get diagnostic results summary (formatted for UI)
     */
    public function getResultsSummary(): array
    {
        if (!$this->isSuccessful()) {
            return ['status' => $this->status, 'error' => $this->error_message];
        }

        $results = $this->results;

        switch ($this->diagnostic_type) {
            case 'ping':
                return [
                    'type' => 'Ping Test',
                    'host' => $this->parameters['host'] ?? 'N/A',
                    'success_count' => $results['SuccessCount'] ?? 0,
                    'failure_count' => $results['FailureCount'] ?? 0,
                    'avg_response_time' => $results['AverageResponseTime'] ?? 0,
                    'min_response_time' => $results['MinimumResponseTime'] ?? 0,
                    'max_response_time' => $results['MaximumResponseTime'] ?? 0,
                    'packet_loss' => $this->calculatePacketLoss($results),
                ];

            case 'traceroute':
                return [
                    'type' => 'Traceroute Test',
                    'host' => $this->parameters['host'] ?? 'N/A',
                    'response_time' => $results['ResponseTime'] ?? 0,
                    'hop_count' => $results['RouteHopsNumberOfEntries'] ?? 0,
                    'route_hops' => $results['RouteHops'] ?? [],
                ];

            case 'download':
                $duration = ($results['EOMTime'] ?? 0) - ($results['BOMTime'] ?? 0);
                $bytes = $results['TotalBytesReceived'] ?? 0;
                $speedMbps = $duration > 0 ? ($bytes * 8 / $duration / 1000000) : 0;

                return [
                    'type' => 'Download Speed Test',
                    'url' => $this->parameters['url'] ?? 'N/A',
                    'total_bytes' => $bytes,
                    'duration_ms' => $duration,
                    'speed_mbps' => round($speedMbps, 2),
                ];

            case 'upload':
                $duration = ($results['EOMTime'] ?? 0) - ($results['BOMTime'] ?? 0);
                $bytes = $results['TotalBytesSent'] ?? 0;
                $speedMbps = $duration > 0 ? ($bytes * 8 / $duration / 1000000) : 0;

                return [
                    'type' => 'Upload Speed Test',
                    'url' => $this->parameters['url'] ?? 'N/A',
                    'total_bytes' => $bytes,
                    'duration_ms' => $duration,
                    'speed_mbps' => round($speedMbps, 2),
                ];

            case 'udpecho':
                return [
                    'type' => 'UDP Echo Test',
                    'host' => $this->parameters['host'] ?? 'N/A',
                    'port' => $this->parameters['port'] ?? 0,
                    'packets_sent' => $this->parameters['packets'] ?? 0,
                    'success_count' => $results['SuccessCount'] ?? 0,
                    'failure_count' => $results['FailureCount'] ?? 0,
                    'avg_response_time' => $results['AverageResponseTime'] ?? 0,
                    'min_response_time' => $results['MinimumResponseTime'] ?? 0,
                    'max_response_time' => $results['MaximumResponseTime'] ?? 0,
                    'packet_loss' => $this->calculatePacketLoss($results),
                ];

            default:
                return ['type' => 'Unknown', 'raw_results' => $results];
        }
    }

    /**
     * Calcola packet loss percentage per ping test
     * Calculate packet loss percentage for ping test
     */
    private function calculatePacketLoss(array $results): float
    {
        $sent = ($results['SuccessCount'] ?? 0) + ($results['FailureCount'] ?? 0);
        if ($sent == 0) {
            return 0;
        }

        return round(($results['FailureCount'] ?? 0) / $sent * 100, 2);
    }
}
