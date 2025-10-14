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
        Schema::create('tr069_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_model_id')->constrained('tr069_data_models')->onDelete('cascade');
            $table->string('parameter_path')->index(); // InternetGatewayDevice.DeviceInfo.SerialNumber
            $table->string('parameter_name'); // SerialNumber
            $table->string('parameter_type'); // string, unsignedInt, boolean, dateTime, etc.
            $table->string('access_type'); // R, W, RW
            $table->boolean('is_object')->default(false); // true se è un object, false se è parameter
            $table->text('description')->nullable();
            $table->string('default_value')->nullable();
            $table->string('min_version')->nullable(); // 6.20, 7.00, etc.
            $table->json('validation_rules')->nullable(); // pattern, size, range, enumeration
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Index composto per ricerche veloci
            $table->index(['data_model_id', 'parameter_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr069_parameters');
    }
};
