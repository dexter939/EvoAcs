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
            // Rename event_path to notification_type for TR-369 compliance
            $table->renameColumn('event_path', 'notification_type');
            
            // Rename notification_retry to enabled
            $table->renameColumn('notification_retry', 'enabled');
            
            // Rename persist to persistent
            $table->renameColumn('persist', 'persistent');
        });
        
        // Add soft deletes support for subscription cancellation
        Schema::table('usp_subscriptions', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usp_subscriptions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('usp_subscriptions', function (Blueprint $table) {
            $table->renameColumn('notification_type', 'event_path');
            $table->renameColumn('enabled', 'notification_retry');
            $table->renameColumn('persistent', 'persist');
        });
    }
};
