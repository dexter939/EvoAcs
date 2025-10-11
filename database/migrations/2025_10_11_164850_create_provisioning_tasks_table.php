<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provisioning_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('task_type', ['set_parameters', 'get_parameters', 'add_object', 'delete_object', 'download', 'reboot', 'factory_reset', 'diagnostic'])->index();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending')->index();
            $table->json('task_data');
            $table->json('result_data')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'scheduled_at']);
            $table->index(['cpe_device_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provisioning_tasks');
    }
};
