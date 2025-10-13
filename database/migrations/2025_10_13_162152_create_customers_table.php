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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('external_id')->nullable()->unique()->comment('External CRM/billing system ID');
            $table->enum('status', ['active', 'inactive', 'suspended', 'terminated'])->default('active');
            $table->json('billing_contact')->nullable()->comment('Billing contact info (name, email, phone, etc.)');
            $table->string('contact_email')->nullable();
            $table->string('timezone')->default('UTC');
            $table->json('address')->nullable()->comment('Physical address (street, city, zip, country)');
            $table->json('metadata')->nullable()->comment('Custom metadata fields');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status');
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
