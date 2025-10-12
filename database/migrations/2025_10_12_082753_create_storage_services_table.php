<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained()->onDelete('cascade');
            $table->string('service_instance')->default('1'); // StorageService.{i}
            $table->boolean('enabled')->default(true);
            
            // Capabilities
            $table->bigInteger('total_capacity')->default(0); // bytes
            $table->bigInteger('used_capacity')->default(0); // bytes
            $table->boolean('raid_supported')->default(false);
            $table->json('supported_raid_types')->nullable(); // [RAID0, RAID1, RAID5, RAID6, RAID10]
            $table->boolean('ftp_supported')->default(true);
            $table->boolean('sftp_supported')->default(true);
            $table->boolean('http_supported')->default(true);
            $table->boolean('https_supported')->default(true);
            $table->boolean('samba_supported')->default(true);
            $table->boolean('nfs_supported')->default(false);
            
            // Physical Medium Info
            $table->integer('physical_mediums_count')->default(0);
            $table->integer('logical_volumes_count')->default(0);
            
            // Health
            $table->string('health_status')->default('OK'); // OK, Warning, Error
            $table->float('temperature')->nullable(); // Celsius
            $table->string('smart_status')->default('PASSED'); // SMART health: PASSED, FAILED, WARNING
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['cpe_device_id', 'service_instance']);
            $table->index('health_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_services');
    }
};
