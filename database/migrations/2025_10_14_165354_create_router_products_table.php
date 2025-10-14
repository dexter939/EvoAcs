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
        Schema::create('router_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->constrained('router_manufacturers')->onDelete('cascade');
            $table->string('model_name');
            $table->string('wifi_standard')->nullable();
            $table->string('max_speed')->nullable();
            $table->integer('release_year')->nullable();
            $table->decimal('price_usd', 10, 2)->nullable();
            $table->text('key_features')->nullable();
            $table->string('product_line')->nullable();
            $table->string('form_factor')->nullable();
            $table->boolean('mesh_support')->default(false);
            $table->boolean('gaming_features')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('manufacturer_id');
            $table->index('wifi_standard');
            $table->index('release_year');
            $table->index(['manufacturer_id', 'model_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('router_products');
    }
};
