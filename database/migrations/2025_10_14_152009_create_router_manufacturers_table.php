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
        Schema::create('router_manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('oui_prefix')->nullable();
            $table->string('category')->nullable();
            $table->string('country')->nullable();
            $table->text('product_lines')->nullable();
            $table->boolean('tr069_support')->default(false);
            $table->boolean('tr369_support')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('name');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('router_manufacturers');
    }
};
