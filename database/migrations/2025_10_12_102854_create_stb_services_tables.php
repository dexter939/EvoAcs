<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stb_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained('cpe_devices')->onDelete('cascade');
            $table->string('service_type', 100)->comment('IPTV, VoD, PVR, TimeShift');
            $table->string('frontend_type', 50)->comment('IP, DVB-T, DVB-S, DVB-C');
            $table->string('streaming_protocol', 50)->comment('RTSP, RTP, IGMP, HLS, DASH');
            $table->string('server_url')->nullable();
            $table->integer('server_port')->nullable();
            $table->json('channel_list')->nullable()->comment('Available channels');
            $table->json('codec_settings')->nullable()->comment('Video/Audio codecs');
            $table->json('qos_parameters')->nullable()->comment('QoS metrics and thresholds');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->index(['cpe_device_id', 'service_type']);
        });

        Schema::create('streaming_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stb_service_id')->constrained('stb_services')->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('channel_name')->nullable();
            $table->string('content_url')->nullable();
            $table->string('status', 50)->default('active')->comment('active, paused, stopped');
            $table->integer('bitrate')->nullable()->comment('Current bitrate in kbps');
            $table->integer('packet_loss')->nullable()->comment('Packet loss percentage');
            $table->decimal('jitter', 10, 2)->nullable()->comment('Jitter in ms');
            $table->json('qos_metrics')->nullable()->comment('Real-time QoS data');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->index(['stb_service_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaming_sessions');
        Schema::dropIfExists('stb_services');
    }
};
