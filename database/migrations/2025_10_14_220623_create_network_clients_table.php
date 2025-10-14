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
        Schema::create('network_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('cpe_devices')->onDelete('cascade');
            $table->string('mac_address', 17)->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('hostname')->nullable();
            $table->enum('connection_type', ['lan', 'wifi_2.4ghz', 'wifi_5ghz', 'wifi_6ghz'])->default('lan');
            $table->string('interface_name', 50)->nullable();
            $table->integer('signal_strength')->nullable()->comment('RSSI dBm for WiFi clients');
            $table->boolean('active')->default(true);
            $table->timestamp('last_seen')->useCurrent();
            $table->timestamps();
            
            $table->unique(['device_id', 'mac_address']);
            $table->index(['device_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_clients');
    }
};
