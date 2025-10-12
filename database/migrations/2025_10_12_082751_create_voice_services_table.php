<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained()->onDelete('cascade');
            $table->string('service_instance')->default('1'); // VoiceService.{i}
            $table->boolean('enabled')->default(true);
            $table->string('protocol')->default('SIP'); // SIP, MGCP, H323
            $table->integer('max_profiles')->default(4);
            $table->integer('max_lines')->default(8);
            $table->integer('max_sessions')->default(4);
            $table->json('capabilities')->nullable(); // VoiceService.Capabilities
            $table->json('codecs')->nullable(); // Supported codec list
            $table->string('rtp_dscp')->default('46'); // QoS DSCP mark
            $table->integer('rtp_port_min')->default(16384);
            $table->integer('rtp_port_max')->default(32767);
            $table->boolean('stun_enabled')->default(false);
            $table->string('stun_server')->nullable();
            $table->integer('stun_port')->default(3478);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['cpe_device_id', 'service_instance']);
            $table->index('protocol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_services');
    }
};
