<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alarms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id')->nullable();
            $table->string('alarm_type', 100);
            $table->enum('severity', ['critical', 'major', 'minor', 'warning', 'info'])->default('info');
            $table->enum('status', ['active', 'acknowledged', 'cleared'])->default('active');
            $table->string('category', 50);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('raised_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('cpe_devices')->onDelete('cascade');
            $table->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['device_id', 'status']);
            $table->index(['severity', 'status']);
            $table->index(['category', 'status']);
            $table->index('raised_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarms');
    }
};
