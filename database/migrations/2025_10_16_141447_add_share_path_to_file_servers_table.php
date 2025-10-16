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
        Schema::table('file_servers', function (Blueprint $table) {
            $table->string('share_path')->nullable()->after('document_root');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_servers', function (Blueprint $table) {
            $table->dropColumn('share_path');
        });
    }
};
