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
        Schema::table('cpe_devices', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('id')->constrained('services')->onDelete('restrict');
            $table->index('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cpe_devices', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropIndex(['service_id']);
            $table->dropColumn('service_id');
        });
    }
};
