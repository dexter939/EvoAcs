<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('smart_home_devices')) {
            Schema::create('smart_home_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cpe_device_id')->constrained('cpe_devices')->onDelete('cascade');
                $table->string('device_class', 100)->comment('Smart device class: lighting, sensor, thermostat, security, lock, camera');
                $table->string('device_name');
                $table->string('protocol', 50)->comment('ZigBee, Z-Wave, WiFi, BLE, Matter, Thread');
                $table->string('ieee_address', 100)->nullable()->comment('Device IEEE/MAC address');
                $table->string('manufacturer')->nullable();
                $table->string('model')->nullable();
                $table->string('firmware_version')->nullable();
                $table->string('status', 50)->default('online')->comment('online, offline, error, pairing');
                $table->json('capabilities')->nullable()->comment('Device capabilities array');
                $table->json('current_state')->nullable()->comment('Current device state (on/off, brightness, temperature, etc)');
                $table->json('configuration')->nullable()->comment('Device-specific configuration');
                $table->timestamp('last_seen')->nullable();
                $table->timestamps();
                $table->index(['cpe_device_id', 'device_class']);
                $table->index(['protocol', 'status']);
            });
        }

        if (!Schema::hasTable('iot_services')) {
            Schema::create('iot_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cpe_device_id')->constrained('cpe_devices')->onDelete('cascade');
                $table->string('service_type', 100)->comment('lighting_automation, climate_control, security_monitoring, energy_management');
                $table->string('service_name');
                $table->boolean('enabled')->default(true);
                $table->json('linked_devices')->nullable()->comment('Array of smart_home_device IDs');
                $table->json('automation_rules')->nullable()->comment('Automation logic: triggers, conditions, actions');
                $table->json('schedule')->nullable()->comment('Time-based schedules');
                $table->json('statistics')->nullable()->comment('Service usage statistics');
                $table->timestamps();
                $table->index(['cpe_device_id', 'service_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_services');
        Schema::dropIfExists('smart_home_devices');
    }
};
