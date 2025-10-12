<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sip_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voice_service_id')->constrained()->onDelete('cascade');
            $table->string('profile_instance')->default('1'); // VoiceProfile.{i}
            $table->boolean('enabled')->default(true);
            $table->string('profile_name')->nullable();
            
            // SIP Proxy/Registrar Settings
            $table->string('proxy_server')->nullable();
            $table->integer('proxy_port')->default(5060);
            $table->string('registrar_server')->nullable();
            $table->integer('registrar_port')->default(5060);
            $table->string('outbound_proxy')->nullable();
            $table->integer('outbound_proxy_port')->default(5060);
            
            // Authentication
            $table->string('auth_username')->nullable();
            $table->string('auth_password')->nullable();
            $table->string('domain')->nullable();
            $table->string('realm')->nullable();
            
            // SIP Transport
            $table->string('transport_protocol')->default('UDP'); // UDP, TCP, TLS
            $table->integer('register_expires')->default(3600);
            $table->boolean('register_retry')->default(true);
            $table->integer('register_retry_interval')->default(60);
            
            // Codec Configuration
            $table->json('codec_list')->nullable(); // Ordered codec preference
            $table->integer('packetization_period')->default(20); // milliseconds
            $table->boolean('silence_suppression')->default(false);
            
            // DTMF Settings
            $table->string('dtmf_method')->default('RFC2833'); // InBand, RFC2833, SIPInfo
            $table->string('dtmf_payload_type')->default('101');
            
            // QoS
            $table->string('sip_dscp')->default('26'); // DSCP mark for SIP
            $table->integer('vlan_id')->nullable();
            $table->integer('vlan_priority')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['voice_service_id', 'profile_instance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sip_profiles');
    }
};
