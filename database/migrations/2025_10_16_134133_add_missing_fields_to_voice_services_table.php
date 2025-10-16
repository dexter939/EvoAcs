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
        Schema::table('voice_services', function (Blueprint $table) {
            $table->string('service_name')->nullable()->after('service_instance');
            $table->string('service_type')->nullable()->after('service_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voice_services', function (Blueprint $table) {
            $table->dropColumn(['service_name', 'service_type']);
        });
    }
};
