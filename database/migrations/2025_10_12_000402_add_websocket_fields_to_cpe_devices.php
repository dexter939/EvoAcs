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
            $table->string('websocket_client_id')->nullable()->after('mqtt_client_id');
            $table->timestamp('websocket_connected_at')->nullable()->after('websocket_client_id');
            $table->timestamp('last_websocket_ping')->nullable()->after('websocket_connected_at');
            
            // Index for faster WebSocket client lookups
            $table->index('websocket_client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cpe_devices', function (Blueprint $table) {
            $table->dropIndex(['websocket_client_id']);
            $table->dropColumn(['websocket_client_id', 'websocket_connected_at', 'last_websocket_ping']);
        });
    }
};
