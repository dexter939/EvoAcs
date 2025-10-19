<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained()->cascadeOnDelete();
            $table->string('event_type'); // provisioning, reboot, firmware_update, diagnostic, connection_request, parameter_change
            $table->string('event_status'); // pending, processing, completed, failed
            $table->string('event_title');
            $table->text('event_description')->nullable();
            $table->json('event_data')->nullable(); // Additional metadata (profile_id, firmware_version, diagnostic_type, etc.)
            $table->string('triggered_by')->nullable(); // web, tr069, tr369, system, scheduler
            $table->string('user_email')->nullable(); // User who triggered the event (if applicable)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['cpe_device_id', 'created_at']);
            $table->index(['event_type', 'event_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_events');
    }
};
