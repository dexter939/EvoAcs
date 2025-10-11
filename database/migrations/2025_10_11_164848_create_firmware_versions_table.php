<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firmware_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 50)->index();
            $table->string('manufacturer', 100)->index();
            $table->string('model', 100)->index();
            $table->string('file_path', 500);
            $table->string('file_hash', 64);
            $table->bigInteger('file_size')->unsigned();
            $table->text('release_notes')->nullable();
            $table->text('changelog')->nullable();
            $table->boolean('is_stable')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('release_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['manufacturer', 'model', 'version']);
            $table->index(['is_active', 'is_stable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmware_versions');
    }
};
