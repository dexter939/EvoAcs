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
        Schema::create('device_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained('cpe_devices')->onDelete('cascade');
            
            // TR-111 Parameter Discovery
            $table->string('parameter_path', 500)->comment('Full parameter path (e.g., Device.DeviceInfo.ModelName)');
            $table->string('parameter_name', 255)->nullable()->comment('Parameter name without path');
            $table->boolean('is_writable')->default(false)->comment('Whether parameter is writable');
            $table->string('data_type', 50)->nullable()->comment('Parameter data type (string, int, boolean, etc.)');
            $table->boolean('is_vendor_specific')->default(false)->comment('Vendor-specific parameter flag');
            
            // Discovery metadata
            $table->timestamp('discovered_at')->nullable()->comment('When parameter was discovered');
            $table->timestamp('last_verified_at')->nullable()->comment('Last verification timestamp');
            $table->json('metadata')->nullable()->comment('Additional discovery metadata');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['cpe_device_id', 'parameter_path']);
            $table->index(['cpe_device_id', 'is_vendor_specific']);
            $table->index('discovered_at');
            $table->index('is_writable');
            
            // Unique constraint: one entry per device per parameter
            $table->unique(['cpe_device_id', 'parameter_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_capabilities');
    }
};
