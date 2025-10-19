<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('module');
            $table->string('action');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            
            $table->index('slug');
            $table->index(['module', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
