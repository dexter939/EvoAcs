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
        Schema::table('usp_subscriptions', function (Blueprint $table) {
            // Drop the global unique constraint on subscription_id
            $table->dropUnique(['subscription_id']);
            
            // Add composite unique constraint (cpe_device_id, subscription_id)
            // This allows different devices to have the same subscription_id
            $table->unique(['cpe_device_id', 'subscription_id'], 'usp_subscriptions_device_subscription_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usp_subscriptions', function (Blueprint $table) {
            // Remove composite unique constraint
            $table->dropUnique('usp_subscriptions_device_subscription_unique');
            
            // Restore global unique constraint
            $table->unique('subscription_id');
        });
    }
};
