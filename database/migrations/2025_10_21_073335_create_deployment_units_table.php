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
        Schema::create('deployment_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('duid')->unique();
            $table->string('name');
            $table->string('status')->default('Installed');
            $table->boolean('resolved')->default(true);
            $table->string('url')->nullable();
            $table->string('vendor')->nullable();
            $table->string('version')->nullable();
            $table->text('description')->nullable();
            $table->string('execution_env_ref')->default('Device.SoftwareModules.ExecEnv.1');
            $table->timestamps();

            $table->index(['cpe_device_id', 'status']);
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployment_units');
    }
};
