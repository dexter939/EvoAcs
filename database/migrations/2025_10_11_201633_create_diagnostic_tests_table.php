<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabella per salvare test diagnostici TR-143 (ping, traceroute, speed test)
     * Table to store TR-143 diagnostic tests (ping, traceroute, speed test)
     */
    public function up(): void
    {
        Schema::create('diagnostic_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained()->cascadeOnDelete();
            
            // Tipo diagnostica TR-069/TR-143: IPPing, TraceRoute, DownloadDiagnostics, UploadDiagnostics
            // TR-069/TR-143 diagnostic type: IPPing, TraceRoute, DownloadDiagnostics, UploadDiagnostics
            $table->enum('diagnostic_type', ['IPPing', 'TraceRoute', 'DownloadDiagnostics', 'UploadDiagnostics', 'UDPEcho'])->index();
            
            // Stato test: pending, running, completed, failed
            // Test status: pending, running, completed, failed
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending')->index();
            
            // Parametri richiesta diagnostica (JSON)
            // Diagnostic request parameters (JSON)
            // Es. {"host": "8.8.8.8", "packets": 4, "timeout": 1000}
            $table->json('parameters')->nullable();
            
            // Risultati test diagnostico (JSON)
            // Diagnostic test results (JSON)
            // Es. {"success_count": 4, "avg_response_time": 25, "packet_loss": 0}
            $table->json('results')->nullable();
            
            // CommandKey TR-069 per correlazione response
            // TR-069 CommandKey for response correlation
            $table->string('command_key', 100)->nullable()->index();
            
            // Tempi esecuzione
            // Execution times
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Messaggio errore se failed
            // Error message if failed
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            // Indici per query performance
            // Indexes for query performance
            $table->index(['cpe_device_id', 'status']);
            $table->index(['diagnostic_type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_tests');
    }
};
