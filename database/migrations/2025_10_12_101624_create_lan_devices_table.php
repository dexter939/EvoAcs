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
        Schema::create('lan_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained('cpe_devices')->onDelete('cascade');
            
            // TR-64 UPnP/SSDP Discovery
            $table->string('usn', 255)->comment('Unique Service Name from UPnP');
            $table->string('device_type', 100)->nullable()->comment('UPnP device type');
            $table->string('friendly_name', 255)->nullable()->comment('Human-readable name');
            $table->string('manufacturer', 100)->nullable();
            $table->string('model_name', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            
            // Network identification
            $table->string('ip_address', 45)->nullable()->comment('IPv4/IPv6 address');
            $table->string('mac_address', 17)->nullable()->comment('MAC address');
            $table->integer('port')->nullable()->comment('UPnP service port');
            
            // UPnP service endpoints
            $table->json('services')->nullable()->comment('Available UPnP/SOAP services');
            $table->text('description_url')->nullable()->comment('Device description URL');
            $table->string('location', 500)->nullable()->comment('SSDP location header');
            
            // Status tracking
            $table->string('status', 50)->default('active')->comment('active, inactive, offline');
            $table->timestamp('last_seen')->nullable()->comment('Last SSDP announcement');
            $table->timestamp('discovered_at')->nullable()->comment('First discovery timestamp');
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['cpe_device_id', 'status']);
            $table->index('usn');
            $table->index('ip_address');
            $table->index('mac_address');
            $table->index('last_seen');
            
            // Unique constraint: one USN per CPE device
            $table->unique(['cpe_device_id', 'usn']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lan_devices');
    }
};
