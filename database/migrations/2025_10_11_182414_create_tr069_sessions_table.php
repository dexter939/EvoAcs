<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per tabella tr069_sessions - Gestione sessioni TR-069 CWMP
 * Migration for tr069_sessions table - TR-069 CWMP session management
 * 
 * Questa tabella traccia le sessioni SOAP attive tra ACS e dispositivi CPE.
 * Implementa il session management richiesto dal protocollo TR-069 per:
 * - Gestire conversazioni SOAP stateful
 * - Accodare comandi da inviare al dispositivo durante la sessione
 * - Tracciare risposte e stato della transazione
 * 
 * This table tracks active SOAP sessions between ACS and CPE devices.
 * Implements session management required by TR-069 protocol for:
 * - Managing stateful SOAP conversations
 * - Queuing commands to send to device during session
 * - Tracking responses and transaction state
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tr069_sessions', function (Blueprint $table) {
            $table->id();
            
            // Identificatori sessione / Session identifiers
            $table->string('session_id', 64)->unique()->index();
            $table->foreignId('cpe_device_id')->constrained()->onDelete('cascade');
            
            // Stato sessione / Session state
            $table->enum('status', ['active', 'closing', 'closed', 'timeout'])->default('active')->index();
            
            // Cookie HTTP per session tracking (standard TR-069)
            // HTTP cookie for session tracking (TR-069 standard)
            $table->string('cookie', 128)->nullable();
            
            // Coda comandi SOAP pendenti da inviare al dispositivo (JSON array)
            // Queue of pending SOAP commands to send to device (JSON array)
            // Formato: [{"type": "GetParameterValues", "params": {...}, "task_id": 123}, ...]
            $table->json('pending_commands')->nullable();
            
            // Ultimo comando inviato al dispositivo (per tracking risposta)
            // Last command sent to device (for response tracking)
            $table->json('last_command_sent')->nullable();
            
            // ID messaggio SOAP corrente (per correlazione richiesta/risposta)
            // Current SOAP message ID (for request/response correlation)
            $table->integer('current_message_id')->default(1);
            
            // Timestamp attività / Activity timestamps
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_activity')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            
            // Timeout sessione (secondi, default 30 secondi come da spec TR-069)
            // Session timeout (seconds, default 30 seconds as per TR-069 spec)
            $table->integer('timeout_seconds')->default(30);
            
            // IP address del dispositivo per questa sessione
            // Device IP address for this session
            $table->string('device_ip', 45)->nullable();
            
            $table->timestamps();
            
            // Indici per performance su query frequenti
            // Indexes for performance on frequent queries
            $table->index(['cpe_device_id', 'status']);
            $table->index('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr069_sessions');
    }
};
