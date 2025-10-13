<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cpe_devices', function (Blueprint $table) {
            $table->enum('auth_method', ['basic', 'digest'])->default('digest')->after('connection_request_password');
        });
    }

    public function down(): void
    {
        Schema::table('cpe_devices', function (Blueprint $table) {
            $table->dropColumn('auth_method');
        });
    }
};
