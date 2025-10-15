<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabella pending_commands per NAT Traversal:
     * Quando Connection Request fallisce (CPE dietro NAT), i comandi vengono accodati.
     * Durante il successivo Periodic Inform del CPE, l'ACS esegue i comandi accodati.
     */
    public function up(): void
    {
        Schema::create('pending_commands', function (Blueprint $table) {
            $table->id();
            
            // Foreign key al dispositivo CPE
            $table->foreignId('cpe_device_id')->constrained()->cascadeOnDelete();
            
            // Tipo di comando da eseguire
            $table->enum('command_type', [
                'provision',          // Provisioning con configuration profile
                'reboot',             // Reboot dispositivo
                'get_parameters',     // Get parameter values
                'set_parameters',     // Set parameter values
                'diagnostic',         // TR-143 diagnostics (ping, traceroute, etc.)
                'firmware_update',    // Firmware download
                'factory_reset',      // Factory reset
                'network_scan'        // Network topology scan
            ])->index();
            
            // Stato del comando
            $table->enum('status', [
                'pending',      // In attesa di esecuzione
                'processing',   // In esecuzione
                'completed',    // Completato con successo
                'failed',       // Fallito
                'cancelled'     // Cancellato dall'operatore
            ])->default('pending')->index();
            
            // Priorità (1 = alta, 10 = bassa, default 5)
            // Comandi urgenti (reboot emergency) possono avere priorità 1
            $table->integer('priority')->default(5)->index();
            
            // Parametri specifici del comando (JSON)
            // Es: {"profile_id": 123} per provision
            // Es: {"parameters": ["Device.DeviceInfo.ModelName"]} per get_parameters
            $table->json('parameters')->nullable();
            
            // Risultato dell'esecuzione (JSON)
            $table->json('result_data')->nullable();
            
            // Messaggio di errore (se fallito)
            $table->text('error_message')->nullable();
            
            // Retry management
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            
            // Timestamps
            $table->timestamp('executed_at')->nullable()->index();
            $table->timestamps();
            
            // Indici compositi per performance
            // Query tipica: "dammi tutti i pending commands per device X, ordinati per priorità"
            $table->index(['cpe_device_id', 'status', 'priority']);
            
            // Query: "dammi tutti i pending commands globally, ordinati per priorità e data"
            $table->index(['status', 'priority', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_commands');
    }
};
