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
        Schema::create('alert_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('alert_type'); // system, device, performance, security
            $table->string('severity'); // critical, high, medium, low
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->foreignId('related_device_id')->nullable()->constrained('cpe_devices')->onDelete('set null');
            $table->string('notification_channel'); // email, webhook, slack, sms
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();
            
            $table->index(['alert_type', 'severity']);
            $table->index(['status', 'created_at']);
            $table->index('related_device_id');
        });

        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('rule_type'); // threshold, pattern, anomaly
            $table->string('metric'); // cpu, memory, query_time, device_offline, etc.
            $table->string('condition'); // >, <, =, contains, etc.
            $table->string('threshold_value');
            $table->integer('duration_minutes')->default(5);
            $table->string('severity');
            $table->json('notification_channels'); // ['email', 'slack']
            $table->json('recipients'); // email addresses, webhook URLs
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('rule_type');
        });

        Schema::create('system_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name');
            $table->string('metric_type'); // gauge, counter, histogram
            $table->decimal('value', 15, 2);
            $table->json('tags')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index(['metric_name', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_metrics');
        Schema::dropIfExists('alert_rules');
        Schema::dropIfExists('alert_notifications');
    }
};
