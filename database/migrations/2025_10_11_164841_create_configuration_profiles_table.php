<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuration_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('description', 500)->nullable();
            $table->enum('device_type', ['router', 'modem', 'gateway', 'ont', 'other'])->default('gateway')->index();
            $table->string('manufacturer', 100)->nullable()->index();
            $table->string('model', 100)->nullable()->index();
            $table->json('parameters');
            $table->json('wifi_settings')->nullable();
            $table->json('wan_settings')->nullable();
            $table->json('voip_settings')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['manufacturer', 'model']);
            $table->index(['is_active', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_profiles');
    }
};
