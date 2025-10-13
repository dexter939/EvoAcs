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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->string('name');
            $table->enum('service_type', ['FTTH', 'VoIP', 'IPTV', 'IoT', 'Femtocell', 'Other'])->default('Other');
            $table->enum('status', ['provisioned', 'active', 'suspended', 'terminated'])->default('provisioned');
            $table->string('contract_number')->nullable()->unique();
            $table->timestamp('activation_at')->nullable();
            $table->timestamp('termination_at')->nullable();
            $table->string('sla_tier')->nullable()->comment('SLA tier (Gold, Silver, Bronze, etc.)');
            $table->string('billing_reference')->nullable()->comment('External billing system reference');
            $table->json('tags')->nullable()->comment('Service tags for categorization');
            $table->json('metadata')->nullable()->comment('Custom metadata fields');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('customer_id');
            $table->index('service_type');
            $table->index('status');
            $table->index('contract_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
