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
        Schema::create('usp_pending_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained()->onDelete('cascade');
            $table->string('msg_id')->unique();
            $table->enum('message_type', ['get', 'set', 'operate', 'add', 'delete'])->index();
            $table->binary('request_payload');
            $table->enum('status', ['pending', 'delivered', 'expired'])->default('pending')->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            
            $table->index(['cpe_device_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usp_pending_requests');
    }
};
