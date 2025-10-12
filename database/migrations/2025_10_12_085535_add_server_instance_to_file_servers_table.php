<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_servers', function (Blueprint $table) {
            $table->string('server_instance', 10)->nullable()->after('storage_service_id');
        });
    }

    public function down(): void
    {
        Schema::table('file_servers', function (Blueprint $table) {
            $table->dropColumn('server_instance');
        });
    }
};
