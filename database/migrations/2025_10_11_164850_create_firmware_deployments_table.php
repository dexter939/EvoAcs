<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firmware_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firmware_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cpe_device_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['scheduled', 'downloading', 'installing', 'completed', 'failed', 'cancelled'])->default('scheduled')->index();
            $table->integer('download_progress')->default(0);
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();
            
            $table->index(['status', 'scheduled_at']);
            $table->index(['cpe_device_id', 'status']);
            $table->unique(['firmware_version_id', 'cpe_device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmware_deployments');
    }
};
