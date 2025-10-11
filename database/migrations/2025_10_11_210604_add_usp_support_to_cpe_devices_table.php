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
        Schema::table('cpe_devices', function (Blueprint $table) {
            $table->enum('protocol_type', ['tr069', 'tr369'])->default('tr069')->after('serial_number')->index();
            $table->string('usp_endpoint_id', 200)->nullable()->unique()->after('protocol_type');
            $table->string('mqtt_client_id', 200)->nullable()->after('usp_endpoint_id');
            $table->enum('mtp_type', ['mqtt', 'websocket', 'stomp', 'coap', 'uds'])->nullable()->after('mqtt_client_id');
            $table->json('usp_capabilities')->nullable()->after('mtp_type');
            
            $table->index(['protocol_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cpe_devices', function (Blueprint $table) {
            $table->dropIndex(['protocol_type', 'status']);
            $table->dropColumn(['protocol_type', 'usp_endpoint_id', 'mqtt_client_id', 'mtp_type', 'usp_capabilities']);
        });
    }
};
