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
        Schema::create('usp_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpe_device_id')->constrained()->onDelete('cascade');
            $table->string('subscription_id')->unique();
            $table->string('event_path')->index();
            $table->json('reference_list')->nullable();
            $table->boolean('notification_retry')->default(false);
            $table->boolean('persist')->default(false);
            $table->enum('status', ['active', 'cancelled', 'expired'])->default('active')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_notification_at')->nullable();
            $table->integer('notification_count')->default(0);
            $table->timestamps();
            
            $table->index(['cpe_device_id', 'status']);
            $table->index(['event_path', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usp_subscriptions');
    }
};
