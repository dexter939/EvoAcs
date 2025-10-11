<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained()->cascadeOnDelete();
            $table->string('parameter_path', 500)->index();
            $table->text('parameter_value')->nullable();
            $table->enum('parameter_type', ['string', 'int', 'boolean', 'dateTime', 'base64'])->default('string');
            $table->boolean('is_writable')->default(true);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            
            $table->unique(['cpe_device_id', 'parameter_path']);
            $table->index(['parameter_path', 'last_updated']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_parameters');
    }
};
