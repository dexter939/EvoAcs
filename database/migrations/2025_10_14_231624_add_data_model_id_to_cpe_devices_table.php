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
            $table->unsignedBigInteger('data_model_id')->nullable()->after('service_id');
            $table->foreign('data_model_id')->references('id')->on('tr069_data_models')->onDelete('set null');
            $table->index('data_model_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cpe_devices', function (Blueprint $table) {
            $table->dropForeign(['data_model_id']);
            $table->dropColumn('data_model_id');
        });
    }
};
