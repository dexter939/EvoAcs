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
        Schema::create('execution_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained()->onDelete('cascade');
            $table->foreignId('deployment_unit_id')->nullable()->constrained()->onDelete('set null');
            $table->string('euid')->unique();
            $table->string('name');
            $table->string('status')->default('Running');
            $table->string('requested_state')->default('Active');
            $table->string('execution_fault_code')->default('NoFault');
            $table->text('execution_fault_message')->nullable();
            $table->string('vendor')->nullable();
            $table->string('version')->nullable();
            $table->integer('run_level')->default(3);
            $table->boolean('auto_start')->default(true);
            $table->string('exec_env_label')->default('Device.SoftwareModules.ExecEnv.1');
            $table->timestamps();

            $table->index(['cpe_device_id', 'status']);
            $table->index('deployment_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('execution_units');
    }
};
