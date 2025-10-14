<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuration_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vendor')->nullable();
            $table->string('model')->nullable();
            $table->string('protocol_version');
            $table->text('description')->nullable();
            $table->json('parameters');
            $table->json('validation_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('data_model_id')->nullable()->constrained('tr069_data_models')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['vendor', 'model', 'protocol_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_templates');
    }
};
