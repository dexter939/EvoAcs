<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model per risultati per-connessione test diagnostici TR-143
 * Model for TR-143 per-connection diagnostic results
 * 
 * Standard: TR-143 (Download/Upload Diagnostics - Per-Connection Results)
 * 
 * Traccia risultati dettagliati per ogni connessione TCP quando NumberOfConnections > 1
 * Tracks detailed results for each TCP connection when NumberOfConnections > 1
 */
class DiagnosticConnectionResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'diagnostic_test_id',
        'connection_number',
        'tcp_open_request_time',
        'tcp_open_response_time',
        'rom_time',
        'bom_time',
        'eom_time',
        'bytes_transferred',
        'connection_status',
        'error_message'
    ];

    protected $casts = [
        'tcp_open_request_time' => 'decimal:6',
        'tcp_open_response_time' => 'decimal:6',
        'rom_time' => 'decimal:6',
        'bom_time' => 'decimal:6',
        'eom_time' => 'decimal:6',
        'bytes_transferred' => 'integer',
    ];

    /**
     * Relazione: risultato connessione appartiene a test diagnostico
     * Relationship: connection result belongs to diagnostic test
     */
    public function diagnosticTest()
    {
        return $this->belongsTo(DiagnosticTest::class);
    }

    /**
     * Calcola durata TCP handshake (microsecondi)
     * Calculate TCP handshake duration (microseconds)
     */
    public function getTcpHandshakeDuration(): ?float
    {
        if ($this->tcp_open_request_time === null || $this->tcp_open_response_time === null) {
            return null;
        }
        
        return (float)$this->tcp_open_response_time - (float)$this->tcp_open_request_time;
    }

    /**
     * Calcola durata trasferimento dati (microsecondi)
     * Calculate data transfer duration (microseconds)
     */
    public function getTransferDuration(): ?float
    {
        if ($this->bom_time === null || $this->eom_time === null) {
            return null;
        }
        
        return (float)$this->eom_time - (float)$this->bom_time;
    }

    /**
     * Calcola velocità trasferimento (Mbps)
     * Calculate transfer speed (Mbps)
     */
    public function getSpeedMbps(): float
    {
        $duration = $this->getTransferDuration();
        
        if ($duration === null || $duration <= 0) {
            return 0;
        }
        
        // bytes_transferred * 8 bits/byte / duration_microseconds * 1000000 microseconds/second / 1000000 bits/megabit
        return round(($this->bytes_transferred * 8 / $duration), 2);
    }

    /**
     * Verifica se connessione è completata con successo
     * Check if connection completed successfully
     */
    public function isSuccessful(): bool
    {
        return $this->connection_status === 'completed' && $this->error_message === null;
    }
}
