<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_versions')) {
            return;
        }

        Schema::create('system_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 50)->index();
            $table->enum('deployment_status', ['pending', 'deploying', 'success', 'failed', 'rolled_back'])
                ->default('pending')
                ->index();
            $table->enum('environment', ['local', 'development', 'staging', 'production'])
                ->default('production')
                ->index();
            $table->timestamp('deployed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('migrations_run')->nullable();
            $table->json('health_check_results')->nullable();
            $table->text('deployment_notes')->nullable();
            $table->text('error_log')->nullable();
            $table->string('deployed_by', 100)->nullable();
            $table->string('rollback_version', 50)->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->boolean('is_current')->default(false)->index();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['environment', 'deployment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_versions');
    }
};
