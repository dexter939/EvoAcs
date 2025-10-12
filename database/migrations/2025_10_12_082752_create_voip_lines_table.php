<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sip_profile_id')->constrained()->onDelete('cascade');
            $table->string('line_instance')->default('1'); // Line.{i}
            $table->boolean('enabled')->default(true);
            
            // Line Configuration
            $table->string('directory_number')->nullable(); // Phone number
            $table->string('display_name')->nullable();
            $table->string('sip_uri')->nullable(); // Full SIP URI
            $table->string('auth_username')->nullable(); // Line-specific auth
            $table->string('auth_password')->nullable();
            
            // Status
            $table->string('status')->default('Disabled'); // Up, Initializing, Registering, Unregistering, Error, Testing, Quiescent, Disabled
            $table->string('call_state')->default('Idle'); // Idle, Dialing, Ringing, Connecting, InCall, Hold, Disconnecting
            $table->timestamp('last_registration')->nullable();
            
            // Call Features
            $table->boolean('call_waiting_enabled')->default(true);
            $table->boolean('call_forward_enabled')->default(false);
            $table->string('call_forward_number')->nullable();
            $table->boolean('call_forward_on_busy')->default(false);
            $table->boolean('call_forward_on_no_answer')->default(false);
            $table->integer('call_forward_no_answer_timeout')->default(20); // seconds
            
            // DND and Privacy
            $table->boolean('dnd_enabled')->default(false);
            $table->boolean('caller_id_enable')->default(true);
            $table->string('caller_id_name')->nullable();
            $table->boolean('anonymous_call_rejection')->default(false);
            
            // Physical Interface
            $table->string('phy_interface')->nullable(); // FXS port reference
            
            // Statistics
            $table->bigInteger('incoming_calls_received')->default(0);
            $table->bigInteger('incoming_calls_answered')->default(0);
            $table->bigInteger('incoming_calls_failed')->default(0);
            $table->bigInteger('outgoing_calls_attempted')->default(0);
            $table->bigInteger('outgoing_calls_answered')->default(0);
            $table->bigInteger('outgoing_calls_failed')->default(0);
            $table->bigInteger('total_call_time')->default(0); // seconds
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['sip_profile_id', 'line_instance']);
            $table->index('directory_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voip_lines');
    }
};
