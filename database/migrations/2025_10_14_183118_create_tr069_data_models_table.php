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
        Schema::create('tr069_data_models', function (Blueprint $table) {
            $table->id();
            $table->string('vendor')->index(); // AVM, MikroTik, TP-Link, etc.
            $table->string('model_name'); // FRITZ!Box, RB2011, etc.
            $table->string('firmware_version')->nullable(); // 7.25, 7.20, etc.
            $table->string('protocol_version'); // TR-098, TR-104, TR-140, TR-181, etc.
            $table->string('spec_name')->unique(); // urn:avm-de:fritzos-TR098-7-25-0
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Dati aggiuntivi dal XML
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr069_data_models');
    }
};
