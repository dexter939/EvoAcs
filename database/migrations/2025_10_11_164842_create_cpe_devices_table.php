<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpe_devices', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number', 100)->unique();
            $table->string('oui', 6)->index();
            $table->string('product_class', 100)->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->string('model_name', 100)->nullable();
            $table->string('hardware_version', 50)->nullable();
            $table->string('software_version', 50)->nullable();
            $table->string('connection_request_url', 500)->nullable();
            $table->string('connection_request_username', 100)->nullable();
            $table->string('connection_request_password', 100)->nullable();
            $table->ipAddress('ip_address')->nullable()->index();
            $table->string('mac_address', 17)->nullable()->index();
            $table->enum('status', ['online', 'offline', 'provisioning', 'error'])->default('offline')->index();
            $table->timestamp('last_inform')->nullable()->index();
            $table->timestamp('last_contact')->nullable();
            $table->foreignId('configuration_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->json('device_info')->nullable();
            $table->json('wan_info')->nullable();
            $table->json('wifi_info')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'last_inform']);
            $table->index(['is_active', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpe_devices');
    }
};
