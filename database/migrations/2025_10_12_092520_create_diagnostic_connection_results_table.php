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
        Schema::create('diagnostic_connection_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostic_test_id')->constrained('diagnostic_tests')->onDelete('cascade');
            $table->integer('connection_number');
            
            // TR-143 timestamp fields with microsecond precision
            $table->decimal('tcp_open_request_time', 20, 6)->nullable()->comment('Microseconds since 1970-01-01 00:00:00');
            $table->decimal('tcp_open_response_time', 20, 6)->nullable()->comment('Microseconds since 1970-01-01 00:00:00');
            $table->decimal('rom_time', 20, 6)->nullable()->comment('Request Out Message time (microseconds)');
            $table->decimal('bom_time', 20, 6)->nullable()->comment('Begin Of Message time (microseconds)');
            $table->decimal('eom_time', 20, 6)->nullable()->comment('End Of Message time (microseconds)');
            
            $table->bigInteger('bytes_transferred')->default(0);
            $table->string('connection_status', 50)->default('pending');
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['diagnostic_test_id', 'connection_number']);
            $table->index('connection_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_connection_results');
    }
};
