<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logical_volumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_service_id')->constrained()->onDelete('cascade');
            $table->string('volume_instance')->default('1'); // LogicalVolume.{i}
            $table->boolean('enabled')->default(true);
            $table->string('volume_name');
            
            // Volume Configuration
            $table->string('filesystem')->default('ext4'); // ext4, ntfs, exfat, btrfs, zfs
            $table->bigInteger('capacity')->default(0); // bytes
            $table->bigInteger('used_space')->default(0); // bytes
            $table->bigInteger('free_space')->default(0); // bytes
            $table->float('usage_percent')->default(0);
            
            // RAID Configuration
            $table->string('raid_level')->nullable(); // RAID0, RAID1, RAID5, RAID6, RAID10
            $table->string('raid_status')->nullable(); // Healthy, Degraded, Failed, Rebuilding
            $table->integer('rebuild_progress')->nullable(); // 0-100%
            
            // Mount Configuration
            $table->string('mount_point')->nullable();
            $table->boolean('auto_mount')->default(true);
            $table->boolean('read_only')->default(false);
            
            // Quota Settings
            $table->boolean('quota_enabled')->default(false);
            $table->bigInteger('quota_size')->nullable(); // bytes
            $table->float('quota_warning_threshold')->default(90); // percentage
            
            // Encryption
            $table->boolean('encrypted')->default(false);
            $table->string('encryption_algorithm')->nullable(); // AES256, AES128
            
            // Status
            $table->string('status')->default('Offline'); // Online, Offline, Error, Formatting, Checking
            $table->timestamp('last_check')->nullable();
            $table->integer('health_percentage')->default(100);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['storage_service_id', 'volume_instance']);
            $table->index('volume_name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logical_volumes');
    }
};
